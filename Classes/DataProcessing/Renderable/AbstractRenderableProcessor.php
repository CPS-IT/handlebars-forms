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

use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * AbstractRenderableProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @template T of Form\Domain\Model\Renderable\RootRenderableInterface
 * @implements RenderableProcessor<T>
 */
abstract class AbstractRenderableProcessor implements RenderableProcessor
{
    /**
     * @var list<non-empty-string>
     */
    protected array $supportedTypes = [];

    public function __construct(
        protected readonly ViewHelperInvoker $viewHelperInvoker,
    ) {}

    public function supports(Form\Domain\Model\Renderable\RootRenderableInterface $renderable): bool
    {
        return in_array($renderable->getType(), $this->supportedTypes, true);
    }

    /**
     * @param T $renderable
     * @return array<string, mixed>
     */
    protected function renderAdditionalAttributes(
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
    ): array {
        $content = $this->translateElementProperty(
            $renderingContext,
            $renderable,
            'fluidAdditionalAttributes',
        );

        if (!is_array($content)) {
            return [];
        }

        return $content;
    }

    protected function translateElementProperty(
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        string $property,
    ): mixed {
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\TranslateElementPropertyViewHelper::class,
            [
                'element' => $renderable,
                'property' => $property,
            ],
        );

        return $result->content;
    }
}
