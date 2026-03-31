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

use CPSIT\Typo3HandlebarsForms\Fluid;
use Symfony\Component\DependencyInjection;

/**
 * TranslatePropertyContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_TRANSLATE_PROPERTY'])]
final class TranslatePropertyContentObject extends AbstractHandlebarsFormsContentObject
{
    public function __construct(
        private readonly Fluid\ViewHelperInvoker $viewHelperInvoker,
    ) {}

    protected function resolve(array $configuration, Context\ValueResolutionContext $context): mixed
    {
        $property = $configuration['property'] ?? null;
        $argumentName = $configuration['argumentName'] ?? null;

        if (!is_string($property)) {
            return null;
        }

        if (!in_array($argumentName, ['property', 'renderingOptionProperty'], true)) {
            $argumentName = 'property';
        }

        return $this->viewHelperInvoker->translateElementProperty(
            $context->renderingContext,
            $context->renderable,
            $property,
            $argumentName,
        );
    }
}
