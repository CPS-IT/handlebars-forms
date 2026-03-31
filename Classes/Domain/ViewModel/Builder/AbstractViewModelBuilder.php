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

namespace CPSIT\Typo3HandlebarsForms\Domain\ViewModel\Builder;

use CPSIT\Typo3HandlebarsForms\Domain;
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
    ): Domain\ViewModel\ViewModel {
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

        if (!($viewModel instanceof Domain\ViewModel\ViewModel)) {
            $viewModel = new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result);
        }

        $this->applyGridColumnClasses($renderable, $viewModel, $renderingContext);

        return $viewModel;
    }

    /**
     * @param T $renderable
     */
    protected function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ?Domain\ViewModel\ViewModel {
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
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
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
        Domain\ViewModel\ViewModel $viewModel,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): void {
        if (!($renderable instanceof Form\Domain\Model\Renderable\RenderableInterface)) {
            return;
        }

        if (!($renderable->getParentRenderable() instanceof Form\Domain\Model\FormElements\GridRowInterface)) {
            return;
        }

        $gridResult = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\GridColumnClassAutoConfigurationViewHelper::class,
            [
                'element' => $renderable,
            ],
        );

        if (is_string($gridResult->content) && $viewModel instanceof Domain\ViewModel\TagAwareViewModel) {
            $tag = $viewModel->getTag();
            $tag->addAttribute(
                'class',
                trim($tag->getAttribute('class') . ' ' . $gridResult->content),
            );
        }
    }
}
