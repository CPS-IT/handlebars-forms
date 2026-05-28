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

use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
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
        private Core\View\ViewFactoryInterface $viewFactory,
        private ViewHelperInvoker $viewHelperInvoker,
    ) {}

    /**
     * @param array<string, mixed> $variables
     */
    public function render(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Form\Domain\Runtime\FormRuntime $formRuntime,
        array $variables = [],
    ): string {
        // Determine renderable variable name based on the renderable type. This is necessary
        // because the various partials expected different naming for the renderable variable.
        $renderableVariableName = match (true) {
            $renderable instanceof Form\Domain\Runtime\FormRuntime => 'form',
            $renderable instanceof Form\Domain\Model\FormElements\Page => 'page',
            default => 'element',
        };

        $view = $this->buildView($formRuntime);
        $view->assignMultiple([
            ...$variables,
            'form' => $formRuntime,
            // Add renderable both as "generic" variable (to perform actions within our template)
            // as well as "special" variable (to pass it to the rendered [= external] Fluid partial).
            'renderable' => $renderable,
            $renderableVariableName => $renderable,
        ]);

        // Perform simple rendering for all non-Fluid views
        if (!($view instanceof Fluid\View\FluidViewAdapter) ||
            !($view->getRenderingContext() instanceof Fluid\Core\Rendering\RenderingContext)
        ) {
            return $view->render('RenderRenderable');
        }

        $result = '';

        // Wrap partial rendering in simulated <f:form> rendering, because the underlying partials
        // may call view helpers which depend on global view helper variable names, which are
        // populated in the <f:form> view helper. When rendered outside this context, view helpers
        // may throw exceptions and we won't be able to properly render the requested renderable then.
        $this->viewHelperInvoker->invoke(
            $view->getRenderingContext(),
            Form\ViewHelpers\FormViewHelper::class,
            [
                'object' => $formRuntime,
            ],
            static function () use (&$result, $view) {
                $result = $view->render('RenderRenderable');

                return '';
            },
        );

        return $result;
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

        $view = $this->viewFactory->create($viewFactoryData);

        if ($view instanceof Fluid\View\FluidViewAdapter) {
            $view->getRenderingContext()
                ->getViewHelperVariableContainer()
                ->addOrUpdate(Form\ViewHelpers\RenderRenderableViewHelper::class, 'formRuntime', $formRuntime)
            ;
        }

        return $view;
    }
}
