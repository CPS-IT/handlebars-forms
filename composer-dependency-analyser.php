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

use ShipMonk\ComposerDependencyAnalyser;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;

$configuration = new ComposerDependencyAnalyser\Config\Configuration();
$configuration
    ->addPathToScan('Classes', false)
    ->addPathToScan('Configuration', false)
    ->addPathToScan('Tests', true)
    ->ignoreUnknownClasses([
        // @todo Remove once support for TYPO3 v13 is dropped
        Form\Event\BeforeRenderableIsRenderedEvent::class,
        Frontend\ContentObject\RegisterStack::class,
    ])
;

return $configuration;
