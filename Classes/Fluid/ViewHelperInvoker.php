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

namespace CPSIT\Typo3HandlebarsForms\Fluid;

use TYPO3\CMS\Fluid;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewHelperInvoker
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class ViewHelperInvoker
{
    /**
     * @param array<string, mixed> $arguments
     * @param (\Closure(FluidStandalone\Core\ViewHelper\TagBuilder): mixed)|null $closure
     */
    public function invoke(
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        string $viewHelperClassName,
        array $arguments = [],
        ?\Closure $closure = null,
    ): ViewHelperInvocationResult {
        $tag = new FluidStandalone\Core\ViewHelper\TagBuilder();
        $viewHelper = $renderingContext->getViewHelperResolver()->createViewHelperInstanceFromClassName($viewHelperClassName);

        if ($viewHelper instanceof FluidStandalone\Core\ViewHelper\AbstractTagBasedViewHelper) {
            $viewHelper->setTagBuilder($tag);
        }

        $content = $renderingContext->getViewHelperInvoker()->invoke(
            $viewHelper,
            $arguments,
            $renderingContext,
            static fn() => $closure ? $closure($tag) : '',
        );

        return new ViewHelperInvocationResult($viewHelper, $renderingContext, $content, $tag);
    }
}
