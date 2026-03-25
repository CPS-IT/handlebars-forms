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

namespace CPSIT\Typo3HandlebarsForms\ContentObject\Context;

use CPSIT\Typo3HandlebarsForms\ContentObject;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Core;

/**
 * ValueCollector
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @internal
 */
#[DependencyInjection\Attribute\Autoconfigure(shared: true)]
final class ValueCollector
{
    private const CACHE_IDENTIFIER_PREFIX = 'HandlebarsFormsValue_';

    /**
     * @todo Switch to runtiem cache once https://forge.typo3.org/issues/109220 is resolved
     *
     * @var array<string, mixed>
     */
    private static array $collection = [];

    public function save(ContentObject\AbstractHandlebarsFormsContentObject $contentObject, mixed $value): string
    {
        $identifier = Core\Utility\StringUtility::getUniqueId(
            self::CACHE_IDENTIFIER_PREFIX . spl_object_hash($contentObject),
        );

        self::$collection[$identifier] = $value;

        return $identifier;
    }

    public function load(string $identifier): mixed
    {
        if (!$this->has($identifier)) {
            return null;
        }

        return self::$collection[$identifier];
    }

    public function has(string $identifier): bool
    {
        if (!$this->isValidIdentifier($identifier)) {
            return false;
        }

        // The array_key_exists check is intentional – Null-coalescing operator MUST be avoided,
        // otherwise collected NULL values will be treated as if no value has been collected.
        // This is also the case why we don't use runtime cache at the moment, because it improperly
        // checks for the existence of a cache entry (see https://forge.typo3.org/issues/109220).
        return array_key_exists($identifier, self::$collection);
    }

    private function isValidIdentifier(string $identifier): bool
    {
        return str_starts_with($identifier, self::CACHE_IDENTIFIER_PREFIX);
    }
}
