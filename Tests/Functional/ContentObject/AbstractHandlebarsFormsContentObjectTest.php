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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\ContentObject;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use DevTheorem\Handlebars;
use EliasHaeussler\TransientLogger;
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * AbstractHandlebarsFormsContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\AbstractHandlebarsFormsContentObject::class)]
final class AbstractHandlebarsFormsContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
{
    use Tests\FrontendRequestTrait;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected array $testExtensionsToLoad = [
        'handlebars',
        'handlebars_forms',
        'typed_extconf',
    ];

    private Frontend\ContentObject\ContentObjectRenderer $cObj;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private TransientLogger\TransientLogger $logger;
    private Src\ContentObject\Context\ValueResolutionContext $context;
    private Tests\Functional\Fixtures\Classes\DummyContentObject $subject;

    private mixed $resolvedValue = null;
    private mixed $processedValue = null;

    /**
     * @var list<array{array<string|int, mixed>, Form\Domain\Model\Renderable\RootRenderableInterface|null, Src\Domain\ViewModel\ViewModel|null}>
     */
    private array $processorCalls = [];

    public function setUp(): void
    {
        parent::setUp();

        $request = $this->buildServerRequest();

        $this->cObj = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->setRequest($request);
        $this->contextStack = new Src\ContentObject\Context\ContextStack();
        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->logger = new TransientLogger\TransientLogger();
        $this->context = new Src\ContentObject\Context\ValueResolutionContext(
            self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class),
            self::createStub(Src\Domain\ViewModel\ViewModel::class),
            self::createStub(Fluid\Core\Rendering\RenderingContext::class),
            self::createStub(Form\Domain\Runtime\FormRuntime::class),
            function (
                array $configuration,
                ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
                ?Src\Domain\ViewModel\ViewModel $viewModel,
            ) {
                $this->processorCalls[] = [$configuration, $renderable, $viewModel];

                return $this->processedValue;
            },
        );

