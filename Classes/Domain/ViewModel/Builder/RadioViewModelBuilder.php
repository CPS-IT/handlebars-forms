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
 * RadioViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\GenericFormElement>
 */
final class RadioViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'RadioButton',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\ViewModelCollection {
        $options = $renderable->getProperties()['options'] ?? null;
        $optionIndex = 0;
        $children = [];

        if (!is_array($options)) {
            $options = [];
        }

        foreach ($options as $value => $label) {
            $radioResult = $this->viewHelperInvoker->invoke(
                $renderingContext,
                Fluid\ViewHelpers\Form\RadioViewHelper::class,
                [
                    'property' => $renderable->getIdentifier(),
                    'id' => $renderable->getUniqueIdentifier() . '-' . $optionIndex++,
                    'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                    'value' => $value,
                    'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
                    'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
                ],
            );

            $radioViewModel = new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $radioResult);
            $labelResult = $this->viewHelperInvoker->translateElementProperty(
                $renderingContext,
                $renderable,
                ['options', $value],
            );

            if (is_string($labelResult)) {
                $children[] = Domain\ViewModel\FormFieldViewModel::forLabelAndElement($labelResult, $radioViewModel);
            } else {
                $children[] = $radioViewModel;
            }
        }

        return new Domain\ViewModel\ViewModelCollection($renderable, $children);
    }
}
