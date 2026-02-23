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

use CPSIT\Typo3HandlebarsForms\DataProcessing;
use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * NavigationValueProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class NavigationValueProcessor implements ValueProcessor
{
    public function __construct(
        private ViewHelperInvoker $viewHelperInvoker,
    ) {}

    /**
     * @return list<NavigationElement>
     */
    public function process(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        DataProcessing\Renderable\ProcessedRenderable $processedRenderable,
        array $configuration = [],
    ): array {
        $navigationElements = [];
        $renderables = [];

        // We cannot process navigation within a concrete form element
        if (!($renderable instanceof Form\Domain\Runtime\FormRuntime)) {
            return [];
        }

        if ($renderable->getPreviousPage() !== null) {
            $renderables['previousPage'] = $renderable->getPreviousPage();
        }

        if ($renderable->getNextPage() !== null) {
            $renderables['nextPage'] = $renderable->getNextPage();
        } else {
            $renderables['submit'] = $renderable;
        }

        foreach ($renderables as $step => $pageOrForm) {
            $stepConfiguration = $configuration[$step . '.'] ?? null;
            $isPage = $pageOrForm instanceof Form\Domain\Model\FormElements\Page;

            if (is_array($stepConfiguration)) {
                $buttonResult = $this->viewHelperInvoker->invoke(
                    $processedRenderable->renderingContext,
                    Fluid\ViewHelpers\Form\ButtonViewHelper::class,
                    [
                        'property' => '__currentPage',
                        'value' => $isPage ? $pageOrForm->getIndex() : count($pageOrForm->getPages()),
                    ],
                );

                $labelResult = $this->viewHelperInvoker->invoke(
                    $processedRenderable->renderingContext,
                    Form\ViewHelpers\TranslateElementPropertyViewHelper::class,
                    [
                        'element' => $isPage ? $renderable->getCurrentPage() : $renderable,
                        'renderingOptionProperty' => match ($step) {
                            'previousPage' => 'previousButtonLabel',
                            'nextPage' => 'nextButtonLabel',
                            'submit' => 'submitButtonLabel',
                        },
                    ],
                );

                $buttonResult->tag->addAttribute('label', $labelResult->content);

                $navigationElements[] = new NavigationElement(
                    $pageOrForm,
                    new DataProcessing\Renderable\ProcessedRenderable(
                        $processedRenderable->renderingContext,
                        $buttonResult->content,
                        $buttonResult->tag,
                    ),
                    $stepConfiguration,
                );
            } else {
                $navigationElements[] = new NavigationElement(
                    $pageOrForm,
                    new DataProcessing\Renderable\ProcessedRenderable($processedRenderable->renderingContext, null),
                );
            }
        }

        return $navigationElements;
    }

    public static function getName(): string
    {
        return 'NAVIGATION';
    }
}
