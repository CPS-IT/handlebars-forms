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

use Symfony\Component\DependencyInjection;

/**
 * ChildrenContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_CHILDREN'])]
final class ChildrenContentObject extends AbstractHandlebarsFormsContentObject
{
    /**
     * @return list<mixed>|null
     */
    protected function resolve(array $configuration, Context\ValueResolutionContext $context): ?array
    {
        $children = $context->viewModel->children;

        if ($children === []) {
            return null;
        }

        $processedValue = [];

        foreach ($children as $childViewModel) {
            $processedValue[] = $context->process($configuration, viewModel: $childViewModel);
        }

        return $processedValue;
    }
}
