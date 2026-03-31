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
use DevTheorem\Handlebars;
use Symfony\Component\DependencyInjection;

/**
 * TagContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_TAG'])]
final class TagContentObject extends AbstractHandlebarsFormsContentObject
{
    protected function resolve(array $configuration, Context\ValueResolutionContext $context): ?Handlebars\SafeString
    {
        if (!($context->viewModel instanceof Domain\ViewModel\TagAwareViewModel)) {
            return null;
        }

        $tag = $context->viewModel->getTag();

        if (!array_key_exists('attribute', $configuration)) {
            return $this->safeString($tag->getContent());
        }

        $attributeName = $configuration['attribute'] ?? null;

        if (!is_string($attributeName)) {
            return null;
        }

        return $this->safeString($tag->getAttribute($attributeName));
    }

    private function safeString(?string $content): ?Handlebars\SafeString
    {
        if ($content === null) {
            return null;
        }

        return new Handlebars\SafeString($content);
    }
}
