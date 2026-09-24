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
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * ContextAwareContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\Context\ContextAwareContentObject::class)]
final class ContextAwareContentObjectTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\AbstractHandlebarsFormsContentObject $handlebarsFormsContentObject;

    public function setUp(): void
    {
        parent::setUp();

        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->handlebarsFormsContentObject = new class extends Src\ContentObject\AbstractHandlebarsFormsContentObject {
            protected function resolve(array $configuration, Src\ContentObject\Context\ValueResolutionContext $context): mixed
            {
                return null;
            }
        };
    }

    #[Framework\Attributes\Test]
    public function renderPassesConfigurationToDecoratedContentObject(): void
    {
        $configuration = null;
        $contentObject = $this->createContentObject(
            'foo',
            static function (array $conf) use (&$configuration) {
                $configuration = $conf;
            },
        );
        $subject = new Src\ContentObject\Context\ContextAwareContentObject($contentObject, $this->valueCollector);

        $subject->render(['foo' => 'bar']);

        self::assertSame(['foo' => 'bar'], $configuration);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsStringValueFromDecoratedContentObjectIfValueIsNotCollected(): void
    {
        $subject = new Src\ContentObject\Context\ContextAwareContentObject(
            $this->createContentObject('foo'),
            $this->valueCollector,
        );

        self::assertSame('foo', $subject->render());
    }

    /**
     * @return \Generator<string, array{bool|int|float|string|null, string}>
     */
    public static function renderReturnsCollectedScalarValueAsStringDataProvider(): \Generator
    {
        yield 'string' => ['foo', 'foo'];
        yield 'true' => [true, '1'];
        yield 'false' => [false, ''];
        yield 'int' => [42, '42'];
        yield 'float' => [1.5, '1.5'];
        yield 'null' => [null, ''];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderReturnsCollectedScalarValueAsStringDataProvider')]
    public function renderReturnsCollectedScalarValueAsString(bool|int|float|string|null $value, string $expected): void
    {
        $identifier = $this->valueCollector->save($this->handlebarsFormsContentObject, $value);
        $subject = new Src\ContentObject\Context\ContextAwareContentObject(
            $this->createContentObject($identifier),
            $this->valueCollector,
        );

        self::assertSame($expected, $subject->render());
    }

    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function renderReturnsIdentifierOfCollectedNonScalarValueDataProvider(): \Generator
    {
        yield 'array' => [['foo' => 'bar']];
        yield 'object' => [new \stdClass()];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderReturnsIdentifierOfCollectedNonScalarValueDataProvider')]
    public function renderReturnsIdentifierOfCollectedNonScalarValue(mixed $value): void
    {
        $identifier = $this->valueCollector->save($this->handlebarsFormsContentObject, $value);
        $subject = new Src\ContentObject\Context\ContextAwareContentObject(
            $this->createContentObject($identifier),
            $this->valueCollector,
        );

        self::assertSame($identifier, $subject->render());
    }

    /**
     * @param (\Closure(array<string|int, mixed>): void)|null $onRender
     */
    private function createContentObject(
        string $value,
        ?\Closure $onRender = null,
    ): Frontend\ContentObject\AbstractContentObject {
        return new class ($value, $onRender) extends Frontend\ContentObject\AbstractContentObject {
            /**
             * @param (\Closure(array<string|int, mixed>): void)|null $onRender
             */
            public function __construct(
                private readonly string $value,
                private readonly ?\Closure $onRender,
            ) {}

            /**
             * @param array<string|int, mixed> $conf
             */
            public function render($conf = []): string
            {
                if ($this->onRender !== null) {
                    ($this->onRender)($conf);
                }

                return $this->value;
            }
        };
    }
}
