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

use CPSIT\Typo3HandlebarsForms\Domain;
use TYPO3\CMS\Form;

/**
 * ValueResolutionContext
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @phpstan-type ProcessorClosure \Closure(
 *     array<string, mixed>,
 *     Form\Domain\Model\Renderable\RootRenderableInterface|null,
 *     Domain\Renderable\ViewModel\ViewModel|null,
 * ): mixed
 */
final readonly class ValueResolutionContext
{
    /**
     * @param ProcessorClosure|null $renderableProcessor
     */
    public function __construct(
        public Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        public Domain\Renderable\ViewModel\ViewModel $viewModel,
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
}
