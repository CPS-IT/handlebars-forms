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

namespace CPSIT\Typo3HandlebarsForms\Utility;

use DevTheorem\Handlebars;

/**
 * StringUtility
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class StringUtility
{
    /**
     * @phpstan-assert-if-true string|Handlebars\SafeString|null $value
     */
    public static function isStringable(mixed $value): bool
    {
        return is_string($value) || $value === null || $value instanceof Handlebars\SafeString;
    }

    /**
     * @param \Closure(string): mixed $processor
     */
    public static function processStringable(string|Handlebars\SafeString|null $value, \Closure $processor): mixed
    {
        $processedValue = $processor((string)$value);

        if ($value instanceof Handlebars\SafeString && is_scalar($processedValue)) {
            return new Handlebars\SafeString((string)$processedValue);
        }

        return $processedValue;
    }
}
