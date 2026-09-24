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

use TYPO3\CMS\Form;

/**
 * DummyBeforeRenderingHook
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
final class DummyBeforeRenderingHook
{
    public ?Form\Domain\Runtime\FormRuntime $formRuntime = null;
    public ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable = null;

    public function beforeRendering(
        Form\Domain\Runtime\FormRuntime $formRuntime,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
    ): void {
        $this->formRuntime = $formRuntime;
        $this->renderable = $renderable;
    }
}
