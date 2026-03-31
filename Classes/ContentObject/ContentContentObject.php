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
 * ContentContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_CONTENT'])]
final class ContentContentObject extends AbstractHandlebarsFormsContentObject
{
    public function resolve(array $configuration, Context\ValueResolutionContext $context): mixed
    {
        if (!($context->viewModel instanceof Domain\ViewModel\ViewHelperContainedViewModel)) {
            return null;
        }

        $content = $context->viewModel->viewHelperInvocationResult->content;

        // Strings can be considered safe, since the relevant escaping is already performed in the view helper
        if (is_string($content)) {
            $content = new Handlebars\SafeString($content);
        }

        return $content;
    }
}
