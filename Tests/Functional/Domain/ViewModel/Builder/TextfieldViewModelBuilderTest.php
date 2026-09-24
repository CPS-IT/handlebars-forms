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
use TYPO3\CMS\Form;

/**
 * TextfieldViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\TextfieldViewModelBuilder::class)]
final class TextfieldViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\TextfieldViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $renderables = [];

        foreach (['Date', 'Email', 'Number', 'Telephone', 'Text', 'Url'] as $type) {
            $renderables[] = [
                'identifier' => strtolower($type),
                'type' => $type,
                'label' => $type,
                'properties' => [
                    'elementClassAttribute' => 'form-control',
                ],
            ];
        }

        $renderables[] = [
            'identifier' => 'message',
            'type' => 'Textarea',
            'label' => 'Message',
        ];

        $this->buildFormRuntime($renderables);

        $this->subject = new Src\Domain\ViewModel\Builder\TextfieldViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    /**
     * @return \Generator<string, array{string, string}>
     */
    public static function buildReturnsViewModelWithRenderedTextfieldOfMatchingTypeDataProvider(): \Generator
    {
        yield 'Date' => ['date', 'date'];
        yield 'Email' => ['email', 'email'];
        yield 'Number' => ['number', 'number'];
        yield 'Telephone' => ['telephone', 'tel'];
        yield 'Text' => ['text', 'text'];
        yield 'Url' => ['url', 'url'];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('buildReturnsViewModelWithRenderedTextfieldOfMatchingTypeDataProvider')]
    public function supportsReturnsTrueForSupportedType(string $identifier): void
    {
        self::assertTrue($this->subject->supports($this->getElement($identifier, Form\Domain\Model\FormElements\FormElementInterface::class)));
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsFalseForUnsupportedType(): void
    {
        self::assertFalse($this->subject->supports($this->getElement('message')));
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('buildReturnsViewModelWithRenderedTextfieldOfMatchingTypeDataProvider')]
    public function buildReturnsViewModelWithRenderedTextfieldOfMatchingType(string $identifier, string $expectedType): void
    {
        $actual = $this->subject->build($this->getTextfieldElement($identifier), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('input', $tag->getTagName());
        self::assertSame($expectedType, $tag->getAttribute('type'));
        self::assertSame('test-form-' . $identifier, $tag->getAttribute('id'));
        self::assertSame('form-control', $tag->getAttribute('class'));
    }

    private function getTextfieldElement(
        string $identifier,
    ): Form\Domain\Model\FormElements\GenericFormElement|Form\Domain\Model\FormElements\Date {
        $element = $this->getElement($identifier, Form\Domain\Model\FormElements\AbstractFormElement::class);

        if (!($element instanceof Form\Domain\Model\FormElements\GenericFormElement)
            && !($element instanceof Form\Domain\Model\FormElements\Date)
        ) {
            self::fail(sprintf('Element "%s" is neither a generic form element nor a date element.', $identifier));
        }

        return $element;
    }
}
