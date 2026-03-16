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

use Symfony\Component\DependencyInjection;

/**
 * ContextStack
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @internal
 */
#[DependencyInjection\Attribute\Autoconfigure(shared: true)]
final class ContextStack
{
    /**
     * @var list<ValueResolutionContext>
     */
    private array $stack = [];

    public function push(ValueResolutionContext $context): void
    {
        $this->stack[] = $context;
    }

    public function pop(): ?ValueResolutionContext
    {
        if ($this->stack === []) {
            return null;
        }

        return array_pop($this->stack);
    }

    public function current(): ?ValueResolutionContext
    {
        if ($this->stack === []) {
            return null;
        }

        return end($this->stack);
    }
}
