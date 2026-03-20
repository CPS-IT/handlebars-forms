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
use Psr\Http\Message;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * TranslateErrorContentObject
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('frontend.contentobject', ['identifier' => 'HBS_TRANSLATE_ERROR'])]
final class TranslateErrorContentObject extends AbstractHandlebarsFormsContentObject
{
    public function __construct(
        private readonly Fluid\ViewHelperInvoker $viewHelperInvoker,
    ) {}

    protected function resolve(array $configuration, Context\ValueResolutionContext $context): mixed
    {
        $errorCode = $configuration['errorCode'] ?? null;

        if (!is_numeric($errorCode)) {
            return null;
        }

        return $this->viewHelperInvoker->invoke(
            $context->viewModel->renderingContext,
            Form\ViewHelpers\TranslateElementErrorViewHelper::class,
            [
                'element' => $context->renderable,
                'error' => $this->buildError($context, (int)$errorCode),
            ],
        )->content;
    }

    private function buildError(Context\ValueResolutionContext $context, int $errorCode): Extbase\Error\Error
    {
        $error = new Extbase\Error\Error('', $errorCode);
        $renderable = $context->renderable;

        // Resolve property path from renderable
        if ($renderable instanceof Form\Domain\Model\Renderable\AbstractRenderable) {
            $property = $renderable->getRootForm()->getIdentifier() . '.' . $renderable->getIdentifier();
        } else {
            $property = $renderable->getIdentifier();
        }

        $request = $context->viewModel->renderingContext->getAttribute(Message\ServerRequestInterface::class);
        $extbaseRequestParameters = $request->getAttribute('extbase');

        // Early return when content object was requested outside of extbase context
        if (!($extbaseRequestParameters instanceof Extbase\Mvc\ExtbaseRequestParameters)) {
            return $error;
        }

        $validationResults = $extbaseRequestParameters->getOriginalRequestMappingResults()->forProperty($property);

        foreach ($validationResults->getErrors() as $validationError) {
            if ($validationError->getCode() === $errorCode) {
                return $validationError;
            }
        }

        return $error;
    }
}
