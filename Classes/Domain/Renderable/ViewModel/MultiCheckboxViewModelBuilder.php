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

namespace CPSIT\Typo3HandlebarsForms\Domain\Renderable\ViewModel;

use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * MultiCheckboxViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\GenericFormElement>
 */
final class MultiCheckboxViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'MultiCheckbox',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ViewModel {
        $options = $renderable->getProperties()['options'] ?? null;
        $optionIndex = 0;
        $children = [];

        if (!is_array($options)) {
            $options = [];
        }

        foreach ($options as $value => $label) {
            $checkboxResult = $this->viewHelperInvoker->invoke(
                $renderingContext,
                Fluid\ViewHelpers\Form\CheckboxViewHelper::class,
                [
                    'property' => $renderable->getIdentifier(),
                    'multiple' => true,
                    'id' => $renderable->getUniqueIdentifier() . '-' . $optionIndex++,
                    'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                    'value' => $value,
                    'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
                    'additionalAttributes' => $this->renderAdditionalAttributes($renderingContext, $renderable),
                ],
            );

            $labelResult = $this->viewHelperInvoker->translateElementProperty(
                $renderingContext,
                $renderable,
                ['options', $value],
            );

            // @todo Check if this can be done in a better way
            $checkboxResult->tag->addAttribute('label', $labelResult);

            $children[] = new ViewModel($renderingContext, $checkboxResult->content, $checkboxResult->tag);
        }

        return new ViewModel($renderingContext, children: $children);
    }
}
