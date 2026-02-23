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

    /**
     * @param iterable<Renderable\RenderableProcessor> $renderableProcessors
     * @param DependencyInjection\ServiceLocator<Value\ValueProcessor> $valueProcessors
     */
    public function __construct(
        private Log\LoggerInterface $logger,
        private Fluid\Core\Rendering\RenderingContextFactory $renderingContextFactory,
        private Renderable\FormRenderableProcessor $formRenderableProcessor,
        #[DependencyInjection\Attribute\AutowireIterator('handlebars_forms.renderable_processor', exclude: Renderable\FormRenderableProcessor::class)]
        private iterable $renderableProcessors,
        #[DependencyInjection\Attribute\AutowireLocator('handlebars_forms.value_processor', defaultIndexMethod: 'getName')]
        private DependencyInjection\ServiceLocator $valueProcessors,
    ) {}

    /**
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
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

        $this->formRenderableProcessor->process(
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

                $processedRenderable = new Renderable\ProcessedRenderable($renderingContext, null, $tag);
                $processedData = $this->processRenderable($processorConfiguration, $formRuntime, $cObj, $processedRenderable) ?? [];

                return '';
            },
        );

        $inputFields = $tag?->getContent() ?? '';

        array_walk_recursive($processedData, static function (&$value) use ($inputFields) {
            if (is_string($value)) {
                $value = str_replace(self::CONTENT_PLACEHOLDER, $inputFields, $value);
            }
        });

        return $processedData;
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>|null
     */
    private function processRenderable(
        array $configuration,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        Renderable\ProcessedRenderable $processedRenderable,
    ): ?array {
        $processedData = [];

        // Early return on configured "if" condition evaluating to false
        if (!$this->checkIf($configuration, $renderable, $cObj, $processedRenderable)) {
            return null;
        }

        foreach ($configuration as $key => $value) {
            $keyWithoutDot = rtrim($key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (is_array($value) && !array_key_exists($keyWithoutDot, $processedData)) {
                $processedValue = $this->processRenderable($value, $renderable, $cObj, $processedRenderable);

                if (is_array($processedValue)) {
                    $processedData[$keyWithoutDot] = $processedValue;
                }
            }

            if (!is_string($value)) {
                continue;
            }

            $valueConfiguration = $configuration[$keyWithDot] ?? [];

            // Process configured value
            if ($this->valueProcessors->has($value)) {
                $processedValue = $this->valueProcessors->get($value)->process(
                    $renderable,
                    $processedRenderable,
                    $valueConfiguration,
                );
            } elseif ($value === 'RENDERABLES') {
                $processedValue = $this->processRenderables(
                    $valueConfiguration,
                    $cObj,
                    $renderable,
                    $processedRenderable->renderingContext,
                );
            } else {
                $processedValue = $value;
            }

            // Post-process navigation
            if (is_array($processedValue) && Value\NavigationValueProcessor::getName() === $value) {
                $processedValue = $this->processNavigation($processedValue, $cObj, $processedRenderable);
            }

            // Skip further processing if processed value is not a string (all COR related methods require a string value)
            if (!is_string($processedValue)) {
                $processedData[$keyWithoutDot] = $processedValue;
                continue;
            }

            // Process value with stdWrap
            if (is_array($valueConfiguration['stdWrap.'] ?? null)) {
                $processedValue = $cObj->stdWrap($processedValue, $valueConfiguration['stdWrap.']);
            }

            // Skip value if a configured "if" evaluates to false
            if (is_array($valueConfiguration['if.'] ?? null)) {
                $valueConfiguration['if.']['value'] ??= $processedValue;

                if (!$cObj->checkIf($valueConfiguration['if.'])) {
                    continue;
                }
            }

            $processedData[$keyWithoutDot] = $processedValue;
        }

        return $processedData;
    }

    /**
     * @param array<string, array<string, mixed>> $configuration
     * @return list<array<string, mixed>>
     */
    private function processRenderables(
        array $configuration,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        Form\Domain\Model\Renderable\RootRenderableInterface $rootRenderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): array {
        $processedElements = [];

        if ($rootRenderable instanceof Form\Domain\Runtime\FormRuntime) {
            $renderables = $rootRenderable->getCurrentPage()?->getRenderablesRecursively() ?? [];
        } elseif ($rootRenderable instanceof Form\Domain\Model\Renderable\CompositeRenderableInterface) {
            $renderables = $rootRenderable->getRenderablesRecursively();
        } else {
            // We cannot process non-composite renderables here
            return [];
        }

        foreach ($renderables as $renderable) {
            $typeConfiguration = $configuration[$renderable->getType() . '.'] ?? null;

            if (is_array($typeConfiguration)) {
                $processedRenderable = $this->buildFormProperties($renderable, $renderingContext);
            } else {
                $typeConfiguration = [];
                $processedRenderable = new Renderable\ProcessedRenderable($renderingContext, null);
            }

            $processedElement = $this->processRenderable(
                $typeConfiguration,
                $renderable,
                $cObj,
                $processedRenderable,
            );

            if ($processedElement !== null) {
                $processedElements[] = $processedElement;
            }
        }

        return $processedElements;
    }

    /**
     * @param list<Value\NavigationElement> $navigationElements
     * @return list<array<string, mixed>>
     */
    private function processNavigation(
        array $navigationElements,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        Renderable\ProcessedRenderable $processedRenderable,
    ): array {
        $processedElements = [];

        foreach ($navigationElements as $navigationElement) {
            $processedElement = $this->processRenderable(
                $navigationElement->configuration,
                $navigationElement->renderable,
                $cObj,
                $navigationElement->processedRenderable,
            );

            if ($processedElement !== null) {
                $processedElements[] = $processedElement;
            }
        }

        return $processedElements;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function checkIf(
        array &$configuration,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Frontend\ContentObject\ContentObjectRenderer $cObj,
        Renderable\ProcessedRenderable $processedRenderable,
    ): bool {
        if (!is_array($configuration['if.'] ?? null)) {
            return true;
        }

        $cObjTemp = clone $cObj;

        if (is_string($configuration['if.']['value'] ?? null) && is_array($configuration['if.']['value.'] ?? null)) {
            $processedValue = $this->processRenderable(
                [
                    'value' => $configuration['if.']['value'],
                    'value.' => $configuration['if.']['value.'],
                ],
                $renderable,
                $cObj,
                $processedRenderable,
            );

            $cObjTemp->setCurrentVal($processedValue['value'] ?? null);

            unset($configuration['if.']['value'], $configuration['if.']['value.']);
        }

        if (!$cObjTemp->checkIf($configuration['if.'])) {
            return false;
        }

        unset($configuration['if.']);

        return true;
    }

    private function buildFormProperties(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Renderable\ProcessedRenderable {
        foreach ($this->renderableProcessors as $renderableProcessor) {
            if ($renderableProcessor->supports($renderable)) {
                return $renderableProcessor->process($renderable, $renderingContext);
            }
        }

        return new Renderable\ProcessedRenderable($renderingContext, null);
    }
}
