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
use PHPUnit\Framework;
use Psr\Http\Message;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * FormValueContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\FormValueContentObject::class)]
final class FormValueContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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

    private Form\Domain\Runtime\FormRuntime $formRuntime;
    private Fluid\Core\Rendering\RenderingContext $renderingContext;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\FormValueContentObject $subject;

    /**
     * @var list<array{array<string|int, mixed>, Src\Domain\ViewModel\ViewModel|null}>
     */
    private array $processorCalls = [];

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $request = $this->buildExtbaseRequest();
        $this->get(Extbase\Configuration\ConfigurationManagerInterface::class)->setRequest($request);

        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
            [
                'identifier' => 'test-form',
                'type' => 'Form',
                'prototypeName' => 'standard',
                'label' => 'Test form',
                'renderables' => [
                    [
                        'identifier' => 'page-1',
                        'type' => 'Page',
                        'label' => 'Page 1',
                        'renderables' => [
                            [
                                'identifier' => 'name',
                                'type' => 'Text',
                                'label' => 'Name',
                                'defaultValue' => 'John Doe',
                            ],
                            [
                                'identifier' => 'colors',
                                'type' => 'MultiCheckbox',
                                'label' => 'Colors',
                                'defaultValue' => ['red', 'blue'],
                                'properties' => [
                                    'options' => [
                                        'red' => 'Red',
                                        'green' => 'Green',
                                        'blue' => 'Blue',
                                    ],
                                ],
                            ],
                            [
                                'identifier' => 'fieldset',
                                'type' => 'Fieldset',
                                'label' => 'Fieldset',
                            ],
                            [
                                'identifier' => 'row',
                                'type' => 'GridRow',
                                'label' => 'Row',
                            ],
                        ],
                    ],
                ],
            ],
            'standard',
            $request,
        );

        $this->formRuntime = $formDefinition->bind($request);
        $this->renderingContext = $this->get(Fluid\Core\Rendering\RenderingContextFactory::class)->create();
        $this->renderingContext->setAttribute(Message\ServerRequestInterface::class, $request);
        $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate(
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            'formRuntime',
            $this->formRuntime,
        );

        $this->contextStack = new Src\ContentObject\Context\ContextStack();
        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->subject = new Src\ContentObject\FormValueContentObject(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfFormValueCannotBeResolved(): void
    {
        $this->pushContext('row');

        self::assertNull($this->valueCollector->load($this->subject->render()));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsProcessedValueIfNoOutputIsConfigured(): void
    {
        $this->pushContext('name');

        self::assertSame('John Doe', $this->subject->render());
    }

    /**
     * @return \Generator<string, array{string, string, mixed}>
     */
    public static function renderReturnsValueForConfiguredOutputInstructionDataProvider(): \Generator
    {
        yield 'VALUE' => ['colors', 'VALUE', ['red', 'blue']];
        yield 'PROCESSED_VALUE' => ['colors', 'PROCESSED_VALUE', ['Red', 'Blue']];
        yield 'IS_MULTI_VALUE (true)' => ['colors', 'IS_MULTI_VALUE', true];
        yield 'IS_MULTI_VALUE (false)' => ['name', 'IS_MULTI_VALUE', false];
        yield 'IS_SECTION (true)' => ['fieldset', 'IS_SECTION', true];
        yield 'IS_SECTION (false)' => ['name', 'IS_SECTION', false];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderReturnsValueForConfiguredOutputInstructionDataProvider')]
    public function renderReturnsValueForConfiguredOutputInstruction(
        string $identifier,
        string $outputInstruction,
        mixed $expected,
    ): void {
        $this->pushContext($identifier);

        self::assertSame(
            $expected,
            $this->valueCollector->load($this->subject->render(['output' => $outputInstruction])),
        );
    }

    #[Framework\Attributes\Test]
    public function renderProcessesGenericOutputInstructionWithFormValueViewModel(): void
    {
        $this->pushContext('name');

        $actual = $this->subject->render([
            'output' => 'TEXT',
            'output.' => [
                'value' => 'foo',
            ],
        ]);

        self::assertSame('processed', $actual);
        self::assertCount(1, $this->processorCalls);
        self::assertSame(
            [
                'tempKey' => 'TEXT',
                'tempKey.' => [
                    'value' => 'foo',
                ],
            ],
            $this->processorCalls[0][0],
        );
        self::assertInstanceOf(Src\Domain\ViewModel\FormValueViewModel::class, $this->processorCalls[0][1]);
        self::assertSame('John Doe', $this->processorCalls[0][1]->value);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesOutputConfiguration(): void
    {
        $this->pushContext('name');

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'output.' => [
                    'value' => 'VALUE',
                    'meta.' => [
                        'isMultiValue' => 'IS_MULTI_VALUE',
                        'isSection' => 'IS_SECTION',
                    ],
                ],
            ]),
        );

        $expected = [
            'value' => 'John Doe',
            'meta' => [
                'isMultiValue' => false,
                'isSection' => false,
            ],
        ];

        self::assertSame($expected, $actual);
    }

    /**
     * @return \Generator<string, array{string}>
     */
    public static function renderProcessesEachValueOfMultiValueFormValueDataProvider(): \Generator
    {
        yield 'EACH_VALUE' => ['EACH_VALUE'];
        yield 'EACH_PROCESSED_VALUE' => ['EACH_PROCESSED_VALUE'];
    }

    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderProcessesEachValueOfMultiValueFormValueDataProvider')]
    public function renderProcessesEachValueOfMultiValueFormValue(string $outputInstruction): void
    {
        $this->pushContext('colors');

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'output.' => [
                    'values' => $outputInstruction,
                    'values.' => [
                        'value' => 'VALUE',
                        'label' => 'PROCESSED_VALUE',
                    ],
                ],
            ]),
        );

        $expected = [
            'values' => [
                [
                    'value' => 'red',
                    'label' => 'Red',
                ],
                [
                    'value' => 'blue',
                    'label' => 'Blue',
                ],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsEmptyListForEachValueOfSingleValueFormValue(): void
    {
        $this->pushContext('name');

        self::assertSame(
            [],
            $this->valueCollector->load($this->subject->render(['output' => 'EACH_VALUE'])),
        );
    }

    private function pushContext(string $identifier): void
    {
        $renderable = $this->formRuntime->getFormDefinition()->getElementByIdentifier($identifier);

        self::assertInstanceOf(Form\Domain\Model\FormElements\FormElementInterface::class, $renderable);

        $this->contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $renderable,
                new Src\Domain\ViewModel\SimpleViewModel($renderable),
                $this->renderingContext,
                $this->formRuntime,
                function (
                    array $configuration,
                    ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
                    ?Src\Domain\ViewModel\ViewModel $viewModel,
                ) {
                    $this->processorCalls[] = [$configuration, $viewModel];

                    return ['tempKey' => 'processed'];
                },
            ),
        );
    }
}
