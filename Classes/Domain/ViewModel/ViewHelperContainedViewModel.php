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

use CPSIT\Typo3HandlebarsForms\Fluid;
use TYPO3\CMS\Form;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewHelperContainedViewModel
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends \ArrayObject<string|int, mixed>
 */
final class ViewHelperContainedViewModel extends \ArrayObject implements CompositeViewModel, TagAwareViewModel
{
    /**
     * @param list<ViewModel> $children
     */
    public function __construct(
        public Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        public Fluid\ViewHelperInvocationResult $viewHelperInvocationResult,
        public array $children = [],
    ) {
        parent::__construct($this->viewHelperInvocationResult->tag->getAttributes());
    }

    public function getRenderable(): Form\Domain\Model\Renderable\RootRenderableInterface
    {
        return $this->renderable;
    }

    public function getChildren(): array
    {
        return $this->children;
    }

    public function getTag(): FluidStandalone\Core\ViewHelper\TagBuilder
    {
        return $this->viewHelperInvocationResult->tag;
    }
}
