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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\Fixtures\Classes;

use CPSIT\Typo3HandlebarsForms\Domain;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * DummyViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 *
 * @extends Domain\ViewModel\Builder\AbstractViewModelBuilder<Form\Domain\Model\Renderable\RootRenderableInterface>
 */
final class DummyViewModelBuilder extends Domain\ViewModel\Builder\AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'Text',
    ];

    public ?Domain\ViewModel\ViewModel $viewModel = null;

    /**
     * @return array<string|int, mixed>
     */
    public function callRenderAdditionalAttributes(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): array {
        return $this->renderAdditionalAttributes($renderable, $renderingContext);
    }

    protected function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ?Domain\ViewModel\ViewModel {
        return $this->viewModel ?? parent::renderRenderable($renderable, $renderingContext);
    }
}
