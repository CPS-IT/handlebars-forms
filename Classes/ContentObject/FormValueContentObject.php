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

namespace CPSIT\Typo3HandlebarsForms\ContentObject;

use CPSIT\Typo3HandlebarsForms\Domain;
use CPSIT\Typo3HandlebarsForms\Fluid;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Form;

/**
 * FormValueContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_FORM_VALUE'])]
final class FormValueContentObject extends AbstractHandlebarsFormsContentObject
{
    public function __construct(
        private readonly Fluid\ViewHelperInvoker $viewHelperInvoker,
    ) {}

    protected function resolve(array $configuration, Context\ValueResolutionContext $context): mixed
    {
        $outputInstruction = $configuration['output'] ?? null;
        $outputConfiguration = $configuration['output.'] ?? null;
        $formValueViewModel = null;
        $formValueVariableName = 'formValue';

        $this->viewHelperInvoker->invoke(
            $context->renderingContext,
            Form\ViewHelpers\RenderFormValueViewHelper::class,
            [
                'renderable' => $context->renderable,
                'as' => $formValueVariableName,
            ],
            function () use ($context, &$formValueViewModel, $formValueVariableName) {
                $formValueContext = $context->renderingContext->getVariableProvider()->get($formValueVariableName);

                if (!is_array($formValueContext)) {
                    return null;
                }

                /** @var array<string, mixed> $formValueContext */
                $formValueViewModel = Domain\ViewModel\FormValueViewModel::fromArray($formValueContext);

                return '';
            },
        );

        // Early return if form value could not be resolved
        if ($formValueViewModel === null) {
            return null;
        }

        // Normalize output configuration
        if (!is_array($outputConfiguration)) {
            $outputConfiguration = null;
        }

        // Apply view model to context
        $context = $context->withViewModel($formValueViewModel);

        if (is_string($outputInstruction)) {
            return $this->processRenderingInstruction($context, $outputInstruction, $outputConfiguration ?? []);
        }

        // Resolve complex rendering configuration
        if (is_array($outputConfiguration)) {
            return $this->processRenderingConfiguration($context, $outputConfiguration);
        }

        // Return processed value in case no rendering instructions are specified
        return $formValueViewModel->processedValue;
    }

    /**
     * @param array<string|int, mixed> $configuration
     * @return array<string|int, mixed>
     */
    private function processRenderingConfiguration(
        Context\ValueResolutionContext $context,
        array $configuration,
    ): array {
        $processedData = [];

        foreach ($configuration as $key => $value) {
            $keyWithoutDot = rtrim((string)$key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (is_array($value)) {
                if (!array_key_exists($keyWithoutDot, $processedData)) {
                    // Process nested rendering configuration
                    $processedData[$keyWithoutDot] = $this->processRenderingConfiguration($context, $value);
                }
            } elseif (is_string($value)) {
                $valueConfiguration = $configuration[$keyWithDot] ?? [];

                if (!is_array($valueConfiguration)) {
                    $valueConfiguration = [];
                }

                $processedData[$keyWithoutDot] = $this->processRenderingInstruction(
                    $context,
                    $value,
                    $valueConfiguration,
                );
            }
        }

        return $processedData;
    }

    /**
     * @param array<string|int, mixed> $configuration
     */
    private function processRenderingInstruction(
        Context\ValueResolutionContext $context,
        string $value,
        array $configuration,
    ): mixed {
        /** @var Domain\ViewModel\FormValueViewModel $viewModel */
        $viewModel = $context->viewModel;

        return match ($value) {
            'EACH_PROCESSED_VALUE', 'EACH_VALUE' => $this->processEachValue($context, $configuration),
            'IS_MULTI_VALUE' => $viewModel->isMultiValue,
            'IS_SECTION' => $viewModel->isSection,
            'PROCESSED_VALUE' => $viewModel->processedValue,
            'VALUE' => $viewModel->value,
            default => $value,
        };
    }

    /**
     * @param array<string|int, mixed> $configuration
     * @return list<array<string|int, mixed>>
     */
    private function processEachValue(Context\ValueResolutionContext $context, array $configuration): array
    {
        /** @var Domain\ViewModel\FormValueViewModel $viewModel */
        $viewModel = $context->viewModel;
        $processedData = [];

        foreach ($viewModel->getChildren() as $child) {
            $processedData[] = $this->processRenderingConfiguration($context->withViewModel($child), $configuration);
        }

        return $processedData;
    }
}
