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
use TYPO3\CMS\Frontend;

/**
 * ContextAwareContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @internal
 */
#[DependencyInjection\Attribute\Exclude]
final class ContextAwareContentObject extends Frontend\ContentObject\AbstractContentObject
{
    public function __construct(
        public readonly Frontend\ContentObject\AbstractContentObject $contentObject,
        private readonly ValueCollector $valueCollector,
    ) {}

    /**
     * @param array<string|int, mixed> $conf
     */
    public function render($conf = [])
    {
        $value = $this->contentObject->render($conf);

        if (!is_string($value)) {
            return $value;
        }

        if (!$this->valueCollector->has($value)) {
            return $value;
        }

        $resolvedValue = $this->valueCollector->load($value);

        if (!is_scalar($resolvedValue) && $resolvedValue !== null) {
            return $value;
        }

        return (string)$resolvedValue;
    }
}
