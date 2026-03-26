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
 * HiddenViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\GenericFormElement>
 */
final class HiddenViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'Hidden',
        'Honeypot',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): ViewModel {
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Fluid\ViewHelpers\Form\HiddenViewHelper::class,
            [
                'property' => $renderable->getIdentifier(),
                'id' => $renderable->getUniqueIdentifier(),
                'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
            ],
        );

        return new ViewModel($renderingContext, $result->content, $result->tag);
    }
}
