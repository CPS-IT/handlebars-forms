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

namespace CPSIT\Typo3HandlebarsForms\Domain\Renderable\ViewModel;

use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * AbstractViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @template T of Form\Domain\Model\Renderable\RootRenderableInterface
 * @implements ViewModelBuilder<T>
 */
abstract class AbstractViewModelBuilder implements ViewModelBuilder
{
    /**
     * @var list<non-empty-string>
     */
    protected array $supportedTypes = [];

    public function __construct(
        protected readonly ViewHelperInvoker $viewHelperInvoker,
    ) {}

    public function build(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ViewModel {
        $viewModel = null;
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            ['renderable' => $renderable],
            function () use ($renderable, $renderingContext, &$viewModel) {
                $viewModel = $this->renderRenderable($renderable, $renderingContext);

                return '';
            },
        );

        if (!($viewModel instanceof ViewModel)) {
            $viewModel = new ViewModel($renderingContext, $result->content, $result->tag);
        }

        $this->applyGridColumnClasses($renderable, $viewModel);

        return $viewModel;
    }

    /**
     * @param T $renderable
     */
    protected function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ?ViewModel {
        return null;
    }

    public function supports(Form\Domain\Model\Renderable\RootRenderableInterface $renderable): bool
    {
        return in_array($renderable->getType(), $this->supportedTypes, true);
    }

    /**
     * @param T $renderable
     * @return array<string|int, mixed>
     */
    protected function renderAdditionalAttributes(
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
    ): array {
        $content = $this->viewHelperInvoker->translateElementProperty(
            $renderingContext,
            $renderable,
            'fluidAdditionalAttributes',
        );

        if (!is_array($content)) {
            return [];
        }

        return $content;
    }

    /**
     * @param T $renderable
     */
    protected function applyGridColumnClasses(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        ViewModel $viewModel,
    ): void {
        if (!($renderable instanceof Form\Domain\Model\Renderable\RenderableInterface)) {
            return;
        }

        if ($renderable->getParentRenderable()?->getType() !== 'GridRow') {
            return;
        }

        $gridResult = $this->viewHelperInvoker->invoke(
            $viewModel->renderingContext,
            Form\ViewHelpers\GridColumnClassAutoConfigurationViewHelper::class,
            [
                'element' => $renderable,
            ],
        );

        if (is_string($gridResult->content)) {
            $viewModel->tag->addAttribute(
                'class',
                trim($viewModel->tag->getAttribute('class') . ' ' . $gridResult->content),
            );
        }
    }
}
