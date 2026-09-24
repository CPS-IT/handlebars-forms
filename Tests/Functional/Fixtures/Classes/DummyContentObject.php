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

/**
 * DummyContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
final class DummyContentObject extends ContentObject\AbstractHandlebarsFormsContentObject
{
    /**
     * @param \Closure(array<string|int, mixed>, ContentObject\Context\ValueResolutionContext): mixed $resolver
     */
    public function __construct(
        private readonly \Closure $resolver,
    ) {}

    /**
     * @param array<string|int, mixed> $configuration
     */
    public function callProcessGenericValue(
        string $value,
        array $configuration,
        ContentObject\Context\ValueResolutionContext $context,
    ): mixed {
        return $this->processGenericValue($value, $configuration, $context);
    }

    protected function resolve(array $configuration, ContentObject\Context\ValueResolutionContext $context): mixed
    {
        return ($this->resolver)($configuration, $context);
    }
}
