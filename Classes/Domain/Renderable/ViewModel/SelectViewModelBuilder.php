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
 * SelectViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\FormElementInterface>
 */
final class SelectViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'MultiSelect',
        'SingleSelect',
    ];

    public function build(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ViewModel {
        $additionalAttributes = $this->renderAdditionalAttributes($renderingContext, $renderable);
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Fluid\ViewHelpers\Form\SelectViewHelper::class,
            [
                'property' => $renderable->getIdentifier(),
                'id' => $renderable->getUniqueIdentifier(),
                'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                'options' => $this->translateElementProperty($renderingContext, $renderable, 'options'),
                'multiple' => $renderable->getType() === 'MultiSelect' ? 'multiple' : null,
                'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
                'additionalAttributes' => $additionalAttributes,
                'prependOptionLabel' => $this->translateElementProperty($renderingContext, $renderable, 'prependOptionLabel'),
                'prependOptionValue' => $this->translateElementProperty($renderingContext, $renderable, 'prependOptionValue'),
            ],
        );

        foreach ($additionalAttributes as $name => $value) {
            $result->tag->addAttribute($name, $value);
        }

        return new ViewModel($renderingContext, $result->content, $result->tag);
    }
}
