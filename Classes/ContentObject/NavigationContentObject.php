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
use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvocationResult;
use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * NavigationContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_NAVIGATION'])]
final class NavigationContentObject extends AbstractHandlebarsFormsContentObject
{
    private const PREVIOUS_PAGE = 'previousPage';
    private const NEXT_PAGE = 'nextPage';
    private const SUBMIT = 'submit';

    public function __construct(
        private readonly ViewHelperInvoker $viewHelperInvoker,
    ) {}

    /**
     * @return list<mixed>
     */
    protected function resolve(array $configuration, Context\ValueResolutionContext $context): array
    {
        $renderable = $context->renderable;
        $elements = [];

        // We cannot process navigation within a concrete form element
        if (!($renderable instanceof Form\Domain\Runtime\FormRuntime)) {
            return [];
        }

        // Add previous page button
        if ($renderable->getPreviousEnabledPage() !== null) {
            $elements[self::PREVIOUS_PAGE] = $renderable->getPreviousEnabledPage();
        }

        // Add next page OR submit button
        if ($renderable->getNextEnabledPage() !== null) {
            $elements[self::NEXT_PAGE] = $renderable->getNextEnabledPage();
        } else {
            $elements[self::SUBMIT] = $renderable;
        }

        return $this->processElements($elements, $configuration, $context, $renderable);
    }

    /**
     * @param array<self::*, Form\Domain\Model\FormElements\Page|Form\Domain\Runtime\FormRuntime> $renderables
     * @param array<string, mixed> $configuration
     * @return list<mixed>
     */
    private function processElements(
        array $renderables,
        array $configuration,
        Context\ValueResolutionContext $context,
        Form\Domain\Runtime\FormRuntime $formRuntime,
    ): array {
        $renderingContext = $context->viewModel->renderingContext;
        $processedElements = [];

        foreach ($renderables as $step => $stepRenderable) {
            $stepConfiguration = $configuration[$step . '.'] ?? null;

            if (is_array($stepConfiguration)) {
                $buttonResult = $this->processButton($renderingContext, $stepRenderable, $formRuntime, $step);
                $stepViewModel = new Domain\Renderable\ViewModel\ViewModel(
                    $renderingContext,
                    $buttonResult->content,
                    $buttonResult->tag,
                );
            } else {
                $stepConfiguration = [];
                $stepViewModel = new Domain\Renderable\ViewModel\ViewModel($renderingContext);
            }

            $processedElement = $context->process($stepConfiguration, $stepRenderable, $stepViewModel);

            if ($processedElement !== null) {
                $processedElements[] = $processedElement;
            }
        }

        return $processedElements;
    }

    /**
     * @param self::* $step
     */
    private function processButton(
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        Form\Domain\Model\FormElements\Page|Form\Domain\Runtime\FormRuntime $pageOrForm,
        Form\Domain\Runtime\FormRuntime $formRuntime,
        string $step,
    ): ViewHelperInvocationResult {
        $isPage = $pageOrForm instanceof Form\Domain\Model\FormElements\Page;
        $labelRenderable = $isPage ? $formRuntime->getCurrentPage() : $formRuntime;

        $buttonResult = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Fluid\ViewHelpers\Form\ButtonViewHelper::class,
            [
                'property' => '__currentPage',
                'value' => $isPage ? $pageOrForm->getIndex() : count($pageOrForm->getPages()),
            ],
        );

        if ($labelRenderable !== null) {
            $labelResult = $this->viewHelperInvoker->translateElementProperty(
                $renderingContext,
                $labelRenderable,
                match ($step) {
                    self::PREVIOUS_PAGE => 'previousButtonLabel',
                    self::NEXT_PAGE => 'nextButtonLabel',
                    self::SUBMIT => 'submitButtonLabel',
                },
                'renderingOptionProperty',
            );

            // @todo Check if this can be done in a better way
            if (is_string($labelResult)) {
                $buttonResult->tag->addAttribute('label', $labelResult);
            }
        }

        return $buttonResult;
    }
}
