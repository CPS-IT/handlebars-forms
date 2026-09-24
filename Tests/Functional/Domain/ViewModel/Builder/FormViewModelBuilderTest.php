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
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * FormViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\FormViewModelBuilder::class)]
final class FormViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\FormViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\FormViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForForm(): void
    {
        self::assertTrue($this->subject->supports($this->formRuntime));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedFormTag(): void
    {
        $actual = $this->subject->build($this->formRuntime, $this->renderingContext);

        self::assertSame($this->formRuntime, $actual->getRenderable());

        $tag = $actual->getTag();

        self::assertSame('form', $tag->getTagName());
        self::assertSame('test-form', $tag->getAttribute('id'));
        self::assertSame('post', $tag->getAttribute('method'));
    }

    #[Framework\Attributes\Test]
    public function buildPassesFormTagToGivenClosure(): void
    {
        $closureTag = null;

        $actual = $this->subject->build(
            $this->formRuntime,
            $this->renderingContext,
            static function (?FluidStandalone\Core\ViewHelper\TagBuilder $tag = null) use (&$closureTag) {
                $closureTag = $tag;

                return 'foo';
            },
        );

        self::assertSame($actual->getTag(), $closureTag);
        self::assertIsString($actual->viewHelperInvocationResult->content);
        self::assertStringContainsString('foo', $actual->viewHelperInvocationResult->content);
    }
}
