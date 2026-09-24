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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\ContentObject;

use CPSIT\Typo3HandlebarsForms as Src;
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * PropertyContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\PropertyContentObject::class)]
final class PropertyContentObjectTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\PropertyContentObject $subject;

    public function setUp(): void
    {
        parent::setUp();

        $renderable = new Form\Domain\Model\FormElements\GenericFormElement('name', 'Text');
        $renderable->setProperty('foo', 'bar');

        $formRuntime = self::createStub(Form\Domain\Runtime\FormRuntime::class);
        $formRuntime->method('getIdentifier')->willReturn('test-form');

        $contextStack = new Src\ContentObject\Context\ContextStack();
        $contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $renderable,
                new Src\Domain\ViewModel\SimpleViewModel($renderable, ['children' => ['foo' => 'baz']]),
                self::createStub(Fluid\Core\Rendering\RenderingContext::class),
                $formRuntime,
            ),
        );

        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->subject = new Src\ContentObject\PropertyContentObject();
        $this->subject->injectContextStack($contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public static function renderReturnsNullIfPathIsMissingOrInvalidDataProvider(): \Generator
    {
        yield 'missing path' => [[]];
        yield 'invalid path' => [['path' => ['type']]];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderReturnsNullIfPathIsMissingOrInvalidDataProvider')]
    public function renderReturnsNullIfPathIsMissingOrInvalid(array $configuration): void
    {
        self::assertNull($this->valueCollector->load($this->subject->render($configuration)));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsPropertyOfRenderableByDefault(): void
    {
        self::assertSame('Text', $this->subject->render(['path' => 'type']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsPropertyOfRenderableIfSubjectIsUnknown(): void
    {
        self::assertSame('Text', $this->subject->render(['path' => 'type', 'subject' => 'foo']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNestedPropertyOfRenderable(): void
    {
        self::assertSame('bar', $this->subject->render(['path' => 'properties.foo']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNonStringPropertyOfRenderable(): void
    {
        self::assertSame(
            ['foo' => 'bar'],
            $this->valueCollector->load($this->subject->render(['path' => 'properties'])),
        );
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfPropertyDoesNotExist(): void
    {
        self::assertNull($this->valueCollector->load($this->subject->render(['path' => 'properties.baz'])));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsPropertyOfViewModel(): void
    {
        self::assertSame('baz', $this->subject->render(['path' => 'children.foo', 'subject' => 'viewModel']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsPropertyOfFormRuntime(): void
    {
        self::assertSame('test-form', $this->subject->render(['path' => 'identifier', 'subject' => 'formRuntime']));
    }
}
