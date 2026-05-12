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

use CPSIT\Typo3HandlebarsForms\Domain;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Core;

/**
 * ChildrenContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_CHILDREN'])]
final class ChildrenContentObject extends AbstractHandlebarsFormsContentObject
{
    use CanUpdateRegister;

    private const IDENTIFIER_COUNT = 'HBS_CHILDREN_COUNT';
    private const IDENTIFIER_CURRENT = 'HBS_CHILDREN_CURRENT';

    public function __construct()
    {
        $this->typo3Version = new Core\Information\Typo3Version();
    }

    /**
     * @return list<mixed>|null
     */
    protected function resolve(array $configuration, Context\ValueResolutionContext $context): ?array
    {
        if (!($context->viewModel instanceof Domain\ViewModel\CompositeViewModel)) {
            return null;
        }

        $children = $context->viewModel->getChildren();

        if ($children === []) {
            return null;
        }

        $processedValue = [];

        // Add children count to register
        $this->updateRegister(self::IDENTIFIER_COUNT, count($children));

        foreach ($children as $index => $childViewModel) {
            // Add current child index to TSFE register
            $this->updateRegister(self::IDENTIFIER_CURRENT, count($children));

            try {
                $processedValue[] = $context->process($configuration, viewModel: $childViewModel);
            } finally {
                $this->updateRegister(self::IDENTIFIER_CURRENT);
            }
        }

        $this->updateRegister(self::IDENTIFIER_COUNT);

        return $processedValue;
    }
}
