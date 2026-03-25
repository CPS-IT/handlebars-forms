<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS extension "handlebars_forms".
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace CPSIT\Typo3HandlebarsForms\DataProcessing;

use CPSIT\Typo3HandlebarsForms\ContentObject;
use CPSIT\Typo3HandlebarsForms\Domain;
use CPSIT\Typo3HandlebarsForms\Utility;
use Psr\Http\Message;
use Psr\Log;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ProcessFormProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('data.processor', ['identifier' => 'process-form'])]
final readonly class ProcessFormProcessor implements Frontend\ContentObject\DataProcessorInterface
{
    private const CONTENT_PLACEHOLDER = '###FORM_CONTENT###';

    public function __construct(
        private Log\LoggerInterface $logger,
        private Fluid\Core\Rendering\RenderingContextFactory $renderingContextFactory,
        private Domain\Renderable\ViewModel\FormViewModelBuilder $formRenderableProcessor,
        private ContentObject\Context\ValueCollector $valueCollector,
        private ContentObject\Context\ContextStack $contextStack,
    ) {}

    /**
     * @param array<string|int, mixed> $contentObjectConfiguration
     * @param array<string|int, mixed> $processorConfiguration
     * @param array<string|int, mixed> $processedData
     * @return array<string|int, mixed>
     */
    public function process(
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData,
    ): array {
        $formRuntime = $contentObjectConfiguration['variables.']['form'] ?? null;

        if (!($formRuntime instanceof Form\Domain\Runtime\FormRuntime)) {
            $this->logger->error(
                'Form runtime is not available when trying to process form with plugin uid "{uid}".',
                ['uid' => $cObj->data['uid'] ?? '(unknown)'],
            );

            return $processedData;
        }

        // Create and prepare Fluid rendering context
        $renderingContext = $this->renderingContextFactory->create();
        $renderingContext->setAttribute(Message\ServerRequestInterface::class, $formRuntime->getRequest());
        $renderingContext->getViewHelperVariableContainer()->addOrUpdate(
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            'formRuntime',
            $formRuntime,
        );

        // Render form and process renderables as part of the form's renderChildrenClosure.
        // Since the final rendered form content (which especially contains all relevant hidden fields)
        // is not yet available when processing renderables, we temporarily pass a content placeholder
        // for all configured CONTENT values and replace them with the real content value later.
        $this->formRenderableProcessor->build(
            $formRuntime,
            $renderingContext,
            function (FluidStandalone\Core\ViewHelper\TagBuilder $tagBuilder) use (
                $cObj,
                $formRuntime,
                &$processedData,
                $processorConfiguration,
                $renderingContext,
                &$tag,
            ) {
                $tag = $tagBuilder;
                $tag->setContent(self::CONTENT_PLACEHOLDER);

                $viewModel = new Domain\Renderable\ViewModel\ViewModel($renderingContext, null, $tag);
                $processedData = $this->processRenderable($formRuntime, $processorConfiguration, $cObj, $viewModel) ?? [];

                return '';
            },
        );

        $formContent = $tag?->getContent();

        // Replace content placeholder with final rendered form content
        if ($formContent !== null) {
            array_walk_recursive($processedData, static function (&$value) use ($formContent) {
                if (Utility\StringUtility::isStringable($value)) {
                    $value = Utility\StringUtility::processStringable(
                        $value,
                        static fn(string $string) => str_replace(self::CONTENT_PLACEHOLDER, $formContent, $string),
                    );
                }
            });
        }

        return $processedData;
    }

    /**
     * @param array<string|int, mixed> $configuration
     * @return array<string|int, mixed>|null
     */
    private function processRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        array $configuration,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
    ): ?array {
        $processedData = [];

        // Early return on configured "if" condition evaluating to false
        if (!$this->checkIf($configuration, $renderable, $cObj, $viewModel)) {
            return null;
        }

        // Merge TS reference (=<) and replace configuration with merged configuration
        $this->mergeTypoScriptReferences($configuration, $cObj);

        foreach ($configuration as $key => $value) {
            $keyWithoutDot = rtrim((string)$key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (is_array($value) && !array_key_exists($keyWithoutDot, $processedData)) {
                $resolvedValue = $this->processRenderable($renderable, $value, $cObj, $viewModel);

                if (is_array($resolvedValue)) {
                    $processedData[$keyWithoutDot] = $resolvedValue;
                }
            }

            if (!is_string($value)) {
                continue;
            }

            $valueConfiguration = $configuration[$keyWithDot] ?? [];
            $contentObject = $cObj->getContentObject($value);

            if (!is_array($valueConfiguration)) {
                $valueConfiguration = [];
            }

            // Resolve configured value
            if ($contentObject !== null) {
                $context = new ContentObject\Context\ValueResolutionContext(
                    $renderable,
                    $viewModel,
                    fn(
                        array $contextConfiguration,
                        ?Form\Domain\Model\Renderable\RootRenderableInterface $contextRenderable = null,
                        ?Domain\Renderable\ViewModel\ViewModel $contextViewModel = null,
                    ) => $this->processRenderable(
                        $contextRenderable ?? $renderable,
                        $contextConfiguration,
                        $cObj,
                        $contextViewModel ?? $viewModel,
                    ),
                );

                $this->contextStack->push($context);

                try {
                    $resolvedValue = $cObj->render($contentObject, $valueConfiguration);
                } finally {
                    $this->contextStack->pop();
                }

                if ($this->valueCollector->has($resolvedValue)) {
                    $resolvedValue = $this->valueCollector->load($resolvedValue);
                }
            } else {
                $resolvedValue = $value;
            }

            // Skip further processing if processed value is not a string (all COR related methods require a string value)
            if (!Utility\StringUtility::isStringable($resolvedValue)) {
                $processedData[$keyWithoutDot] = $resolvedValue;
                continue;
            }

            // Skip value if a configured "if" evaluates to false
            if (is_array($valueConfiguration['if.'] ?? null)) {
                $valueConfiguration['if.']['value'] ??= (string)$resolvedValue;

                if (!$this->checkIf($valueConfiguration, $renderable, $cObj, $viewModel)) {
                    continue;
                }
            }

            $processedData[$keyWithoutDot] = $resolvedValue;
        }

        return $processedData;
    }

    /**
     * @param array<string|int, mixed> $configuration
     */
    private function mergeTypoScriptReferences(
        array &$configuration,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
    ): void {
        $processedKeys = [];

        foreach ($configuration as $key => $value) {
            if (in_array($key, $processedKeys, true)) {
                continue;
            }

            $keyWithoutDot = rtrim((string)$key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (array_key_exists($keyWithDot, $configuration) && is_array($configuration[$keyWithDot])) {
                $this->mergeTypoScriptReferences($configuration[$keyWithDot], $cObj);
            }

            if (!array_key_exists($keyWithoutDot, $configuration)) {
                continue;
            }

            $mergedConfig = $cObj->mergeTSRef(
                [
                    $keyWithoutDot => $configuration[$keyWithoutDot] ?? '',
                    $keyWithDot => $configuration[$keyWithDot] ?? [],
                ],
                $keyWithoutDot,
            );

            $configuration[$keyWithoutDot] = $mergedConfig[$keyWithoutDot];

            if ($mergedConfig[$keyWithDot] !== []) {
                $configuration[$keyWithDot] = $mergedConfig[$keyWithDot];
            }

            $processedKeys[] = $keyWithoutDot;
            $processedKeys[] = $keyWithDot;
        }
    }

    /**
     * @param array<string|int, mixed> $configuration
     */
    private function checkIf(
        array &$configuration,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
    ): bool {
        if (!is_array($configuration['if.'] ?? null)) {
            return true;
        }

        $cObjTemp = clone $cObj;

        if (is_string($configuration['if.']['currentValue'] ?? null) || is_array($configuration['if.']['currentValue.'] ?? null)) {
            $processedValue = $this->processRenderable(
                $renderable,
                [
                    'currentValue' => $configuration['if.']['currentValue'] ?? '',
                    'currentValue.' => $configuration['if.']['currentValue.'] ?? [],
                ],
                $cObj,
                $viewModel,
            );

            $cObjTemp->setCurrentVal($processedValue['currentValue'] ?? null);

            unset($configuration['if.']['currentValue'], $configuration['if.']['currentValue.']);
        }

        if (!$cObjTemp->checkIf($configuration['if.'])) {
            return false;
        }

        unset($configuration['if.']);

        return true;
    }
}
