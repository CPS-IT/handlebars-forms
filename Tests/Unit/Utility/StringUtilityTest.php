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
            new class () implements \Stringable {
                public function __toString(): string
                {
                    return 'foo';
                }
            },
            false,
        ];
        yield 'bool' => [true, false];
        yield 'int' => [1, false];
        yield 'float' => [1.0, false];
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
}
