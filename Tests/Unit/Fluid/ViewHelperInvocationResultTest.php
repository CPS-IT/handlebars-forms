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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\Fluid;

use CPSIT\Typo3HandlebarsForms as Src;
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\TestingFramework;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewHelperInvocationResultTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Fluid\ViewHelperInvocationResult::class)]
final class ViewHelperInvocationResultTest extends TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function extractChildNodesReturnsEmptyListIfContentIsUnsupportedDataProvider(): \Generator
    {
        yield 'empty string' => [''];
        yield 'array' => [['<option>foo</option>']];
        yield 'object' => [new \stdClass()];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('extractChildNodesReturnsEmptyListIfContentIsUnsupportedDataProvider')]
    public function extractChildNodesReturnsEmptyListIfContentIsUnsupported(mixed $content): void
    {
        $subject = $this->createSubject($content);

        self::assertSame([], $subject->extractChildNodes('option'));
    }

    #[Framework\Attributes\Test]
    public function extractChildNodesReturnsEmptyListIfNoMatchingNodesExist(): void
    {
        $subject = $this->createSubject('<option value="foo">Foo</option>');

        self::assertSame([], $subject->extractChildNodes('input'));
    }

    #[Framework\Attributes\Test]
    public function extractChildNodesReturnsMatchingNodesAsTagBuilders(): void
    {
        $subject = $this->createSubject(
            '<optgroup label="Group"><option value="foo" selected="selected"> Foo </option></optgroup><option value="bar">Bar</option>',
        );

        $foo = new FluidStandalone\Core\ViewHelper\TagBuilder('option', 'Foo');
        $foo->addAttribute('value', 'foo');
        $foo->addAttribute('selected', 'selected');

        $bar = new FluidStandalone\Core\ViewHelper\TagBuilder('option', 'Bar');
        $bar->addAttribute('value', 'bar');

        self::assertEquals([$foo, $bar], $subject->extractChildNodes('option'));
    }

    #[Framework\Attributes\Test]
    public function extractChildNodesPreservesUtf8Characters(): void
    {
        $subject = $this->createSubject('<option value="ä">Äpfel</option>');

        $expected = new FluidStandalone\Core\ViewHelper\TagBuilder('option', 'Äpfel');
        $expected->addAttribute('value', 'ä');

        self::assertEquals([$expected], $subject->extractChildNodes('option'));
    }

    #[Framework\Attributes\Test]
    public function extractChildNodesFallsBackToTagContentIfContentIsNull(): void
    {
        $tag = new FluidStandalone\Core\ViewHelper\TagBuilder('select', '<option value="foo">Foo</option>');
        $subject = $this->createSubject(null, $tag);

        $expected = new FluidStandalone\Core\ViewHelper\TagBuilder('option', 'Foo');
        $expected->addAttribute('value', 'foo');

        self::assertEquals([$expected], $subject->extractChildNodes('option'));
    }

    #[Framework\Attributes\Test]
    public function extractChildNodesIgnoresNonElementNodes(): void
    {
        $subject = $this->createSubject('<option value="foo">Foo</option>');

        self::assertSame([], $subject->extractChildNodes('text()'));
    }

    private function createSubject(
        mixed $content,
        FluidStandalone\Core\ViewHelper\TagBuilder $tag = new FluidStandalone\Core\ViewHelper\TagBuilder(),
    ): Src\Fluid\ViewHelperInvocationResult {
        return new Src\Fluid\ViewHelperInvocationResult(
            self::createStub(FluidStandalone\Core\ViewHelper\ViewHelperInterface::class),
            self::createStub(Fluid\Core\Rendering\RenderingContext::class),
            $content,
            $tag,
        );
    }
}
