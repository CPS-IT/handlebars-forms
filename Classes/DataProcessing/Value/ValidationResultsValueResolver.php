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

namespace CPSIT\Typo3HandlebarsForms\DataProcessing\Value;

use CPSIT\Typo3HandlebarsForms\Domain;
use CPSIT\Typo3HandlebarsForms\Fluid;
use Psr\Http\Message;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * ValidationResultsValueResolver
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class ValidationResultsValueResolver implements ValueResolver
{
    public function __construct(
        private Fluid\ViewHelperInvoker $viewHelperInvoker,
    ) {}

    public function resolve(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        ValueResolutionContext $context = new ValueResolutionContext(),
    ): mixed {
        $outputInstruction = $context['output'];
        $outputConfiguration = $context['output.'];

        // Resolve form definition from renderable
        if ($renderable instanceof Form\Domain\Model\Renderable\AbstractRenderable) {
            $property = $renderable->getRootForm()->getIdentifier() . '.' . $renderable->getIdentifier();
        } else {
            $property = $renderable->getIdentifier();
        }

        $request = $viewModel->renderingContext->getAttribute(Message\ServerRequestInterface::class);
        $extbaseRequestParameters = $request->getAttribute('extbase');

        // Early return when resolver was requested outside of extbase context
        if (!($extbaseRequestParameters instanceof Extbase\Mvc\ExtbaseRequestParameters)) {
            return null;
        }

        $validationResults = $extbaseRequestParameters->getOriginalRequestMappingResults()->forProperty($property);

        // Normalize output configuration
        if (!is_array($outputConfiguration)) {
            $outputConfiguration = null;
        }

        // Resolve validation results by a single rendering instruction
        if (is_string($outputInstruction)) {
            return $this->processRenderingInstruction(
                $renderable,
                $viewModel,
                $validationResults,
                $outputInstruction,
                $outputConfiguration ?? [],
            );
        }

        // Resolve complex rendering configuration
        if (is_array($outputConfiguration)) {
            return $this->processRenderingConfiguration(
                $renderable,
                $viewModel,
                $validationResults,
                $outputConfiguration,
            );
        }

        // Return raw results in case no rendering instructions are specified
        return $validationResults;
    }

    /**
     * @param array<string, mixed> $configuration
     * @return array<string, mixed>
     */
    private function processRenderingConfiguration(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        Extbase\Error\Result $result,
        array $configuration,
    ): array {
        $processedData = [];

        foreach ($configuration as $key => $value) {
            $keyWithoutDot = rtrim($key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (is_array($value) && !array_key_exists($keyWithoutDot, $processedData)) {
                $processedData[$keyWithoutDot] = $this->processRenderingConfiguration(
                    $renderable,
                    $viewModel,
                    $result,
                    $value,
                );
            } elseif (is_string($value)) {
                $processedData[$keyWithoutDot] = $this->processRenderingInstruction(
                    $renderable,
                    $viewModel,
                    $result,
                    $value,
                    $configuration[$keyWithDot] ?? [],
                );
            }
        }

        return $processedData;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function processRenderingInstruction(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        Extbase\Error\Result $result,
        string $value,
        array $configuration,
    ): mixed {
        return match ($value) {
            'EACH_ERROR' => $this->processErrors($renderable, $viewModel, $result, $configuration),
            'ERROR_MESSAGE' => $this->processErrorMessage($renderable, $viewModel, $result),
            'PROPERTY' => $this->processProperty($result, $configuration),
            default => $value,
        };
    }

    /**
     * @param array<string, mixed> $configuration
     * @return ($renderable is Form\Domain\Model\Renderable\CompositeRenderableInterface|Form\Domain\Runtime\FormRuntime ? array<string, list<array<string, mixed>>> : list<array<string, mixed>>)
     */
    private function processErrors(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        Extbase\Error\Result $result,
        array $configuration,
    ): array {
        $processedErrors = [];

        foreach ($result->getFlattenedErrors() as $propertyPath => $errors) {
            $currentRenderable = $renderable;

            if ($propertyPath !== '' && $renderable instanceof Form\Domain\Runtime\FormRuntime) {
                $currentRenderable = $renderable->getFormDefinition()->getElementByIdentifier($propertyPath);
            }

            // Skip errors if current renderable could not be resolved
            if ($currentRenderable === null) {
                continue;
            }

            $processedResult = [];

            foreach ($errors as $error) {
                $errorResult = new Extbase\Error\Result();
                $errorResult->addError($error);

                $processedResult[] = $this->processRenderingConfiguration(
                    $currentRenderable,
                    $viewModel,
                    $errorResult,
                    $configuration,
                );
            }

            $processedErrors[$propertyPath] = $processedResult;
        }

        if ($renderable instanceof Form\Domain\Model\Renderable\CompositeRenderableInterface
            || $renderable instanceof Form\Domain\Runtime\FormRuntime
        ) {
            return $processedErrors;
        }

        $firstProcessedError = reset($processedErrors);

        if ($firstProcessedError !== false) {
            return $firstProcessedError;
        }

        return [];
    }

    private function processErrorMessage(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        Extbase\Error\Result $result,
    ): mixed {
        $error = $result->getFirstError();

        // Early return if no error is attached to result
        if ($error === false) {
            return null;
        }

        $translationResult = $this->viewHelperInvoker->invoke(
            $viewModel->renderingContext,
            Form\ViewHelpers\TranslateElementErrorViewHelper::class,
            [
                'element' => $renderable,
                'error' => $error,
            ],
        );

        return $translationResult->content;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function processProperty(Extbase\Error\Result $result, array $configuration): mixed
    {
        $path = $configuration['path'] ?? null;

        if (!is_string($path)) {
            return null;
        }

        return Extbase\Reflection\ObjectAccess::getProperty($result, $path);
    }

    public static function getName(): string
    {
        return 'VALIDATION_RESULTS';
    }
}
