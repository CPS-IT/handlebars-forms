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
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * RenderablesContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_RENDERABLES'])]
final class RenderablesContentObject extends AbstractHandlebarsFormsContentObject
{
    /**
     * @param iterable<Domain\Renderable\ViewModel\ViewModelBuilder<Form\Domain\Model\Renderable\RootRenderableInterface>> $viewModelBuilders
     */
    public function __construct(
        #[DependencyInjection\Attribute\AutowireIterator('handlebars_forms.view_model_builder')]
        private readonly iterable $viewModelBuilders,
    ) {}

    /**
     * @return list<mixed>
     */
    protected function resolve(array $configuration, Context\ValueResolutionContext $context): array
    {
        $renderable = $context->renderable;
        $processedRenderables = [];

        // Use current page as base renderable if we're on root form context
        if ($renderable instanceof Form\Domain\Runtime\FormRuntime) {
            $renderable = $renderable->getCurrentPage() ?? $renderable;
        }

        // Fetch renderables from base renderable. On default sections (e.g. pages), this reflects
        // all direct children. On all other composite renderables, this reflects all renderables
        // recursively (including deeply nested rebderables). If we have a non-composite base
        // renderable in place, we do nothing since this value resolver only handles composite renderables.
        if ($renderable instanceof Form\Domain\Model\FormElements\AbstractSection) {
            $renderables = $renderable->getElements();
        } elseif ($renderable instanceof Form\Domain\Model\Renderable\CompositeRenderableInterface) {
            $renderables = $renderable->getRenderablesRecursively();
        } else {
            $renderables = [];
        }

        foreach ($renderables as $child) {
            if (!$child->isEnabled()) {
                continue;
            }

            $childConfiguration = $configuration[$child->getType() . '.'] ?? null;

            if (is_array($childConfiguration)) {
                $childViewModel = $this->buildViewModel($child, $context->viewModel->renderingContext);
            } else {
                $childConfiguration = [];
                $childViewModel = new Domain\Renderable\ViewModel\ViewModel($context->viewModel->renderingContext, null);
            }

            $processedChild = $context->process($childConfiguration, $child, $childViewModel);

            if ($processedChild !== null) {
                $processedRenderables[] = $processedChild;
            }
        }

        return $processedRenderables;
    }

    private function buildViewModel(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\Renderable\ViewModel\ViewModel {
        foreach ($this->viewModelBuilders as $viewModelBuilder) {
            if ($viewModelBuilder->supports($renderable)) {
                return $viewModelBuilder->build($renderable, $renderingContext);
            }
        }

        return new Domain\Renderable\ViewModel\ViewModel($renderingContext, null);
    }
}