        $this->subject = new Tests\Functional\Fixtures\Classes\DummyContentObject(
            fn() => $this->resolvedValue,
        );
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
        $this->subject->setLogger($this->logger);
        $this->subject->setRequest($request);
        $this->subject->setContentObjectRenderer($this->cObj);
    }

    #[Framework\Attributes\Test]
    public function renderLogsWarningAndReturnsEmptyStringIfNoContextIsAvailable(): void
    {
        $this->resolvedValue = 'foo';

        self::assertSame('', $this->subject->render());

        $logs = $this->logger->getByLogLevel(TransientLogger\Log\LogLevel::Warning);

        self::assertCount(1, $logs);
        self::assertSame(
            'Using a HBS_* content object in other contexts than "process-form" data processor is not supported.',
            $logs[0]->message,
        );
    }

    #[Framework\Attributes\Test]
    public function renderPassesConfigurationAndCurrentContextToResolver(): void
    {
        $arguments = [];
        $subject = new Tests\Functional\Fixtures\Classes\DummyContentObject(
            static function (array $configuration, Src\ContentObject\Context\ValueResolutionContext $context) use (&$arguments) {
                $arguments = [$configuration, $context];

                return 'foo';
            },
        );
        $subject->injectContextStack($this->contextStack);
        $subject->injectValueCollector($this->valueCollector);

        $this->contextStack->push($this->context);

        $subject->render(['foo' => 'bar']);

        self::assertSame([['foo' => 'bar'], $this->context], $arguments);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsResolvedStringValue(): void
    {
        $this->resolvedValue = 'foo';
        $this->contextStack->push($this->context);

        self::assertSame('foo', $this->subject->render());
    }

    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function renderStoresResolvedNonStringValueInValueCollectorDataProvider(): \Generator
    {
        yield 'null' => [null];
        yield 'bool' => [true];
        yield 'int' => [42];
        yield 'array' => [['foo' => 'bar']];
        yield 'object' => [new \stdClass()];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderStoresResolvedNonStringValueInValueCollectorDataProvider')]
    public function renderStoresResolvedNonStringValueInValueCollector(mixed $value): void
    {
        $this->resolvedValue = $value;
        $this->contextStack->push($this->context);

        $actual = $this->subject->render();

        self::assertTrue($this->valueCollector->has($actual));
        self::assertSame($value, $this->valueCollector->load($actual));
    }

    #[Framework\Attributes\Test]
    public function renderAppliesStdWrapOnResolvedStringValue(): void
    {
        $this->resolvedValue = 'foo';
        $this->contextStack->push($this->context);

        $actual = $this->subject->render([
            'stdWrap.' => [
                'wrap' => '<strong>|</strong>',
            ],
        ]);

        self::assertSame('<strong>foo</strong>', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderAppliesStdWrapOnResolvedSafeStringValue(): void
    {
        $this->resolvedValue = new Handlebars\SafeString('foo');
        $this->contextStack->push($this->context);

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'stdWrap.' => [
                    'wrap' => '<strong>|</strong>',
                ],
            ]),
        );

        self::assertEquals(new Handlebars\SafeString('<strong>foo</strong>'), $actual);
    }

    #[Framework\Attributes\Test]
    public function renderAppliesStdWrapOnResolvedScalarValue(): void
    {
        $this->resolvedValue = 42;
        $this->contextStack->push($this->context);

        $actual = $this->subject->render([
            'stdWrap.' => [
                'wrap' => '<strong>|</strong>',
            ],
        ]);

        self::assertSame('<strong>42</strong>', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProvidesScalarArrayValuesAsCurrentValueForStdWrap(): void
    {
        $this->resolvedValue = ['foo', ['baz'], 'bar'];
        $this->contextStack->push($this->context);

        $actual = $this->subject->render([
            'stdWrap.' => [
                'current' => 1,
            ],
        ]);

        self::assertSame('foo,bar', $actual);
    }

    /**
     * @return \Generator<string, array{\stdClass|null}>
     */
    public static function renderProvidesNoCurrentValueForStdWrapIfResolvedValueIsNullOrNotStringableDataProvider(): \Generator
    {
        yield 'null' => [null];
        yield 'object' => [new \stdClass()];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderProvidesNoCurrentValueForStdWrapIfResolvedValueIsNullOrNotStringableDataProvider')]
    public function renderProvidesNoCurrentValueForStdWrapIfResolvedValueIsNullOrNotStringable(?\stdClass $value): void
    {
        $this->resolvedValue = $value;
        $this->contextStack->push($this->context);

        $actual = $this->subject->render([
            'stdWrap.' => [
                'current' => 1,
                'ifEmpty' => 'empty',
            ],
        ]);

        self::assertSame('empty', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderRestoresPreviousCurrentValueAfterStdWrapIsApplied(): void
    {
        $this->resolvedValue = 'foo';
        $this->contextStack->push($this->context);
        $this->cObj->setCurrentVal('previous');

        $this->subject->render([
            'stdWrap.' => [
                'current' => 1,
            ],
        ]);

        self::assertSame('previous', $this->cObj->getCurrentVal());
    }

    #[Framework\Attributes\Test]
    public function renderDoesNotApplyStdWrapIfContentObjectRendererIsMissing(): void
    {
        $subject = new Tests\Functional\Fixtures\Classes\DummyContentObject(
            static fn() => 'foo',
        );
        $subject->injectContextStack($this->contextStack);
        $subject->injectValueCollector($this->valueCollector);

        $this->contextStack->push($this->context);

        $actual = $subject->render([
            'stdWrap.' => [
                'wrap' => '<strong>|</strong>',
            ],
        ]);

        self::assertSame('foo', $actual);
    }

    #[Framework\Attributes\Test]
    public function processGenericValueProcessesValueWithinCurrentContext(): void
    {
        $this->processedValue = ['tempKey' => 'processed'];

        $actual = $this->subject->callProcessGenericValue('TEXT', ['value' => 'foo'], $this->context);

        $expectedCalls = [
            [
                [
                    'tempKey' => 'TEXT',
                    'tempKey.' => ['value' => 'foo'],
                ],
                $this->context->renderable,
                $this->context->viewModel,
            ],
        ];

        self::assertSame('processed', $actual);
        self::assertSame($expectedCalls, $this->processorCalls);
    }

    /**
     * @return \Generator<string, array{mixed}>
     */
    public static function processGenericValueReturnsOriginalValueIfProcessedValueIsUnusableDataProvider(): \Generator
    {
        yield 'null' => [null];
        yield 'string' => ['foo'];
        yield 'array without processed value' => [[]];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('processGenericValueReturnsOriginalValueIfProcessedValueIsUnusableDataProvider')]
    public function processGenericValueReturnsOriginalValueIfProcessedValueIsUnusable(mixed $processedValue): void
    {
        $this->processedValue = $processedValue;

        self::assertSame('TEXT', $this->subject->callProcessGenericValue('TEXT', [], $this->context));
    }
}
