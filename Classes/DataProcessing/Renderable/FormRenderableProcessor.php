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

namespace CPSIT\Typo3HandlebarsForms\DataProcessing\Renderable;

use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * FormRenderableProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractRenderableProcessor<Form\Domain\Runtime\FormRuntime>
 */
final class FormRenderableProcessor extends AbstractRenderableProcessor
{
    protected array $supportedTypes = [
        'Form',
    ];

    public function process(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        ?\Closure $viewHelperClosure = null,
    ): RenderableViewModel {
        $additionalAttributes = $this->renderAdditionalAttributes($renderingContext, $renderable);
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\FormViewHelper::class,
            [
                'object' => $renderable,
                'action' => $renderable->getRenderingOptions()['controllerAction'] ?? null,
                'method' => $renderable->getRenderingOptions()['httpMethod'] ?? null,
                'id' => $renderable->getIdentifier(),
                'section' => $renderable->getIdentifier(),
                'addQueryString' => $renderable->getRenderingOptions()['addQueryString'] ?? null,
                'argumentsToBeExcludedFromQueryString' => $renderable->getRenderingOptions()['argumentsToBeExcludedFromQueryString'] ?? null,
                'additionalParams' => $renderable->getRenderingOptions()['additionalParams'] ?? null,
                'additionalAttributes' => $additionalAttributes,
            ],
            $viewHelperClosure,
        );

        foreach ($additionalAttributes as $name => $value) {
            $result->tag->addAttribute($name, $value);
        }

        return new RenderableViewModel($renderingContext, $result->content, $result->tag);
    }
}
