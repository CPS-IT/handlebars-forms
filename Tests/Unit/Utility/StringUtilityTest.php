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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\Utility;

use CPSIT\Typo3HandlebarsForms as Src;
use DevTheorem\Handlebars;
use PHPUnit\Framework;
use TYPO3\TestingFramework;

/**
 * StringUtilityTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Utility\StringUtility::class)]
final class StringUtilityTest extends TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @return \Generator<string, array{mixed, bool}>
     */
    public static function isStringableReturnsTrueForSupportedTypeDataProvider(): \Generator
    {
        yield 'string' => ['foo', true];
        yield 'SafeString' => [new Handlebars\SafeString('foo'), true];
        yield 'null' => [null, true];
        yield 'Stringable' => [
            new class implements \Stringable {
                public function __toString(): string
                {
                    return 'foo';
                }
            },
            true,
        ];
        yield 'bool' => [true, true];
        yield 'int' => [1, true];
        yield 'float' => [1.0, true];
        yield 'object' => [new \stdClass(), false];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('isStringableReturnsTrueForSupportedTypeDataProvider')]
    public function isStringableReturnsTrueForSupportedType(mixed $value, bool $expected): void
    {
        self::assertSame($expected, Src\Utility\StringUtility::isStringable($value));
    }

    #[Framework\Attributes\Test]
    public function processStringableReturnsProcessedString(): void
    {
        $processor = static fn(string $value) => $value . $value;

        self::assertSame('foofoo', Src\Utility\StringUtility::processStringable('foo', $processor));
    }

    #[Framework\Attributes\Test]
    public function processStringableReturnsProcessedSafeString(): void
    {
        $processor = static fn(string $value) => $value . $value;

        self::assertEquals(
            new Handlebars\SafeString('foofoo'),
            Src\Utility\StringUtility::processStringable(new Handlebars\SafeString('foo'), $processor),
        );
    }

    #[Framework\Attributes\Test]
    public function processStringableReturnsProcessedNullValue(): void
    {
        $processor = static fn(string $value) => 'foo';

        self::assertSame('foo', Src\Utility\StringUtility::processStringable(null, $processor));
    }

    #[Framework\Attributes\Test]
    public function processStringableReturnsProcessedStringableValue(): void
    {
        $processor = static fn(string $value) => $value . $value;
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'foo';
            }
        };

        self::assertSame('foofoo', Src\Utility\StringUtility::processStringable($stringable, $processor));
    }

    /**
     * @return \Generator<string, array{int|float|bool, string}>
     */
    public static function processStringableReturnsProcessedScalarValueDataProvider(): \Generator
    {
        yield 'bool' => [true, '11'];
        yield 'int' => [1, '11'];
        yield 'float' => [1.0, '11'];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('processStringableReturnsProcessedScalarValueDataProvider')]
    public function processStringableReturnsProcessedScalarValue(int|float|bool $value, string $expected): void
    {
        $processor = static fn(string $value) => $value . $value;

        self::assertSame($expected, Src\Utility\StringUtility::processStringable($value, $processor));
    }
}
