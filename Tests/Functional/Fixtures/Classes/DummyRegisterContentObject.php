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

use CPSIT\Typo3HandlebarsForms\ContentObject;
use TYPO3\CMS\Core;
use TYPO3\CMS\Frontend;

/**
 * DummyRegisterContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
final class DummyRegisterContentObject extends Frontend\ContentObject\AbstractContentObject
{
    use ContentObject\CanUpdateRegister;

    public function __construct(Core\Information\Typo3Version $typo3Version)
    {
        $this->typo3Version = $typo3Version;
    }

    public function callUpdateRegister(string $key, ?int $value = null): void
    {
        $this->updateRegister($key, $value);
    }

    /**
     * @param array<string|int, mixed> $conf
     */
    public function render($conf = []): string
    {
        return '';
    }
}
