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
use PHPUnit\Framework;

/**
 * RadioViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\RadioViewModelBuilder::class)]
final class RadioViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\RadioViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'colors',
                'type' => 'RadioButton',
                'label' => 'Colors',
                'properties' => [
                    'elementClassAttribute' => 'form-check-input',
                    'options' => [
                        'red' => 'Red',
                        'blue' => 'Blue',
                    ],
                ],
            ],
            [
                'identifier' => 'no-options',
                'type' => 'RadioButton',
                'label' => 'No options',
                'properties' => [
                    'options' => 'foo',
                ],
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\RadioViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForRadioButton(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('colors')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsCollectionOfLabeledFieldsForEachOption(): void
    {
        $actual = $this->subject->build($this->getElement('colors'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $actual);

        $children = $actual->getChildren();

        self::assertCount(2, $children);

        foreach ([['red', 'Red'], ['blue', 'Blue']] as $index => [$value, $label]) {
            $child = $children[$index];

            self::assertInstanceOf(Src\Domain\ViewModel\FormFieldViewModel::class, $child);
            self::assertSame($label, $child->label->getContent());

            $tag = $child->getTag();

            self::assertSame('input', $tag->getTagName());
            self::assertSame('radio', $tag->getAttribute('type'));
            self::assertSame('test-form-colors-' . $index, $tag->getAttribute('id'));
            self::assertSame('form-check-input', $tag->getAttribute('class'));
            self::assertSame($value, $tag->getAttribute('value'));
        }
    }

    #[Framework\Attributes\Test]
    public function buildReturnsEmptyCollectionIfOptionsAreInvalid(): void
    {
        $actual = $this->subject->build($this->getElement('no-options'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $actual);
        self::assertSame([], $actual->getChildren());
    }
}
