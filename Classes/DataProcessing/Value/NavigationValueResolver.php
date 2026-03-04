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
use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvocationResult;
use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * NavigationValueProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class NavigationValueResolver implements ValueResolver
{
    private const PREVIOUS_PAGE = 'previousPage';
    private const NEXT_PAGE = 'nextPage';
    private const SUBMIT = 'submit';

    public function __construct(
        private ViewHelperInvoker $viewHelperInvoker,
    ) {}

    /**
     * @return list<mixed>
     */
    public function resolve(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        ValueResolutionContext $context = new ValueResolutionContext(),
    ): array {
        $elements = [];

        // We cannot process navigation within a concrete form element
        if (!($renderable instanceof Form\Domain\Runtime\FormRuntime)) {
            return [];
        }

        // Add previous page button
        if ($renderable->getPreviousPage() !== null) {
            $elements[self::PREVIOUS_PAGE] = $renderable->getPreviousPage();
        }

        // Add next page OR submit button
        if ($renderable->getNextPage() !== null) {
            $elements[self::NEXT_PAGE] = $renderable->getNextPage();
        } else {
            $elements[self::SUBMIT] = $renderable;
        }

        return $this->processElements($elements, $context, $viewModel->renderingContext, $renderable);
    }

    /**
     * @param array{
     *     previousPage?: Form\Domain\Model\FormElements\Page,
     *     nextPage?: Form\Domain\Model\FormElements\Page,
     *     submit?: Form\Domain\Runtime\FormRuntime,
     * } $renderables
     * @return list<mixed>
     */
    private function processElements(
        array $renderables,
        ValueResolutionContext $context,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        Form\Domain\Runtime\FormRuntime $formRuntime,
    ): array {
        $processedElements = [];

        foreach ($renderables as $step => $stepRenderable) {
            $stepConfiguration = $context[$step . '.'] ?? null;

            if (is_array($stepConfiguration)) {
                $buttonResult = $this->processButton($renderingContext, $stepRenderable, $formRuntime, $step);
                $stepViewModel = new Domain\Renderable\ViewModel\ViewModel(
                    $renderingContext,
                    $buttonResult->content,
                    $buttonResult->tag,
                );
            } else {
                $stepConfiguration = [];
                $stepViewModel = new Domain\Renderable\ViewModel\ViewModel($renderingContext, null);
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

            $buttonResult->tag->addAttribute('label', $labelResult);
        }

        return $buttonResult;
    }

    public static function getName(): string
    {
        return 'NAVIGATION';
    }
}
