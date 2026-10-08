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

use CPSIT\Typo3HandlebarsForms\EventListener;
use TYPO3\CMS\Core;

// @todo Remove once support for TYPO3 v13 is dropped
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['afterInitializeCurrentPage'][1791449126] = EventListener\CurrentPageInitializedListener::class;

// @todo Remove once support for TYPO3 v13 is dropped
if ((new Core\Information\Typo3Version())->getMajorVersion() < 14) {
    Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('
        module.tx_form {
            settings {
                yamlConfigurations {
                    1791451248 = EXT:handlebars_forms/Configuration/Yaml/FormSetup.yaml
                }
            }
        }
    ');
}
