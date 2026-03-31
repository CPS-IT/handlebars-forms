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

use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * FileResourceViewModel
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends \ArrayObject<string|int, mixed>
 */
final class FileResourceViewModel extends \ArrayObject implements ViewModel
{
    public function __construct(
        public readonly Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        public readonly Core\Resource\File|Core\Resource\FileReference|Extbase\Domain\Model\File|Extbase\Domain\Model\FileReference $resource,
    ) {
        parent::__construct(['resource' => $this->resource]);
    }

    public function getRenderable(): Form\Domain\Model\Renderable\RootRenderableInterface
    {
        return $this->renderable;
    }
}
