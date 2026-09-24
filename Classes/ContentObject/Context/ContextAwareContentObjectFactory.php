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

use Psr\Http\Message;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Frontend;

/**
 * ContextAwareContentObjectFactory
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @internal
 */
#[DependencyInjection\Attribute\AsDecorator(Frontend\ContentObject\ContentObjectFactory::class)]
final class ContextAwareContentObjectFactory extends Frontend\ContentObject\ContentObjectFactory
{
    public function __construct(
        #[DependencyInjection\Attribute\AutowireDecorated]
        private readonly Frontend\ContentObject\ContentObjectFactory $inner,
        private readonly ValueCollector $valueCollector,
    ) {
        // Missing constructor call is intended.
    }

    public function getContentObject(
        string $name,
        Message\ServerRequestInterface $request,
        Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer,
    ): ?Frontend\ContentObject\AbstractContentObject {
        $contentObject = $this->inner->getContentObject($name, $request, $contentObjectRenderer);

        if ($contentObject === null) {
            return null;
        }

        return new ContextAwareContentObject($contentObject, $this->valueCollector);
    }
}
