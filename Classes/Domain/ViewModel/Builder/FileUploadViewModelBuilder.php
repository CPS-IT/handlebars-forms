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
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;

/**
 * FileUploadViewModelBuilder
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends AbstractViewModelBuilder<Form\Domain\Model\FormElements\FileUpload>
 */
final class FileUploadViewModelBuilder extends AbstractViewModelBuilder
{
    protected array $supportedTypes = [
        'FileUpload',
    ];

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\ViewModelCollection|Domain\ViewModel\ViewHelperContainedViewModel {
        $resource = null;
        $resourceVariableName = 'resource';
        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\Form\UploadedResourceViewHelper::class,
            [
                'property' => $renderable->getIdentifier(),
                'as' => $resourceVariableName,
                'id' => $renderable->getUniqueIdentifier(),
                'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
                'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
                'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
                'accept' => $renderable->getProperties()['allowedMimeTypes'] ?? null,
            ],
            static function () use ($renderingContext, &$resource, $resourceVariableName) {
                $resource = $renderingContext->getVariableProvider()->get($resourceVariableName);
            },
        );
        $inputViewModel = new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result);

        if (!($resource instanceof Extbase\Domain\Model\FileReference)) {
            return $inputViewModel;
        }

        $hiddenFields = $result->extractChildNodes('input[@type="hidden"]');
        $viewModels = [
            'uploadField' => $inputViewModel,
            'resource' => new Domain\ViewModel\FileResourceViewModel($renderable, $resource),
        ];

        if ($hiddenFields !== []) {
            $viewModels['resourcePointerField'] = new Domain\ViewModel\StandaloneTagViewModel($renderable, $hiddenFields[0]);
        }

        return new Domain\ViewModel\ViewModelCollection($renderable, $viewModels);
    }
}
