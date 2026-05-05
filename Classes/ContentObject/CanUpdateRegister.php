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

use Psr\Http\Message;
use TYPO3\CMS\Core;
use TYPO3\CMS\Frontend;

/**
 * CanUpdateRegister
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 *
 * @property Message\ServerRequestInterface $request
 */
trait CanUpdateRegister
{
    protected Core\Information\Typo3Version $typo3Version;

    protected function updateRegister(string $key, ?int $value = null): void
    {
        match ($this->typo3Version->getMajorVersion()) {
            13 => $this->modifyRegisterUsingTsfe($key, $value),
            14 => $this->modifyRegisterUsingRequest($key, $value),
            default => null,
        };
    }

    /**
     * @todo Remove once support for TYPO3 v13 is dropped
     */
    protected function modifyRegisterUsingTsfe(string $key, ?int $value): void
    {
        $tsfe = $this->getTypoScriptFrontendController();

        if ($value === null) {
            unset($tsfe->register[$key]);
        } else {
            $tsfe->register[$key] = $value;
        }
    }

    protected function modifyRegisterUsingRequest(string $key, ?int $value): void
    {
        $stack = $this->request->getAttribute('frontend.register.stack');

        if (!($stack instanceof Frontend\ContentObject\RegisterStack)) {
            return;
        }

        if ($value !== null) {
            $stack->current()->set($key, $value);
        }
    }
}
