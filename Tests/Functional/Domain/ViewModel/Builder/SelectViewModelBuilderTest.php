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
 * SelectViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\SelectViewModelBuilder::class)]
final class SelectViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\SelectViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'single',
                'type' => 'SingleSelect',
                'label' => 'Single select',
                'properties' => [
                    'elementClassAttribute' => 'form-select',
                    'prependOptionLabel' => 'Please choose',
                    'prependOptionValue' => '',
                    'options' => [
                        'a' => 'Option A',
                        'b' => 'Option B',
                    ],
                ],
            ],
            [
                'identifier' => 'multi',
                'type' => 'MultiSelect',
                'label' => 'Multi select',
                'properties' => [
                    'options' => [
                        'a' => 'Option A',
                    ],
                ],
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\SelectViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForSingleSelectAndMultiSelect(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('single')));
        self::assertTrue($this->subject->supports($this->getElement('multi')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedSelectAndOptionsAsChildren(): void
    {
        $actual = $this->subject->build($this->getElement('single'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('select', $tag->getTagName());
        self::assertSame('test-form-single', $tag->getAttribute('id'));
        self::assertSame('form-select', $tag->getAttribute('class'));
        self::assertNull($tag->getAttribute('multiple'));

        $options = array_map(
            static function (Src\Domain\ViewModel\ViewModel $child) {
                self::assertInstanceOf(Src\Domain\ViewModel\StandaloneTagViewModel::class, $child);

                return [$child->getTag()->getAttribute('value'), $child->getTag()->getContent()];
            },
            $actual->getChildren(),
        );

        self::assertSame([['', 'Please choose'], ['a', 'Option A'], ['b', 'Option B']], $options);
    }

    #[Framework\Attributes\Test]
    public function buildRendersMultipleSelectForMultiSelect(): void
    {
        $actual = $this->subject->build($this->getElement('multi'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);
        self::assertSame('multiple', $actual->getTag()->getAttribute('multiple'));
    }
}
