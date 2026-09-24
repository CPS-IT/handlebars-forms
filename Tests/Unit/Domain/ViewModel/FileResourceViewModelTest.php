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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\Domain\ViewModel;

use CPSIT\Typo3HandlebarsForms as Src;
use PHPUnit\Framework;
use TYPO3\CMS\Core;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * FileResourceViewModelTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\FileResourceViewModel::class)]
final class FileResourceViewModelTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;
    private Core\Resource\File $resource;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('upload', 'FileUpload');
        $this->resource = self::createStub(Core\Resource\File::class);
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesResourceAndDeleteCheckboxAsArrayValues(): void
    {
        $deleteCheckbox = Src\Domain\ViewModel\FormFieldViewModel::forLabelAndElement(
            'Delete',
            new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
        );

        $subject = new Src\Domain\ViewModel\FileResourceViewModel($this->renderable, $this->resource, $deleteCheckbox);

        $expected = [
            'resource' => $this->resource,
            'deleteCheckbox' => $deleteCheckbox,
        ];

        self::assertSame($expected, $subject->getArrayCopy());
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesNoDeleteCheckboxByDefault(): void
    {
        $subject = new Src\Domain\ViewModel\FileResourceViewModel($this->renderable, $this->resource);

        self::assertNull($subject['deleteCheckbox']);
    }

    #[Framework\Attributes\Test]
    public function getRenderableReturnsRenderable(): void
    {
        $subject = new Src\Domain\ViewModel\FileResourceViewModel($this->renderable, $this->resource);

        self::assertSame($this->renderable, $subject->getRenderable());
    }
}
