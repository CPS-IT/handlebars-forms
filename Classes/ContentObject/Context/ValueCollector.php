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
final readonly class ValueCollector
{
    private const CACHE_IDENTIFIER_PREFIX = 'HandlebarsFormsValue_';

    public function __construct(
        #[DependencyInjection\Attribute\Autowire(service: 'cache.runtime')]
        private Core\Cache\Frontend\FrontendInterface $cache,
    ) {}

    public function save(ContentObject\AbstractHandlebarsFormsContentObject $contentObject, mixed $value): string
    {
        $identifier = Core\Utility\StringUtility::getUniqueId(
            self::CACHE_IDENTIFIER_PREFIX . spl_object_hash($contentObject),
        );

        $this->cache->set($identifier, $value);

        return $identifier;
    }

    public function load(string $identifier): mixed
    {
        if (!$this->isValidIdentifier($identifier)) {
            return null;
        }

        if (!$this->cache->has($identifier)) {
            return null;
        }

        return $this->cache->get($identifier);
    }

    public function has(string $identifier): bool
    {
        if (!$this->isValidIdentifier($identifier)) {
            return false;
        }

        return $this->cache->has($identifier);
    }

    private function isValidIdentifier(string $identifier): bool
    {
        return str_starts_with($identifier, self::CACHE_IDENTIFIER_PREFIX);
    }
}
