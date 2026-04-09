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

use CPSIT\Typo3HandlebarsForms\Utility;
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
     * @param array<string|int, mixed> $conf
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

        // Resolve value and apply stdWrap (either on stringable value or by using value as currentValue in COR)
        $resolvedValue = $this->resolve($conf, $context);
        $value = $this->applyStdWrap($resolvedValue, $conf);

        // Strings can be returned without additional caching using ValueCollector,
        // since content objects are already designed to return string values
        if (is_string($value)) {
            return $value;
        }

        return $this->valueCollector->save($this, $value);
    }

    /**
     * @param array<string|int, mixed> $configuration
     */
    abstract protected function resolve(array $configuration, Context\ValueResolutionContext $context): mixed;

    /**
     * @param array<string|int, mixed> $configuration
     */
    private function applyStdWrap(mixed $value, array $configuration): mixed
    {
        if ($this->cObj === null || !is_array($configuration['stdWrap.'] ?? null)) {
            return $value;
        }

        $apply = fn(string $string) => $this->cObj->stdWrap($string, $configuration['stdWrap.']) ?? $value;

        // Backup and override current value
        $currentValue = $this->cObj->getCurrentVal();
        $this->cObj->setCurrentVal($this->convertValueToStringableValue($value));

        try {
            // Apply stdWrap directly on stringable value
            if (Utility\StringUtility::isStringable($value)) {
                return Utility\StringUtility::processStringable($value, $apply(...));
            }

            // Apply stdWrap on empty string, but give consumers the chance to perform actions
            // based on the current value (which reflects the non-stringable resolved value)
            return $apply('');
        } finally {
            // Restore previous current value
            $this->cObj?->setCurrentVal($currentValue);
        }
    }

    /**
     * Build stringable value for usage as current value in COR.
     * Passes through stringables and tries to convert scalar-arrays to a string list.
     */
    private function convertValueToStringableValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (Utility\StringUtility::isStringable($value)) {
            return (string)$value;
        }

        if (is_array($value)) {
            return implode(',', array_filter($value, is_scalar(...)));
        }

        return null;
    }
}
