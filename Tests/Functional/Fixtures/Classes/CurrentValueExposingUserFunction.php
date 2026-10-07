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

use TYPO3\CMS\Core;
use TYPO3\CMS\Frontend;

/**
 * CurrentValueExposingUserFunction
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
final class CurrentValueExposingUserFunction
{
    private static mixed $currentValue = null;

    private ?Frontend\ContentObject\ContentObjectRenderer $cObj = null;

    public static function getCurrentValue(): mixed
    {
        return self::$currentValue;
    }

    public static function reset(): void
    {
        self::$currentValue = null;
    }

    public function setContentObjectRenderer(Frontend\ContentObject\ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    #[Core\Attribute\AsAllowedCallable]
    public function expose(string $content): string
    {
        self::$currentValue = $this->cObj?->getCurrentVal();

        return $content;
    }
}
