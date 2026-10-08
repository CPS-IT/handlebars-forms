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

namespace CPSIT\Typo3HandlebarsForms\Domain\Runtime;

use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * Accessible form runtime (not final by intent, since the base class is not final as well).
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
#[DependencyInjection\Attribute\AsAlias(id: Form\Domain\Runtime\FormRuntime::class, public: true)]
#[DependencyInjection\Attribute\Autoconfigure(public: true, shared: false)]
class AccessibleFormRuntime extends Form\Domain\Runtime\FormRuntime
{
    public function validatePage(Form\Domain\Model\FormElements\Page $page): Extbase\Error\Result
    {
        return $this->mapAndValidatePage($page);
    }
}
