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

namespace CPSIT\Typo3HandlebarsForms\Domain\Renderer;

use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Core;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * FluidRenderableRenderer
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
final readonly class FluidRenderableRenderer
{
    public function __construct(
        #[DependencyInjection\Attribute\Autowire(service: Fluid\View\FluidViewFactory::class)]
        private Core\View\ViewFactoryInterface $viewFactory,
    ) {}

    public function render(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Form\Domain\Runtime\FormRuntime $formRuntime,
    ): string {
        $view = $this->buildView($formRuntime);
        $view->assign('form', $formRuntime);
        $view->assign('element', $renderable);

        if ($view instanceof Fluid\View\FluidViewAdapter) {
            $view->getRenderingContext()
                ->getViewHelperVariableContainer()
                ->addOrUpdate(Form\ViewHelpers\RenderRenderableViewHelper::class, 'formRuntime', $formRuntime)
            ;
        }

        return $view->render('RenderRenderable');
    }

    private function buildView(Form\Domain\Runtime\FormRuntime $formRuntime): Core\View\ViewInterface
    {
        $renderingOptions = $formRuntime->getRenderingOptions();
        $templateRootPaths = $renderingOptions['templateRootPaths'] ?? [];
        $partialRootPaths = $renderingOptions['partialRootPaths'] ?? [];
        $layoutRootPaths = $renderingOptions['layoutRootPaths'] ?? [];

        if (!is_array($templateRootPaths)) {
            $templateRootPaths = [];
        }
        if (!is_array($partialRootPaths)) {
            $partialRootPaths = [];
        }
        if (!is_array($layoutRootPaths)) {
            $layoutRootPaths = [];
        }

        // Inject template root path which contains the "bridge" template
        $templateRootPaths[100] = 'EXT:handlebars_forms/Resources/Private/Templates/Fluid';

        $viewFactoryData = new Core\View\ViewFactoryData(
            templateRootPaths: $templateRootPaths,
            partialRootPaths: $partialRootPaths,
            layoutRootPaths: $layoutRootPaths,
            request: $formRuntime->getRequest(),
        );

        return $this->viewFactory->create($viewFactoryData);
    }
}
