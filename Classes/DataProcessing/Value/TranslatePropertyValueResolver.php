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

namespace CPSIT\Typo3HandlebarsForms\DataProcessing\Value;

use CPSIT\Typo3HandlebarsForms\Domain;
use CPSIT\Typo3HandlebarsForms\Fluid;
use TYPO3\CMS\Form;

/**
 * TranslatePropertyValueProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class TranslatePropertyValueResolver implements ValueResolver
{
    public function __construct(
        private Fluid\ViewHelperInvoker $viewHelperInvoker,
    ) {}

    public function resolve(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Domain\Renderable\ViewModel\ViewModel $viewModel,
        ValueResolutionContext $context = new ValueResolutionContext(),
    ): mixed {
        $property = $context['property'];
        $argumentName = $context['argumentName'];

        if (!is_string($property)) {
            return null;
        }

        if (!in_array($argumentName, ['property', 'renderingOptionProperty'], true)) {
            $argumentName = 'property';
        }

        return $this->viewHelperInvoker->translateElementProperty(
            $viewModel->renderingContext,
            $renderable,
            $property,
            $argumentName,
        );
    }

    public static function getName(): string
    {
        return 'TRANSLATE_PROPERTY';
    }
}
