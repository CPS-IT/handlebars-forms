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
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * FormValueViewModelTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\FormValueViewModel::class)]
final class FormValueViewModelTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $element;

    public function setUp(): void
    {
        parent::setUp();

        $this->element = new Form\Domain\Model\FormElements\GenericFormElement('colors', 'MultiCheckbox');
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesFormValueAsArrayValues(): void
    {
        $subject = new Src\Domain\ViewModel\FormValueViewModel($this->element, ['red'], ['Red'], true, false);

        $expected = [
            'value' => ['red'],
            'processedValue' => ['Red'],
            'isMultiValue' => true,
            'isSection' => false,
        ];

        self::assertSame($expected, $subject->getArrayCopy());
    }

    #[Framework\Attributes\Test]
    public function fromArrayThrowsExceptionIfElementIsMissing(): void
    {
        $this->expectExceptionObject(new Src\Exception\FormValueContextIsInvalid());

        Src\Domain\ViewModel\FormValueViewModel::fromArray(['value' => 'foo']);
    }

    #[Framework\Attributes\Test]
    public function fromArrayThrowsExceptionIfElementIsInvalid(): void
    {
        $this->expectExceptionObject(new Src\Exception\FormValueContextIsInvalid());

        Src\Domain\ViewModel\FormValueViewModel::fromArray(['element' => 'foo']);
    }

    #[Framework\Attributes\Test]
    public function fromArrayReturnsViewModelWithDefaultsForMissingValues(): void
    {
        $actual = Src\Domain\ViewModel\FormValueViewModel::fromArray(['element' => $this->element]);

        self::assertSame($this->element, $actual->element);
        self::assertNull($actual->value);
        self::assertNull($actual->processedValue);
        self::assertFalse($actual->isMultiValue);
        self::assertFalse($actual->isSection);
    }

    #[Framework\Attributes\Test]
    public function fromArrayReturnsViewModelFromGivenContext(): void
    {
        $actual = Src\Domain\ViewModel\FormValueViewModel::fromArray([
            'element' => $this->element,
            'value' => ['red'],
            'processedValue' => ['Red'],
            'isMultiValue' => true,
            'isSection' => true,
        ]);

        self::assertSame(['red'], $actual->value);
        self::assertSame(['Red'], $actual->processedValue);
        self::assertTrue($actual->isMultiValue);
        self::assertTrue($actual->isSection);
    }

    /**
     * @return \Generator<string, array{mixed, bool}>
     */
    public static function fromArrayNormalizesBooleanFlagsDataProvider(): \Generator
    {
        yield 'truthy scalar' => ['1', true];
        yield 'falsy scalar' => [0, false];
        yield 'non-scalar' => [['foo'], false];
        yield 'null' => [null, false];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('fromArrayNormalizesBooleanFlagsDataProvider')]
    public function fromArrayNormalizesBooleanFlags(mixed $flag, bool $expected): void
    {
        $actual = Src\Domain\ViewModel\FormValueViewModel::fromArray([
            'element' => $this->element,
            'isMultiValue' => $flag,
            'isSection' => $flag,
        ]);

        self::assertSame($expected, $actual->isMultiValue);
        self::assertSame($expected, $actual->isSection);
    }

    #[Framework\Attributes\Test]
    public function getRenderableReturnsElement(): void
    {
        $subject = new Src\Domain\ViewModel\FormValueViewModel($this->element, 'foo');

        self::assertSame($this->element, $subject->getRenderable());
    }

    /**
     * @return \Generator<string, array{mixed, mixed}>
     */
    public static function getChildrenReturnsEmptyListIfValueOrProcessedValueIsNoArrayDataProvider(): \Generator
    {
        yield 'no array value' => ['red', ['Red']];
        yield 'no array processed value' => [['red'], 'Red'];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('getChildrenReturnsEmptyListIfValueOrProcessedValueIsNoArrayDataProvider')]
    public function getChildrenReturnsEmptyListIfValueOrProcessedValueIsNoArray(mixed $value, mixed $processedValue): void
    {
        $subject = new Src\Domain\ViewModel\FormValueViewModel($this->element, $value, $processedValue);

        self::assertSame([], $subject->getChildren());
    }

    #[Framework\Attributes\Test]
    public function getChildrenReturnsViewModelForEachProcessedValue(): void
    {
        $subject = new Src\Domain\ViewModel\FormValueViewModel(
            $this->element,
            ['first' => 'red', 'second' => 'blue'],
            ['first' => 'Red', 'second' => 'Blue', 'third' => 'Green'],
            true,
        );

        $expected = [
            'first' => new Src\Domain\ViewModel\FormValueViewModel($this->element, 'red', 'Red'),
            'second' => new Src\Domain\ViewModel\FormValueViewModel($this->element, 'blue', 'Blue'),
            'third' => new Src\Domain\ViewModel\FormValueViewModel($this->element, null, 'Green'),
        ];

        self::assertEquals($expected, $subject->getChildren());
    }
}
