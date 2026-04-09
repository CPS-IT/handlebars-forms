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
    private const IDENTIFIER_COUNT = 'HBS_RENDERABLES_COUNT';
    private const IDENTIFIER_CURRENT = 'HBS_RENDERABLES_CURRENT';

    /**
     * @param iterable<Domain\ViewModel\Builder\ViewModelBuilder<Form\Domain\Model\Renderable\RootRenderableInterface>> $viewModelBuilders
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
        $baseRenderable = $renderable = $context->renderable;
        $processedRenderables = [];

        // Use current page as base renderable if we're on root form context
        if ($baseRenderable instanceof Form\Domain\Runtime\FormRuntime) {
            $renderable = $baseRenderable->getCurrentPage() ?? $baseRenderable;
        }

        // Resolve rendering order
        if (is_string($configuration['order'] ?? null)) {
            $order = RenderingOrder::from($configuration['order']);
        } else {
            $order = RenderingOrder::determineFromRenderable($renderable);
        }

        // Fetch renderables from base renderable:
        // - On summary pages, the base renderable defines the selection of renderables:
        //   + If the incoming renderable is the summary page, we use ALL ELEMENTS of the configured form.
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
                match ($order) {
                    RenderingOrder::Flat => $this->isElement(...),
                    RenderingOrder::Hierarchical => $this->isTopLevelElement(...),
                },
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

        // Add renderables count to TSFE register
        // @todo Use $this->request->getAttribute('frontend.register.stack') in TYPO3 v14
        $tsfe = $this->getTypoScriptFrontendController();
        $tsfe->register[self::IDENTIFIER_COUNT] = count($renderables);

        foreach ($renderables as $index => $child) {
            if (!$this->isEnabled($child)) {
                continue;
            }

            if (array_key_exists($child->getType() . '.', $configuration)) {
                // Use configured type-specific configuration (e.g. "Fieldset." for fieldsets)
                $childConfiguration = $configuration[$child->getType() . '.'];
            } elseif (!array_key_exists('default.', $configuration)) {
                // Skip rendering on missing fallback config
                continue;
            } else {
                // Use configured fallback configuration ("default.")
                $childConfiguration = $configuration['default.'];
            }

            if (is_array($childConfiguration)) {
                $childViewModel = $this->buildViewModel($child, $context->renderingContext);
            } else {
                $childConfiguration = [];
                $childViewModel = new Domain\ViewModel\SimpleViewModel($child);
            }

            // Add current renderable index to TSFE register
            $tsfe->register[self::IDENTIFIER_CURRENT] = $index;

            try {
                $processedChild = $context->process($childConfiguration, $child, $childViewModel);
            } finally {
                unset($tsfe->register[self::IDENTIFIER_CURRENT]);
            }

            if ($processedChild !== null) {
                $processedRenderables[] = $processedChild;
            }
        }

        unset($tsfe->register[self::IDENTIFIER_COUNT]);

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

    private function isTopLevelElement(Form\Domain\Model\Renderable\RenderableInterface $renderable): bool
    {
        return $this->isElement($renderable)
            && $renderable->getParentRenderable() instanceof Form\Domain\Model\FormElements\Page
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
