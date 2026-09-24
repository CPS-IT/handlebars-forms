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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\ContentObject\Context;

use CPSIT\Typo3HandlebarsForms as Src;
use PHPUnit\Framework;
use TYPO3\TestingFramework;

/**
 * ValueCollectorTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\Context\ValueCollector::class)]
final class ValueCollectorTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Src\ContentObject\AbstractHandlebarsFormsContentObject $contentObject;
    private Src\ContentObject\Context\ValueCollector $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->contentObject = new class extends Src\ContentObject\AbstractHandlebarsFormsContentObject {
            protected function resolve(array $configuration, Src\ContentObject\Context\ValueResolutionContext $context): mixed
            {
                return null;
            }
        };
        $this->subject = new Src\ContentObject\Context\ValueCollector();
    }

    #[Framework\Attributes\Test]
    public function saveReturnsUniqueIdentifier(): void
    {
        $first = $this->subject->save($this->contentObject, 'foo');
        $second = $this->subject->save($this->contentObject, 'foo');

        self::assertStringStartsWith('HandlebarsFormsValue_', $first);
        self::assertStringStartsWith('HandlebarsFormsValue_', $second);
        self::assertNotSame($first, $second);
    }

    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function loadReturnsSavedValueDataProvider(): \Generator
    {
        yield 'string' => ['foo'];
        yield 'bool' => [false];
        yield 'int' => [0];
        yield 'array' => [['foo' => 'bar']];
        yield 'object' => [new \stdClass()];
        yield 'null' => [null];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('loadReturnsSavedValueDataProvider')]
    public function loadReturnsSavedValue(mixed $value): void
    {
        $identifier = $this->subject->save($this->contentObject, $value);

        self::assertSame($value, $this->subject->load($identifier));
    }

    #[Framework\Attributes\Test]
    public function loadReturnsSavedValueFromOtherInstance(): void
    {
        $identifier = $this->subject->save($this->contentObject, 'foo');

        self::assertSame('foo', (new Src\ContentObject\Context\ValueCollector())->load($identifier));
    }

    #[Framework\Attributes\Test]
    public function loadReturnsNullIfIdentifierIsInvalid(): void
    {
        self::assertNull($this->subject->load('foo'));
    }

    #[Framework\Attributes\Test]
    public function loadReturnsNullIfNoValueWasSaved(): void
    {
        self::assertNull($this->subject->load('HandlebarsFormsValue_foo'));
    }

    #[Framework\Attributes\Test]
    public function hasReturnsTrueIfValueWasSaved(): void
    {
        $identifier = $this->subject->save($this->contentObject, 'foo');

        self::assertTrue($this->subject->has($identifier));
    }

    #[Framework\Attributes\Test]
    public function hasReturnsTrueIfSavedValueIsNull(): void
    {
        $identifier = $this->subject->save($this->contentObject, null);

        self::assertTrue($this->subject->has($identifier));
    }

    #[Framework\Attributes\Test]
    public function hasReturnsFalseIfIdentifierIsInvalid(): void
    {
        self::assertFalse($this->subject->has('foo'));
    }

    #[Framework\Attributes\Test]
    public function hasReturnsFalseIfNoValueWasSaved(): void
    {
        self::assertFalse($this->subject->has('HandlebarsFormsValue_foo'));
    }
}
