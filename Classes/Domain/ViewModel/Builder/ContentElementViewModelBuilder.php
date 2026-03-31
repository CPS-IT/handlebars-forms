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
 * ContentElementViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\GenericFormElement>
 */
final class ContentElementViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'ContentElement',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\SimpleViewModel|Domain\ViewModel\ViewHelperContainedViewModel {
        $className = $renderable->getProperties()['elementClassAttribute'] ?? null;
        $contentElementUid = $renderable->getProperties()['contentElementUid'] ?? null;

        if (!is_numeric($contentElementUid) || (int)$contentElementUid <= 0) {
            return new Domain\ViewModel\SimpleViewModel($renderable);
        }

        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Fluid\ViewHelpers\CObjectViewHelper::class,
            [
                'data' => (int)$contentElementUid,
                'typoscriptObjectPath' => 'lib.tx_form.contentElementRendering',
            ],
        );

        if (is_string($className)) {
            $result->tag->addAttribute('class', $className);
        }

        return new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result);
    }
}
