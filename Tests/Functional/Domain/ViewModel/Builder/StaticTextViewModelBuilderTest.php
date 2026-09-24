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
 * StaticTextViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\StaticTextViewModelBuilder::class)]
final class StaticTextViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\StaticTextViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'notice',
                'type' => 'StaticText',
                'label' => 'Notice',
                'properties' => [
                    'elementClassAttribute' => 'lead',
                    'text' => "First line\nSecond line",
                ],
            ],
            [
                'identifier' => 'empty-notice',
                'type' => 'StaticText',
                'label' => '',
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\StaticTextViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForStaticText(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('notice')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsLabeledParagraphWithTextAndLineBreaks(): void
    {
        $actual = $this->subject->build($this->getElement('notice'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\FormFieldViewModel::class, $actual);
        self::assertSame('Notice', $actual->label->getContent());

        $tag = $actual->getTag();

        self::assertSame('p', $tag->getTagName());
        self::assertSame("First line<br />\nSecond line", $tag->getContent());
        self::assertSame('lead', $tag->getAttribute('class'));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsParagraphWithoutClassIfNoClassIsConfigured(): void
    {
        $actual = $this->subject->build($this->getElement('empty-notice'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\FormFieldViewModel::class, $actual);
        self::assertSame('p', $actual->getTag()->getTagName());
        self::assertNull($actual->getTag()->getAttribute('class'));
    }
}
