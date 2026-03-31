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

use CPSIT\Typo3HandlebarsForms\Fluid;
use Psr\Http\Message;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * ValidationResultsContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_VALIDATION_RESULTS'])]
final class ValidationResultsContentObject extends AbstractHandlebarsFormsContentObject
{
    public function __construct(
        private readonly Fluid\ViewHelperInvoker $viewHelperInvoker,
    ) {}

    protected function resolve(array $configuration, Context\ValueResolutionContext $context): mixed
    {
        $outputInstruction = $configuration['output'] ?? null;
        $outputConfiguration = $configuration['output.'] ?? null;
        $renderable = $context->renderable;

        // Resolve property path from renderable
        if ($renderable instanceof Form\Domain\Model\Renderable\AbstractRenderable) {
            $property = $renderable->getRootForm()->getIdentifier() . '.' . $renderable->getIdentifier();
        } else {
            $property = $renderable->getIdentifier();
        }

        $request = $context->renderingContext->getAttribute(Message\ServerRequestInterface::class);
        $extbaseRequestParameters = $request->getAttribute('extbase');

        // Early return when content object was requested outside of extbase context
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
                $context,
                $validationResults,
                $outputInstruction,
                $outputConfiguration ?? [],
            );
        }

        // Resolve complex rendering configuration
        if (is_array($outputConfiguration)) {
            return $this->processRenderingConfiguration($context, $validationResults, $outputConfiguration);
        }

        // Return raw results in case no rendering instructions are specified
        return $validationResults;
    }

    /**
     * @param array<string|int, mixed> $configuration
     * @return array<string|int, mixed>
     */
    private function processRenderingConfiguration(
        Context\ValueResolutionContext $context,
        Extbase\Error\Result $result,
        array $configuration,
        bool $retainUnmatchedRenderingConfiguration = false,
    ): array {
        $processedData = [];

        foreach ($configuration as $key => $value) {
            $keyWithoutDot = rtrim((string)$key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (is_array($value)) {
                if (!array_key_exists($keyWithoutDot, $processedData)) {
                    // Process nested rendering configuration
                    $processedData[$keyWithoutDot] = $this->processRenderingConfiguration(
                        $context,
                        $result,
                        $value,
                        $retainUnmatchedRenderingConfiguration,
                    );
                } elseif ($retainUnmatchedRenderingConfiguration) {
                    // Keep non-resolvable configuration (may be resolved differently)
                    $processedData[$keyWithDot] = $value;
                }
            } elseif (is_string($value)) {
                $valueConfiguration = $configuration[$keyWithDot] ?? [];

                if (!is_array($valueConfiguration)) {
                    $valueConfiguration = [];
                }

                $processedData[$keyWithoutDot] = $this->processRenderingInstruction(
                    $context,
                    $result,
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
        Extbase\Error\Result $result,
        string $value,
        array $configuration,
    ): mixed {
        return match ($value) {
            'EACH_ERROR' => $this->processErrors($context, $result, $configuration),
            'EACH_RENDERABLE' => $this->processRenderables($context, $result, $configuration),
            'ERROR_MESSAGE' => $this->processErrorMessage($context, $result),
            'PROPERTY' => $this->processProperty($context, $configuration),
            'RESULT' => $this->processResult($result, $configuration),
            default => $value,
        };
    }

    /**
     * @param array<string|int, mixed> $configuration
     * @return array<string, list<array<string|int, mixed>>>|list<array<string|int, mixed>>
     */
    private function processErrors(
        Context\ValueResolutionContext $context,
        Extbase\Error\Result $result,
        array $configuration,
    ): array {
        $renderable = $context->renderable;
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
                    $context->withRenderable($currentRenderable),
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

    /**
     * @param array<string|int, mixed> $configuration
     * @return array<string, mixed>
     */
    private function processRenderables(
        Context\ValueResolutionContext $context,
        Extbase\Error\Result $result,
        array $configuration,
    ): array {
        $renderable = $context->renderable;
        $processedRenderables = [];

        foreach ($result->getFlattenedErrors() as $propertyPath => $errors) {
            $currentRenderable = $renderable;

            if ($propertyPath !== '' && $renderable instanceof Form\Domain\Runtime\FormRuntime) {
                $currentRenderable = $renderable->getFormDefinition()->getElementByIdentifier($propertyPath);
            }

            // Skip errors if current renderable could not be resolved
            if ($currentRenderable === null) {
                continue;
            }

            // Process validation result first
            $processedRenderable = $this->processRenderingConfiguration(
                $context->withRenderable($currentRenderable),
                $result->forProperty($propertyPath),
                $configuration,
                true,
            );

            // Normalize rendering configuration for post-processing
            $processedRenderable = $this->normalizeRenderingConfiguration($processedRenderable);

            // Post-process renderable
            $processedRenderables[$propertyPath] = $context->process($processedRenderable, $currentRenderable);
        }

        return $processedRenderables;
    }

    /**
     * @param array<string|int, mixed> $processedRenderable
     * @return array<string|int, mixed>
     */
    private function normalizeRenderingConfiguration(array $processedRenderable): array
    {
        foreach ($processedRenderable as $key => $value) {
            $keyWithoutDot = rtrim((string)$key, '.');
            $keyWithDot = $keyWithoutDot . '.';

            if (!is_array($value) || $key === $keyWithDot) {
                continue;
            }

            if (!array_key_exists($keyWithDot, $processedRenderable)) {
                $processedRenderable[$keyWithDot] = $this->normalizeRenderingConfiguration($value);
            }

            if (!is_array($processedRenderable[$keyWithDot])) {
                $processedRenderable[$keyWithDot] = [];
            }

            $processedRenderable[$keyWithDot] = array_replace_recursive($processedRenderable[$keyWithDot], $value);

            unset($processedRenderable[$keyWithoutDot]);
        }

        return $processedRenderable;
    }

    private function processErrorMessage(Context\ValueResolutionContext $context, Extbase\Error\Result $result): mixed
    {
        $error = $result->getFirstError();

        // Early return if no error is attached to result
        if ($error === false) {
            return null;
        }

        $translationResult = $this->viewHelperInvoker->invoke(
            $context->renderingContext,
            Form\ViewHelpers\TranslateElementErrorViewHelper::class,
            [
                'element' => $context->renderable,
                'error' => $error,
            ],
        );

        return $translationResult->content;
    }

    /**
     * @param array<string|int, mixed> $configuration
     */
    private function processProperty(Context\ValueResolutionContext $context, array $configuration): mixed
    {
        $path = $configuration['path'] ?? null;

        if (!is_string($path)) {
            return null;
        }

        return Extbase\Reflection\ObjectAccess::getProperty($context->renderable, $path);
    }

    /**
     * @param array<string|int, mixed> $configuration
     */
    private function processResult(Extbase\Error\Result $result, array $configuration): mixed
    {
        $propertyPath = $configuration['propertyPath'];

        if (!is_string($propertyPath)) {
            return $result;
        }

        return Extbase\Reflection\ObjectAccess::getProperty($result, $propertyPath);
    }
}
