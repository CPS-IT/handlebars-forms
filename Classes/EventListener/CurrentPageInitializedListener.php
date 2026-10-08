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

namespace CPSIT\Typo3HandlebarsForms\EventListener;

use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * CurrentPageInitializedListener
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class CurrentPageInitializedListener
{
    #[Core\Attribute\AsEventListener('cpsit/typo3-handlebars-forms/after-current-page-is-resolved')]
    public function __invoke(Form\Event\AfterCurrentPageIsResolvedEvent $event): void
    {
        $event->currentPage = $this->afterInitializeCurrentPage($event->formRuntime, $event->currentPage);
    }

    /**
     * @todo Move to invoke method once support for TYPO3 v13 is dropped
     */
    public function afterInitializeCurrentPage(
        Form\Domain\Runtime\FormRuntime $formRuntime,
        ?Form\Domain\Model\FormElements\Page $currentPage,
    ): ?Form\Domain\Model\FormElements\Page {
        $allowFastForwardSubmit = (bool)($formRuntime->getFormDefinition()->getRenderingOptions()['allowFastForwardSubmit'] ?? false);

        if ($currentPage === null || !$allowFastForwardSubmit) {
            return $currentPage;
        }

        /** @var Extbase\Mvc\ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = $formRuntime->getRequest()->getAttribute('extbase');
        $currentPageFromRequest = $extbaseRequestParameters->getInternalArgument('__currentPage');

        if ($currentPageFromRequest === count($formRuntime->getPages())) {
            return null;
        }

        return $currentPage;
    }
}
