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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\DataProcessing;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use DevTheorem\Handlebars;
use EliasHaeussler\TransientLogger;
use PHPUnit\Framework;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * ProcessFormProcessorTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\DataProcessing\ProcessFormProcessor::class)]
final class ProcessFormProcessorTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Form\Domain\Runtime\FormRuntime $formRuntime;
    private TransientLogger\TransientLogger $logger;
    private Src\DataProcessing\ProcessFormProcessor $subject;
    private ?Extbase\Mvc\ExtbaseRequestParameters $extbaseRequestParameters = null;

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $request = $this->buildExtbaseRequest($this->extbaseRequestParameters);
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
                                'identifier' => 'email',
                                'type' => 'Email',
                                'label' => 'Email',
                            ],
                        ],
                    ],
                ],
            ],
            'standard',
            $request,
        );

        $this->cObj = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->setRequest($request);
        $this->formRuntime = $formDefinition->bind($request);
        $this->logger = new TransientLogger\TransientLogger();
        $this->subject = new Src\DataProcessing\ProcessFormProcessor(
            $this->logger,
            $this->get(Fluid\Core\Rendering\RenderingContextFactory::class),
            $this->get(Src\Domain\ViewModel\Builder\FormViewModelBuilder::class),
            $this->get(Src\ContentObject\Context\ValueCollector::class),
            $this->get(Src\ContentObject\Context\ContextStack::class),
        );
    }

    #[Framework\Attributes\Test]
    public function processLogsWarningAndDoesNothingElseIfFormRuntimeIsMissing(): void
    {
        self::assertSame([], $this->subject->process($this->cObj, [], [], []));

        $logs = $this->logger->getByLogLevel(TransientLogger\Log\LogLevel::Error);

        self::assertCount(1, $logs);
        self::assertSame(
            'Form runtime is not available when trying to process form with plugin uid "{uid}".',
            $logs[0]->message,
        );
    }

    #[Framework\Attributes\Test]
    public function processResolvesHasErrorsInstructionAsTrueIfValidationResultsContainErrors(): void
    {
        self::assertInstanceOf(Extbase\Mvc\ExtbaseRequestParameters::class, $this->extbaseRequestParameters);

        $validationResults = new Extbase\Error\Result();
        $validationResults->forProperty($this->formRuntime->getIdentifier())->addError(
            new Extbase\Error\Error('Something went wrong.', 1234567890),
        );

        $this->extbaseRequestParameters->setOriginalRequestMappingResults($validationResults);

        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'hasErrors' => 'HBS_VALIDATION_RESULTS',
                'hasErrors.' => ['output' => 'HAS_ERRORS'],
            ],
            [],
        );

        self::assertTrue($processedData['hasErrors']);
    }

    #[Framework\Attributes\Test]
    public function processResolvesHasErrorsInstructionAsFalseIfValidationResultsContainNoErrors(): void
    {
        self::assertInstanceOf(Extbase\Mvc\ExtbaseRequestParameters::class, $this->extbaseRequestParameters);

        $this->extbaseRequestParameters->setOriginalRequestMappingResults(new Extbase\Error\Result());

        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'hasErrors' => 'HBS_VALIDATION_RESULTS',
                'hasErrors.' => ['output' => 'HAS_ERRORS'],
            ],
            [],
        );

        self::assertFalse($processedData['hasErrors']);
    }

    #[Framework\Attributes\Test]
    public function processPassesThroughStaticValues(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => 'bar',
                'baz.' => [
                    'hello' => 'world',
                ],
            ],
            [],
        );

        $expected = [
            'foo' => 'bar',
            'baz' => [
                'hello' => 'world',
            ],
        ];

        self::assertSame($expected, $processedData);
    }

    #[Framework\Attributes\Test]
    public function processPassesThroughNonStringValues(): void
    {
        $object = new \stdClass();

        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => true,
                'bar' => 42,
                'baz.' => [
                    'qux' => null,
                    'object' => $object,
                ],
            ],
            [],
        );

        $expected = [
            'foo' => true,
            'bar' => 42,
            'baz' => [
                'qux' => null,
                'object' => $object,
            ],
        ];

        self::assertSame($expected, $processedData);
    }

    #[Framework\Attributes\Test]
    public function processResolvesContentObjects(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => 'TEXT',
                'foo.' => [
                    'value' => 'bar',
                    'wrap' => '<p>|</p>',
                ],
            ],
            [],
        );

        self::assertSame(['foo' => '<p>bar</p>'], $processedData);
    }

    #[Framework\Attributes\Test]
    public function processResolvesNonStringValuesFromValueCollector(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'validationResults' => 'HBS_VALIDATION_RESULTS',
            ],
            [],
        );

        self::assertInstanceOf(Extbase\Error\Result::class, $processedData['validationResults']);
    }

    #[Framework\Attributes\Test]
    public function processPreservesNonStringScalarValues(): void
    {
        self::assertInstanceOf(Extbase\Mvc\ExtbaseRequestParameters::class, $this->extbaseRequestParameters);

        $this->extbaseRequestParameters->setOriginalRequestMappingResults(new Extbase\Error\Result());

        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'validation' => 'HBS_VALIDATION_RESULTS',
                'validation.' => [
                    'output.' => [
                        'hasErrors' => 'HAS_ERRORS',
                    ],
                ],
            ],
            [],
        );

        self::assertSame(['validation' => ['hasErrors' => false]], $processedData);
    }

    #[Framework\Attributes\Test]
    public function processReplacesContentPlaceholderWithRenderedFormContent(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'id' => 'HBS_TAG',
                'id.' => [
                    'attribute' => 'id',
                ],
                'content' => 'HBS_TAG',
            ],
            [],
        );

        self::assertInstanceOf(Handlebars\SafeString::class, $processedData['id']);
        self::assertSame('test-form', (string)$processedData['id']);
        self::assertInstanceOf(Handlebars\SafeString::class, $processedData['content']);
        self::assertStringNotContainsString('###FORM_CONTENT###', (string)$processedData['content']);
        self::assertStringContainsString('[__state]', (string)$processedData['content']);
    }

    #[Framework\Attributes\Test]
    public function processSkipsConfigurationBlockIfConditionEvaluatesToFalse(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo.' => [
                    'if.' => [
                        'isTrue' => 0,
                    ],
                    'bar' => 'baz',
                ],
            ],
            [],
        );

        self::assertSame([], $processedData);
    }

    #[Framework\Attributes\Test]
    public function processProcessesConfigurationBlockIfConditionEvaluatesToTrue(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo.' => [
                    'if.' => [
                        'isTrue' => 1,
                    ],
                    'bar' => 'baz',
                ],
            ],
            [],
        );

        self::assertSame(['foo' => ['bar' => 'baz']], $processedData);
    }

    #[Framework\Attributes\Test]
    public function processSkipsValueIfConditionOnResolvedValueEvaluatesToFalse(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => 'bar',
                'foo.' => [
                    'if.' => [
                        'equals' => 'baz',
                    ],
                ],
            ],
            [],
        );

        self::assertSame([], $processedData);
    }

    #[Framework\Attributes\Test]
    public function processKeepsValueIfConditionOnResolvedValueEvaluatesToTrue(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => 'bar',
                'foo.' => [
                    'if.' => [
                        'equals' => 'bar',
                    ],
                ],
            ],
            [],
        );

        self::assertSame(['foo' => 'bar'], $processedData);
    }

    /**
     * @return \Generator<string, array{string, array<string, mixed>}>
     */
    public static function processResolvesCurrentValueForConditionsDataProvider(): \Generator
    {
        yield 'condition evaluates to true' => ['bar', ['foo' => ['hello' => 'world']]];
        yield 'condition evaluates to false' => ['baz', []];
    }

    /**
     * @param array<string, mixed> $expected
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('processResolvesCurrentValueForConditionsDataProvider')]
    public function processResolvesCurrentValueForConditions(string $equals, array $expected): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo.' => [
                    'if.' => [
                        'currentValue' => 'TEXT',
                        'currentValue.' => [
                            'value' => 'bar',
                        ],
                        'value.' => [
                            'current' => 1,
                        ],
                        'equals' => $equals,
                    ],
                    'hello' => 'world',
                ],
            ],
            [],
        );

        self::assertSame($expected, $processedData);
    }

    #[Framework\Attributes\Test]
    public function processMergesTypoScriptReferences(): void
    {
        $this->cObj->setRequest(
            $this->buildServerRequest(
                typoScriptSetup: <<<'TYPOSCRIPT'
lib.foo = TEXT
lib.foo {
    value = bar
    wrap = <p>|</p>
}
TYPOSCRIPT,
            ),
        );

        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => '<lib.foo',
                'baz' => '<lib.foo',
                'baz.' => [
                    'value' => 'baz',
                ],
            ],
            [],
        );

        $expected = [
            'foo' => '<p>bar</p>',
            'baz' => '<p>baz</p>',
        ];

        self::assertSame($expected, $processedData);
    }

    #[Framework\Attributes\Test]
    public function processKeepsValuesStartingWithLessThanSignIfNoTypoScriptReferenceCanBeResolved(): void
    {
        $processedData = $this->subject->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            [
                'foo' => '<lib.missing',
                'bar' => '<strong>Hello world</strong>',
            ],
            [],
        );

        $expected = [
            'foo' => '<lib.missing',
            'bar' => '<strong>Hello world</strong>',
        ];

        self::assertSame($expected, $processedData);
    }
}
