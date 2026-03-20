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

namespace CPSIT\Typo3HandlebarsForms\ContentObject;

use Psr\Log;
use TYPO3\CMS\Frontend;

/**
 * AbstractHandlebarsFormsContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
abstract class AbstractHandlebarsFormsContentObject extends Frontend\ContentObject\AbstractContentObject implements Log\LoggerAwareInterface
{
    use Log\LoggerAwareTrait;

    private Context\ContextStack $contextStack;
    private Context\ValueCollector $valueCollector;

    public function injectContextStack(Context\ContextStack $contextStack): void
    {
        $this->contextStack = $contextStack;
    }

    public function injectValueCollector(Context\ValueCollector $valueCollector): void
    {
        $this->valueCollector = $valueCollector;
    }

    /**
     * @param array<string, mixed> $conf
     */
    final public function render($conf = []): string
    {
        $context = $this->contextStack->current();

        if ($context === null) {
            $this->logger?->warning(
                'Using a HBS_* content object in other contexts than "process-form" data processor is not supported.',
            );

            return '';
        }

        $value = $this->resolve($conf, $context);

        if (is_string($value) || $value === null) {
            return $this->applyStdWrap((string)$value, $conf);
        }

        return $this->valueCollector->save($this, $value);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    abstract protected function resolve(array $configuration, Context\ValueResolutionContext $context): mixed;

    /**
     * @param array<string, mixed> $configuration
     */
    private function applyStdWrap(string $value, array $configuration): string
    {
        if (!is_array($configuration['stdWrap.'] ?? null)) {
            return $value;
        }

        return $this->cObj?->stdWrap($value, $configuration['stdWrap.']) ?? $value;
    }
}
