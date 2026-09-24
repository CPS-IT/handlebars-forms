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
 * TextareaViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\TextareaViewModelBuilder::class)]
final class TextareaViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\TextareaViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'message',
                'type' => 'Textarea',
                'label' => 'Message',
                'properties' => [
                    'elementClassAttribute' => 'form-control',
                    'rows' => 5,
                    'cols' => 40,
                ],
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\TextareaViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForTextarea(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('message')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedTextarea(): void
    {
        $actual = $this->subject->build($this->getElement('message'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('textarea', $tag->getTagName());
        self::assertSame('test-form-message', $tag->getAttribute('id'));
        self::assertSame('form-control', $tag->getAttribute('class'));
        self::assertSame('5', $tag->getAttribute('rows'));
        self::assertSame('40', $tag->getAttribute('cols'));
    }
}
