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
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * StaticTextViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\GenericFormElement>
 */
final class StaticTextViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'StaticText',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\StandaloneTagViewModel|Domain\ViewModel\FormFieldViewModel {
        $label = $this->viewHelperInvoker->translateElementProperty($renderingContext, $renderable, 'label');
        $text = $this->viewHelperInvoker->translateElementProperty($renderingContext, $renderable, 'text');
        $className = $renderable->getProperties()['elementClassAttribute'] ?? null;

        if (is_string($text)) {
            $text = nl2br($text);
        } elseif ($text !== null) {
            $text = null;
        }

        $tag = new FluidStandalone\Core\ViewHelper\TagBuilder('p', $text);
        $textViewModel = new Domain\ViewModel\StandaloneTagViewModel($renderable, $tag);

        if (is_string($className)) {
            $tag->addAttribute('class', $className);
        }

        if (!is_string($label)) {
            return $textViewModel;
        }

        return Domain\ViewModel\FormFieldViewModel::forLabelAndElement($label, $textViewModel);
    }
}
