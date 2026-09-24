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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\Domain\ViewModel\Builder;

use CPSIT\Typo3HandlebarsForms as Src;
use EliasHaeussler\PHPUnitAttributes;
use PHPUnit\Framework;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;

/**
 * FileUploadViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\FileUploadViewModelBuilder::class)]
final class FileUploadViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\FileUploadViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(dirname(__DIR__, 3) . '/Fixtures/Database/sys_file_storage.csv');
        $this->importCSVDataSet(dirname(__DIR__, 3) . '/Fixtures/Database/sys_file.csv');

        $this->buildFormRuntime([
            [
                'identifier' => 'upload',
                'type' => 'FileUpload',
                'label' => 'Upload',
                'properties' => [
                    'elementClassAttribute' => 'form-control',
                    'allowedMimeTypes' => ['application/pdf'],
                ],
            ],
            [
                'identifier' => 'removable-upload',
                'type' => 'FileUpload',
                'label' => 'Removable upload',
                'properties' => [
                    'allowRemoval' => true,
                ],
            ],
            [
                'identifier' => 'multiple-upload',
                'type' => 'FileUpload',
                'label' => 'Multiple upload',
                'properties' => [
                    'multiple' => true,
                    'allowRemoval' => true,
                ],
            ],
            [
                'identifier' => 'image',
                'type' => 'ImageUpload',
                'label' => 'Image',
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\FileUploadViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForFileUploadAndImageUpload(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('upload', Form\Domain\Model\FormElements\FormElementInterface::class)));
        self::assertTrue($this->subject->supports($this->getElement('image', Form\Domain\Model\FormElements\FormElementInterface::class)));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedUploadFieldIfNoResourceWasUploaded(): void
    {
        $actual = $this->buildWithinFormContext('upload');

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('input', $tag->getTagName());
        self::assertSame('file', $tag->getAttribute('type'));
        self::assertSame('test-form-upload', $tag->getAttribute('id'));
        self::assertSame('form-control', $tag->getAttribute('class'));
        self::assertSame('application/pdf', $tag->getAttribute('accept'));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsCollectionWithUploadFieldResourcePointerAndUploadedFile(): void
    {
        $fileReference = $this->createFileReference(1);

        $this->formRuntime['upload'] = $fileReference;

        $actual = $this->buildWithinFormContext('upload');

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $actual);

        $children = $actual->getChildren();

        self::assertSame(['uploadField', 'resourcePointerFields', 'uploads'], array_keys($children));

        // Upload field
        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $children['uploadField']);
        self::assertSame('file', $children['uploadField']->getTag()->getAttribute('type'));

        // Resource pointer fields
        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $children['resourcePointerFields']);
        self::assertCount(1, $children['resourcePointerFields']->getChildren());
        self::assertResourcePointerField(
            'test-form-upload-file-reference',
            1,
            $children['resourcePointerFields']->getChildren()[0],
        );

        // Uploads
        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $children['uploads']);

        $uploads = $children['uploads']->getChildren();

        self::assertCount(1, $uploads);
        self::assertInstanceOf(Src\Domain\ViewModel\FileResourceViewModel::class, $uploads[0]);
        self::assertSame($fileReference, $uploads[0]->resource);
        self::assertNull($uploads[0]->deleteCheckbox);
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~13.4.0')]
    public function buildDoesNotAddDeleteCheckboxForUploadedFileOnTypo3V13(): void
    {
        $this->formRuntime['removable-upload'] = $this->createFileReference(1);

        $uploads = $this->getUploads($this->buildWithinFormContext('removable-upload'));

        self::assertCount(1, $uploads);
        self::assertNull($uploads[0]->deleteCheckbox);
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function buildAddsDeleteCheckboxForUploadedFileIfRemovalIsAllowedOnTypo3V14(): void
    {
        $this->formRuntime['removable-upload'] = $this->createFileReference(1);

        $uploads = $this->getUploads($this->buildWithinFormContext('removable-upload'));

        self::assertCount(1, $uploads);
        self::assertDeleteCheckbox('first.pdf', 0, $uploads[0]->deleteCheckbox);
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function buildReturnsCollectionWithAllUploadedFilesForMultipleUploadOnTypo3V14(): void
    {
        $first = $this->createFileReference(1);
        $second = $this->createFileReference(2);

        $fileReferences = new Extbase\Persistence\ObjectStorage();
        $fileReferences->attach($first);
        $fileReferences->attach($second);

        $this->formRuntime['multiple-upload'] = $fileReferences;

        $actual = $this->buildWithinFormContext('multiple-upload');

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $actual);

        $children = $actual->getChildren();

        // Upload field
        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $children['uploadField']);
        self::assertSame('multiple', $children['uploadField']->getTag()->getAttribute('multiple'));

        // Resource pointer fields
        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $children['resourcePointerFields']);

        $resourcePointerFields = $children['resourcePointerFields']->getChildren();

        // Resource pointer fields are suffixed by the (1-based) position within the object storage
        self::assertCount(2, $resourcePointerFields);
        self::assertResourcePointerField('test-form-multiple-upload-file-reference-1', 1, $resourcePointerFields[0]);
        self::assertResourcePointerField('test-form-multiple-upload-file-reference-2', 2, $resourcePointerFields[1]);

        // Uploads
        $uploads = $this->getUploads($actual);

        self::assertCount(2, $uploads);
        self::assertSame($first, $uploads[0]->resource);
        self::assertDeleteCheckbox('first.pdf', 0, $uploads[0]->deleteCheckbox);
        self::assertSame($second, $uploads[1]->resource);
        self::assertDeleteCheckbox('second.pdf', 1, $uploads[1]->deleteCheckbox);
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function buildReturnsViewModelWithRenderedUploadFieldIfNoFilesWereUploadedForMultipleUploadOnTypo3V14(): void
    {
        $this->formRuntime['multiple-upload'] = new Extbase\Persistence\ObjectStorage();

        $actual = $this->buildWithinFormContext('multiple-upload');

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);
        self::assertSame('multiple', $actual->getTag()->getAttribute('multiple'));
    }

    private static function assertResourcePointerField(
        string $expectedId,
        int $expectedFileUid,
        Src\Domain\ViewModel\ViewModel $viewModel,
    ): void {
        self::assertInstanceOf(Src\Domain\ViewModel\StandaloneTagViewModel::class, $viewModel);

        $tag = $viewModel->getTag();

        self::assertSame('input', $tag->getTagName());
        self::assertSame('hidden', $tag->getAttribute('type'));
        self::assertSame($expectedId, $tag->getAttribute('id'));
        // Resource pointer of non-persisted file references is the HMAC-signed file uid
        self::assertStringStartsWith('file:' . $expectedFileUid, (string)$tag->getAttribute('value'));
    }

    private static function assertDeleteCheckbox(
        string $expectedLabel,
        int $expectedFileIndex,
        ?Src\Domain\ViewModel\FormFieldViewModel $viewModel,
    ): void {
        self::assertInstanceOf(Src\Domain\ViewModel\FormFieldViewModel::class, $viewModel);
        self::assertSame($expectedLabel, $viewModel->label->getContent());

        $tag = $viewModel->getTag();

        self::assertSame('input', $tag->getTagName());
        self::assertSame('checkbox', $tag->getAttribute('type'));
        self::assertStringEndsWith('[__deleteFile][' . $expectedFileIndex . ']', (string)$tag->getAttribute('name'));
    }

    /**
     * @return list<Src\Domain\ViewModel\FileResourceViewModel>
     */
    private function getUploads(Src\Domain\ViewModel\ViewModel $viewModel): array
    {
        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $viewModel);

        $uploads = $viewModel->getChildren()['uploads'] ?? null;

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $uploads);

        $children = array_values($uploads->getChildren());

        foreach ($children as $child) {
            self::assertInstanceOf(Src\Domain\ViewModel\FileResourceViewModel::class, $child);
        }

        /** @var list<Src\Domain\ViewModel\FileResourceViewModel> $children */
        return $children;
    }

    /**
     * Build view model within <f:form> context, since the upload field reads
     * previously uploaded resources from the bound form object (= form runtime).
     */
    private function buildWithinFormContext(string $identifier): Src\Domain\ViewModel\ViewModel
    {
        $element = $this->getElement($identifier, Form\Domain\Model\FormElements\FileUpload::class);
        $viewModel = null;

        $formViewModelBuilder = new Src\Domain\ViewModel\Builder\FormViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
        $formViewModelBuilder->build(
            $this->formRuntime,
            $this->renderingContext,
            function () use ($element, &$viewModel) {
                $viewModel = $this->subject->build($element, $this->renderingContext);

                return '';
            },
        );

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModel::class, $viewModel);

        return $viewModel;
    }

    private function createFileReference(int $fileUid): Extbase\Domain\Model\FileReference
    {
        $fileReference = new Extbase\Domain\Model\FileReference();
        $fileReference->setOriginalResource(
            $this->get(Core\Resource\ResourceFactory::class)->createFileReferenceObject([
                'uid' => 0,
                'uid_local' => $fileUid,
            ]),
        );

        return $fileReference;
    }
}
