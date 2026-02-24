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
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * RenderablesValueProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class RenderablesValueProcessor implements ValueProcessor
{
    /**
     * @param iterable<DataProcessing\Renderable\RenderableProcessor<Form\Domain\Model\Renderable\RootRenderableInterface>> $renderableProcessors
     */
    public function __construct(
        #[DependencyInjection\Attribute\AutowireIterator('handlebars_forms.renderable_processor')]
        private iterable $renderableProcessors,
    ) {}

    /**
     * @return list<mixed>
     */
    public function process(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        DataProcessing\Renderable\RenderableViewModel $viewModel,
        ProcessingContext $context = new ProcessingContext(),
    ): array {
        $processedRenderables = [];

        if ($renderable instanceof Form\Domain\Runtime\FormRuntime) {
            $renderables = $renderable->getCurrentPage()?->getRenderablesRecursively() ?? [];
        } elseif ($renderable instanceof Form\Domain\Model\Renderable\CompositeRenderableInterface) {
            $renderables = $renderable->getRenderablesRecursively();
        } else {
            // We cannot process non-composite renderables here
            return [];
        }

        foreach ($renderables as $child) {
            $childConfiguration = $context[$child->getType() . '.'];

            if (is_array($childConfiguration)) {
                $childViewModel = $this->buildFormProperties($child, $viewModel->renderingContext);
            } else {
                $childConfiguration = [];
                $childViewModel = new DataProcessing\Renderable\RenderableViewModel($viewModel->renderingContext, null);
            }

            $processedChild = $context->process($childConfiguration, $child, $childViewModel);

            if ($processedChild !== null) {
                $processedRenderables[] = $processedChild;
            }
        }

        return $processedRenderables;
    }

    private function buildFormProperties(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): DataProcessing\Renderable\RenderableViewModel {
        foreach ($this->renderableProcessors as $renderableProcessor) {
            if ($renderableProcessor->supports($renderable)) {
                return $renderableProcessor->process($renderable, $renderingContext);
            }
        }

        return new DataProcessing\Renderable\RenderableViewModel($renderingContext, null);
    }

    public static function getName(): string
    {
        return 'RENDERABLES';
    }
}
