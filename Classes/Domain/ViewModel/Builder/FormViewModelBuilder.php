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
 * FormViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Runtime\FormRuntime>
 */
final class FormViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'Form',
    ];

    public function build(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        ?\Closure $viewHelperClosure = null,
    ): Domain\ViewModel\ViewHelperContainedViewModel {
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\FormViewHelper::class,
            [
                'object' => $renderable,
                'action' => $renderable->getRenderingOptions()['controllerAction'] ?? null,
                'method' => $renderable->getRenderingOptions()['httpMethod'] ?? null,
                'id' => $renderable->getIdentifier(),
                'enctype' => $renderable->getRenderingOptions()['httpEnctype'] ?? null,
                'section' => $renderable->getIdentifier(),
                'addQueryString' => $renderable->getRenderingOptions()['addQueryString'] ?? null,
                'argumentsToBeExcludedFromQueryString' => $renderable->getRenderingOptions()['argumentsToBeExcludedFromQueryString'] ?? null,
                'additionalParams' => $renderable->getRenderingOptions()['additionalParams'] ?? null,
                'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
            ],
            $viewHelperClosure,
        );

        return new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result);
    }
}
