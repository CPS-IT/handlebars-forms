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
 * ContentElementViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\ContentElementViewModelBuilder::class)]
final class ContentElementViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\ContentElementViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'content',
                'type' => 'ContentElement',
                'label' => 'Content',
                'properties' => [
                    'contentElementUid' => 42,
                    'elementClassAttribute' => 'content-element',
                ],
            ],
            [
                'identifier' => 'content-without-uid',
                'type' => 'ContentElement',
                'label' => 'Content without uid',
            ],
            [
                'identifier' => 'content-with-invalid-uid',
                'type' => 'ContentElement',
                'label' => 'Content with invalid uid',
                'properties' => [
                    'contentElementUid' => 0,
                ],
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\ContentElementViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForContentElement(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('content')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function buildReturnsSimpleViewModelIfContentElementUidIsMissingOrInvalidDataProvider(): \Generator
    {
        yield 'missing uid' => ['content-without-uid'];
        yield 'invalid uid' => ['content-with-invalid-uid'];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('buildReturnsSimpleViewModelIfContentElementUidIsMissingOrInvalidDataProvider')]
    public function buildReturnsSimpleViewModelIfContentElementUidIsMissingOrInvalid(string $identifier): void
    {
        $element = $this->getElement($identifier);

        $actual = $this->subject->build($element, $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\SimpleViewModel::class, $actual);
        self::assertSame($element, $actual->getRenderable());
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedContentElement(): void
    {
        $actual = $this->subject->build($this->getElement('content'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);
        self::assertSame('<div>42</div>', $actual->viewHelperInvocationResult->content);
        self::assertSame('content-element', $actual->getTag()->getAttribute('class'));
    }

    protected function getTypoScriptSetup(): string
    {
        return <<<'TYPOSCRIPT'
lib.tx_form.contentElementRendering = TEXT
lib.tx_form.contentElementRendering {
  current = 1
  wrap = <div>|</div>
}
TYPOSCRIPT;
    }
}
