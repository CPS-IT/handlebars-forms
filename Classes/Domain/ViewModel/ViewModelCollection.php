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

namespace CPSIT\Typo3HandlebarsForms\Domain\ViewModel;

use TYPO3\CMS\Form;
use TYPO3Fluid\Fluid;

/**
 * ViewModelCollection
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends \ArrayObject<string|int, mixed>
 */
final class ViewModelCollection extends \ArrayObject implements CompositeViewModel, TagAwareViewModel
{
    /**
     * @param array<string|int, ViewModel> $viewModels
     */
    public function __construct(
        public readonly Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        public readonly array $viewModels,
    ) {
        parent::__construct($this->viewModels);
    }

    public function getRenderable(): Form\Domain\Model\Renderable\RootRenderableInterface
    {
        return $this->renderable;
    }

    public function getChildren(): array
    {
        return $this->viewModels;
    }

    public function getTag(): Fluid\Core\ViewHelper\TagBuilder
    {
        foreach ($this->viewModels as $viewModel) {
            if ($viewModel instanceof TagAwareViewModel) {
                return $viewModel->getTag();
            }
        }

        return new Fluid\Core\ViewHelper\TagBuilder();
    }
}
