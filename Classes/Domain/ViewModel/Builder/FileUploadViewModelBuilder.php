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
use CPSIT\Typo3HandlebarsForms\Fluid\ViewHelperInvoker;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3Fluid\Fluid as FluidStandalone;

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
        'ImageUpload',
    ];

    private readonly Core\Information\Typo3Version $typo3Version;

    public function __construct(ViewHelperInvoker $viewHelperInvoker)
    {
        parent::__construct($viewHelperInvoker);

        $this->typo3Version = new Core\Information\Typo3Version();
    }

    public function renderRenderable(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
    ): Domain\ViewModel\ViewModelCollection|Domain\ViewModel\ViewHelperContainedViewModel {
        $resource = null;
        $resourceVariableName = 'resource';
        $arguments = [
            'property' => $renderable->getIdentifier(),
            'as' => $resourceVariableName,
            'id' => $renderable->getUniqueIdentifier(),
            'class' => $renderable->getProperties()['elementClassAttribute'] ?? null,
            'errorClass' => $renderable->getProperties()['elementErrorClassAttribute'] ?? null,
            'additionalAttributes' => $this->renderAdditionalAttributes($renderable, $renderingContext),
            'accept' => $renderable->getProperties()['allowedMimeTypes'] ?? null,
        ];

        // @todo Remove condition once support for TYPO3 v13 is dropped
        if ($this->typo3Version->getMajorVersion() >= 14) {
            $arguments['multiple'] = $renderable->getProperties()['multiple'] ?? false;
        }

        $result = $this->viewHelperInvoker->invoke(
            $renderingContext,
            Form\ViewHelpers\Form\UploadedResourceViewHelper::class,
            $arguments,
            static function () use ($renderingContext, &$resource, $resourceVariableName) {
                $resource = $renderingContext->getVariableProvider()->get($resourceVariableName);
            },
        );
        $inputViewModel = new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result);

        if (!$this->isValidResource($resource)) {
            return $inputViewModel;
        }

        $hiddenFields = $result->extractChildNodes('input[@type="hidden"]');
        $viewModels = [
            'uploadField' => $inputViewModel,
            'resource' => new Domain\ViewModel\FileResourceViewModel($renderable, $resource),
        ];

        if ($hiddenFields !== []) {
            $viewModels['resourcePointerFields'] = $this->buildResourcePointerFields($renderable, $hiddenFields);
        }

        // @todo Remove first condition once support for TYPO3 v13 is dropped
        if ($this->typo3Version->getMajorVersion() >= 14 && (bool)($renderable->getProperties()['allowRemoval'] ?? false)) {
            $viewModels['deleteCheckboxes'] = $this->buildDeleteCheckboxes($renderable, $renderingContext, $resource);
        }

        return new Domain\ViewModel\ViewModelCollection($renderable, $viewModels);
    }

    /**
     * @param list<FluidStandalone\Core\ViewHelper\TagBuilder> $hiddenFields
     */
    private function buildResourcePointerFields(
        Form\Domain\Model\FormElements\FileUpload $renderable,
        array $hiddenFields,
    ): Domain\ViewModel\ViewModelCollection {
        $resourcePointerFields = [];

        foreach ($hiddenFields as $hiddenField) {
            $resourcePointerFields[] = new Domain\ViewModel\StandaloneTagViewModel($renderable, $hiddenField);
        }

        return new Domain\ViewModel\ViewModelCollection($renderable, $resourcePointerFields);
    }

    /**
     * @param Extbase\Domain\Model\FileReference|Extbase\Persistence\ObjectStorage<Extbase\Domain\Model\FileReference> $resource
     */
    private function buildDeleteCheckboxes(
        Form\Domain\Model\FormElements\FileUpload $renderable,
        Fluid\Core\Rendering\RenderingContext $renderingContext,
        Extbase\Domain\Model\FileReference|Extbase\Persistence\ObjectStorage $resource,
    ): Domain\ViewModel\ViewModelCollection {
        if (!is_iterable($resource)) {
            $resource = [$resource];
        }

        $viewModels = [];
        $index = 0;

        // Map file references to view models
        foreach ($resource as $fileReference) {
            $result = $this->viewHelperInvoker->invoke(
                $renderingContext,
                Form\ViewHelpers\Form\UploadDeleteCheckboxViewHelper::class,
                [
                    'property' => $renderable->getIdentifier(),
                    'fileReference' => $fileReference,
                    'fileIndex' => $index++,
                ],
            );

            $viewModels[] = Domain\ViewModel\FormFieldViewModel::forLabelAndElement(
                $fileReference->getOriginalResource()->getOriginalFile()->getName(),
                new Domain\ViewModel\ViewHelperContainedViewModel($renderable, $result),
            );
        }

        return new Domain\ViewModel\ViewModelCollection($renderable, $viewModels);
    }

    /**
     * @phpstan-assert-if-true Extbase\Domain\Model\FileReference|Extbase\Persistence\ObjectStorage<Extbase\Domain\Model\FileReference> $resource
     */
    private function isValidResource(mixed $resource): bool
    {
        if ($resource instanceof Extbase\Domain\Model\FileReference) {
            return true;
        }

        // @todo Combine with previous condition once support for TYPO3 v13 is dropped
        if ($this->typo3Version->getMajorVersion() >= 14
            && $resource instanceof Extbase\Persistence\ObjectStorage
            && count($resource) > 0
        ) {
            return true;
        }

        return false;
    }
}
