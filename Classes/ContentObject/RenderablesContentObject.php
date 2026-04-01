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
    private const RENDERABLE_INDEX_IDENTIFIER = '_currentRenderableIndex';

    /**
     * @param iterable<Domain\ViewModel\Builder\ViewModelBuilder<Form\Domain\Model\Renderable\RootRenderableInterface>> $viewModelBuilders
     */
    public function __construct(
        #[DependencyInjection\Attribute\AutowireIterator('handlebars_forms.view_model_builder')]
        private readonly iterable $viewModelBuilders,
        private readonly PassthroughContentObject $passthroughContentObject,
    ) {}

    /**
     * @return list<mixed>
     */
    protected function resolve(array $configuration, Context\ValueResolutionContext $context): array
    {
        $baseRenderable = $renderable = $context->renderable;
        $processedRenderables = [];

        // Use current page as base renderable if we're on root form context
        if ($baseRenderable instanceof Form\Domain\Runtime\FormRuntime) {
            $renderable = $baseRenderable->getCurrentPage() ?? $baseRenderable;
        }

        // Fetch renderables from base renderable:
        // - On summary pages, the base renderable defines the selection of renderables:
        //   + If the incoming renderable is the summary page, we use ALL elements (no sections) of the configured form.
        //   + If the incoming renderable is the root form, we explicitly render the summary page renderable
        //     to allow further configuration of this specific page type. In TypoScript, the form renderables may
        //     still be rendered for summary pages by using a combination of HBS_RENDERABLES objects for form & page:
        //       formData {
        //         items = HBS_RENDERABLES
        //         items {
        //           # ...
        //           SummaryPage {
        //             elements = HBS_RENDERABLES
        //             elements {
        //               Text { ... }
        //               # ...
        //             }
        //           }
        //         }
        //       }
        // - On default sections (e.g. non-summary pages), this reflects all direct children.
        // - On all other composite renderables, this reflects all renderables recursively (including deeply nested
        //   renderables).
        // - If we have a non-composite base renderable in place, we do nothing since this value resolver only handles
        //   composite renderables.
        if ($baseRenderable instanceof Form\Domain\Model\FormElements\Page && $baseRenderable->getType() === 'SummaryPage') {
            $renderables = array_filter(
                $baseRenderable->getRootForm()->getRenderablesRecursively(),
                $this->isElement(...),
            );
        } elseif ($renderable instanceof Form\Domain\Model\FormElements\Page && $renderable->getType() === 'SummaryPage') {
            $renderables = [$renderable];
        } elseif ($renderable instanceof Form\Domain\Model\FormElements\AbstractSection) {
            $renderables = $renderable->getElements();
        } elseif ($renderable instanceof Form\Domain\Model\Renderable\CompositeRenderableInterface) {
            $renderables = $renderable->getRenderablesRecursively();
        } else {
            $renderables = [];
        }

        foreach ($renderables as $index => $child) {
            if (!$this->isEnabled($child)) {
                continue;
            }

            if (!array_key_exists($child->getType() . '.', $configuration)) {
                $processedRenderables[] = $this->passthroughContentObject->render();

                continue;
            }

            $childConfiguration = $configuration[$child->getType() . '.'];

            if (is_array($childConfiguration)) {
                $childViewModel = $this->buildViewModel($child, $context->renderingContext);
            } else {
                $childConfiguration = [];
                $childViewModel = new Domain\ViewModel\SimpleViewModel($child);
            }

            if ($this->cObj !== null) {
                $this->cObj->data[self::RENDERABLE_INDEX_IDENTIFIER] = $index;
            }

            try {
                $processedChild = $context->process($childConfiguration, $child, $childViewModel);
            } finally {
                if ($this->cObj !== null) {
                    unset($this->cObj->data[self::RENDERABLE_INDEX_IDENTIFIER]);
                }
            }

            if ($processedChild !== null) {
                $processedRenderables[] = $processedChild;
            }
        }

        return $processedRenderables;
    }

    private function buildViewModel(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\ViewModel {
        foreach ($this->viewModelBuilders as $viewModelBuilder) {
            if ($viewModelBuilder->supports($renderable)) {
                return $viewModelBuilder->build($renderable, $renderingContext);
            }
        }

        return new Domain\ViewModel\SimpleViewModel($renderable);
    }

    private function isElement(Form\Domain\Model\Renderable\RenderableInterface $renderable): bool
    {
        return $renderable instanceof Form\Domain\Model\FormElements\FormElementInterface
            && $this->isEnabled($renderable)
        ;
    }

    private function isEnabled(Form\Domain\Model\Renderable\RenderableInterface $renderable): bool
    {
        if (!$renderable->isEnabled()) {
            return false;
        }

        while (($renderable = $renderable->getParentRenderable()) !== null) {
            if (!$renderable->isEnabled()) {
                return false;
            }
        }

        return true;
    }
}
