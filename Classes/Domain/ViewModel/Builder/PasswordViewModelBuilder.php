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

namespace CPSIT\Typo3HandlebarsForms\Domain\ViewModel\Builder;

use CPSIT\Typo3HandlebarsForms\Domain;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * PasswordViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\GenericFormElement>
 */
final class PasswordViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'AdvancedPassword',
        'Password',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\ViewHelperContainedViewModel|Domain\ViewModel\ViewModelCollection {
        $passwordResult = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Fluid\ViewHelpers\Form\PasswordViewHelper::class,
            [
                'property' => $renderable->getIdentifier(),
                'id' => $renderable->getUniqueIdentifier(),
                'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
                'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
            ],
        );
        $passwordViewModel = new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $passwordResult);

        if ($renderable->getType() === 'AdvancedPassword') {
            return new Domain\ViewModel\ViewModelCollection(
                $renderable,
                [
                    'passwordField' => $passwordViewModel,
                    'confirmationField' => $this->buildConfirmationViewModel($renderable, $renderingContext),
                ],
            );
        }

        return $passwordViewModel;
    }

    private function buildConfirmationViewModel(
        Form\Domain\Model\FormElements\GenericFormElement $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\ViewHelperContainedViewModel|Domain\ViewModel\FormFieldViewModel {
        $confirmationResult = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Fluid\ViewHelpers\Form\PasswordViewHelper::class,
            [
                'property' => $renderable->getIdentifier() . '.confirmation',
                'id' => $renderable->getUniqueIdentifier() . '-confirmation',
                'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
                'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
            ],
        );
        $confirmationFieldViewModel = new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $confirmationResult);
        $labelResult = $this->viewHelperInvoker->translateElementProperty(
            $renderingContext,
            $renderable,
            ['confirmationLabel'],
        );

        if (is_string($labelResult)) {
            return Domain\ViewModel\FormFieldViewModel::forLabelAndElement($labelResult, $confirmationFieldViewModel);
        }

        return $confirmationFieldViewModel;
    }
}
