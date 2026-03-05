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

namespace CPSIT\Typo3HandlebarsForms\DataProcessing\Value;

use CPSIT\Typo3HandlebarsForms\Domain;
use TYPO3\CMS\Form;

/**
 * ValueResolutionContext
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 *
 * @phpstan-type ProcessorClosure \Closure(
 *     array<string, mixed>,
 *     Form\Domain\Model\Renderable\RootRenderableInterface|null,
 *     Domain\Renderable\ViewModel\ViewModel|null,
 * ): mixed
 */
final readonly class ValueResolutionContext implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @param array<string, mixed> $configuration
     * @param ProcessorClosure|null $renderableProcessor
     */
    public function __construct(
        public array $configuration = [],
        private ?\Closure $renderableProcessor = null,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public function process(
        array $configuration = [],
        ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable = null,
        ?Domain\Renderable\ViewModel\ViewModel $viewModel = null,
    ): mixed {
        if ($this->renderableProcessor === null) {
            return null;
        }

        return ($this->renderableProcessor)($configuration, $renderable, $viewModel);
    }

    public function offsetExists(mixed $offset): bool
    {
        return \array_key_exists($offset, $this->configuration);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->configuration[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        // Not allowed.
    }

    public function offsetUnset(mixed $offset): void
    {
        // Not allowed.
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->configuration);
    }
}
