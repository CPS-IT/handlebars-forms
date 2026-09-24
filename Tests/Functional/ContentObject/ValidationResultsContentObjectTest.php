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
use Psr\Log;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * ValidationResultsContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\ValidationResultsContentObject::class)]
final class ValidationResultsContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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

    private ?Extbase\Mvc\ExtbaseRequestParameters $extbaseRequestParameters = null;
    private Extbase\Error\Result $validationResults;
    private Frontend\ContentObject\ContentObjectRenderer $cObj;
    private Form\Domain\Runtime\FormRuntime $formRuntime;
    private Src\DataProcessing\ProcessFormProcessor $processor;

    public function setUp(): void
    {
        parent::setUp();

        $this->initializeTypoScriptFrontendController();

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
                'renderingOptions' => [
                    // Avoid auto-generated honeypot elements with random identifiers
                    'honeypot' => [
                        'enable' => false,
                    ],
                ],
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
                            ],
                            [
                                'identifier' => 'comment',
                                'type' => 'Text',
                                'label' => 'Comment',
                            ],
                            [
                                'identifier' => 'phone',
                                'type' => 'Text',
                                'label' => 'Phone',
                            ],
                        ],
                    ],
                ],
            ],
            'standard',
            $request,
        );

        // Add validation results for "name" and "comment" elements as well as an unknown element
        $this->validationResults = new Extbase\Error\Result();
        $this->validationResults->forProperty('test-form.name')->addError(
            new Extbase\Error\Error('Name is missing.', 1221560718),
        );
        $this->validationResults->forProperty('test-form.comment')->addError(
            new Extbase\Error\Error('Comment is too long.', 1238108069, [5]),
        );
        $this->validationResults->forProperty('test-form.unknown')->addError(
            new Extbase\Error\Error('Unknown is missing.', 1221560718),
        );

        $this->extbaseRequestParameters->setOriginalRequestMappingResults($this->validationResults);

        $this->cObj = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->setRequest($request);
        $this->formRuntime = $formDefinition->bind($request);
        $this->processor = new Src\DataProcessing\ProcessFormProcessor(
            new Log\NullLogger(),
            $this->get(Fluid\Core\Rendering\RenderingContextFactory::class),
            $this->get(Src\Domain\ViewModel\Builder\FormViewModelBuilder::class),
            $this->get(Src\ContentObject\Context\ValueCollector::class),
            $this->get(Src\ContentObject\Context\ContextStack::class),
        );
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullOutsideOfExtbaseContext(): void
    {
        $renderingContext = $this->get(Fluid\Core\Rendering\RenderingContextFactory::class)->create();
        $renderingContext->setAttribute(Message\ServerRequestInterface::class, $this->buildServerRequest());

        $contextStack = new Src\ContentObject\Context\ContextStack();
        $contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $this->formRuntime,
                new Src\Domain\ViewModel\SimpleViewModel($this->formRuntime),
                $renderingContext,
                $this->formRuntime,
            ),
        );

        $valueCollector = new Src\ContentObject\Context\ValueCollector();
        $subject = new Src\ContentObject\ValidationResultsContentObject($this->get(Src\Fluid\ViewHelperInvoker::class));
        $subject->injectContextStack($contextStack);
        $subject->injectValueCollector($valueCollector);

        self::assertNull($valueCollector->load($subject->render()));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsValidationResultsOfRenderableIfNoOutputIsConfigured(): void
    {
        $actual = $this->process([
            'validationResults' => 'HBS_VALIDATION_RESULTS',
        ]);

        self::assertSame($this->validationResults->forProperty('test-form'), $actual['validationResults']);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesHasErrorsInstructionForEachElement(): void
    {
        $actual = $this->processFields([
            'hasErrors' => 'HBS_VALIDATION_RESULTS',
            'hasErrors.' => [
                'output' => 'HAS_ERRORS',
            ],
        ]);

        $expected = [
            ['hasErrors' => true],
            ['hasErrors' => true],
            ['hasErrors' => false],
        ];

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesErrorMessageInstructionForEachElement(): void
    {
        $actual = $this->processFields([
            'message' => 'HBS_VALIDATION_RESULTS',
            'message.' => [
                'output' => 'ERROR_MESSAGE',
            ],
        ]);

        $expected = [
            ['message' => 'This field is mandatory.'],
            ['message' => 'You must enter text which is no longer than 5 characters.'],
            ['message' => null],
        ];

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesResultInstructionWithPropertyPathForEachElement(): void
    {
        $actual = $this->processFields([
            'code' => 'HBS_VALIDATION_RESULTS',
            'code.' => [
                'output' => 'RESULT',
                'output.' => [
                    'propertyPath' => 'firstError.code',
                ],
            ],
        ]);

        $expected = [
            ['code' => 1221560718],
            ['code' => 1238108069],
            ['code' => null],
        ];

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesResultInstructionWithoutPropertyPathAsValidationResults(): void
    {
        $actual = $this->process([
            'validationResults' => 'HBS_VALIDATION_RESULTS',
            'validationResults.' => [
                'output' => 'RESULT',
            ],
        ]);

        self::assertSame($this->validationResults->forProperty('test-form'), $actual['validationResults']);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesEachErrorInstructionAsListOnElements(): void
    {
        $actual = $this->processFields([
            'errors' => 'HBS_VALIDATION_RESULTS',
            'errors.' => [
                'output' => 'EACH_ERROR',
                'output.' => [
                    'code' => 'RESULT',
                    'code.' => [
                        'propertyPath' => 'firstError.code',
                    ],
                    'message' => 'ERROR_MESSAGE',
                ],
            ],
        ]);

        $expected = [
            [
                'errors' => [
                    [
                        'code' => 1221560718,
                        'message' => 'This field is mandatory.',
                    ],
                ],
            ],
            [
                'errors' => [
                    [
                        'code' => 1238108069,
                        'message' => 'You must enter text which is no longer than 5 characters.',
                    ],
                ],
            ],
            [
                'errors' => [],
            ],
        ];

        self::assertSame($expected, $actual);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesEachErrorInstructionAsDictionaryOfResolvableElementsOnForm(): void
    {
        $actual = $this->process([
            'errors' => 'HBS_VALIDATION_RESULTS',
            'errors.' => [
                'output' => 'EACH_ERROR',
                'output.' => [
                    'message' => 'ERROR_MESSAGE',
                ],
            ],
        ]);

        $expected = [
            'name' => [
                ['message' => 'This field is mandatory.'],
            ],
            'comment' => [
                ['message' => 'You must enter text which is no longer than 5 characters.'],
            ],
        ];

        self::assertSame($expected, $actual['errors']);
    }

    #[Framework\Attributes\Test]
    public function renderResolvesEachRenderableInstructionAsDictionaryOfResolvableElementsOnForm(): void
    {
        $actual = $this->process([
            'errors' => 'HBS_VALIDATION_RESULTS',
            'errors.' => [
                'output' => 'EACH_RENDERABLE',
                'output.' => [
                    'label' => 'HBS_LABEL',
                    'message' => 'HBS_VALIDATION_RESULTS',
                    'message.' => [
                        'output' => 'ERROR_MESSAGE',
                    ],
                    'meta.' => [
                        'hasErrors' => 'HAS_ERRORS',
                    ],
                ],
            ],
        ]);

        $expected = [
            'name' => [
                'label' => 'Name',
                'message' => 'This field is mandatory.',
                'meta' => [
                    'hasErrors' => true,
                ],
            ],
            'comment' => [
                'label' => 'Comment',
                'message' => 'You must enter text which is no longer than 5 characters.',
                'meta' => [
                    'hasErrors' => true,
                ],
            ],
        ];

        self::assertSame($expected, $actual['errors']);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesGenericOutputInstructionWithinProcessFormContext(): void
    {
        $actual = $this->process([
            'text' => 'HBS_VALIDATION_RESULTS',
            'text.' => [
                'output' => 'TEXT',
                'output.' => [
                    'value' => 'foo',
                ],
            ],
        ]);

        self::assertSame('foo', $actual['text']);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesOutputConfiguration(): void
    {
        $actual = $this->process([
            'validation' => 'HBS_VALIDATION_RESULTS',
            'validation.' => [
                'output.' => [
                    'hasErrors' => 'HAS_ERRORS',
                    'nested.' => [
                        'code' => 'RESULT',
                        'code.' => [
                            'propertyPath' => 'flattenedErrors.name.0.code',
                        ],
                    ],
                ],
            ],
        ]);

        $expected = [
            'hasErrors' => true,
            'nested' => [
                'code' => 1221560718,
            ],
        ];

        self::assertSame($expected, $actual['validation']);
    }

    /**
     * @param array<string|int, mixed> $configuration
     * @return array<string|int, mixed>
     */
    private function process(array $configuration): array
    {
        return $this->processor->process(
            $this->cObj,
            ['variables.' => ['form' => $this->formRuntime]],
            $configuration,
            [],
        );
    }

    /**
     * @param array<string|int, mixed> $fieldConfiguration
     * @return list<mixed>
     */
    private function processFields(array $fieldConfiguration): array
    {
        $processedData = $this->process([
            'fields' => 'HBS_RENDERABLES',
            'fields.' => [
                'default.' => $fieldConfiguration,
            ],
        ]);

        self::assertIsList($processedData['fields']);

        return $processedData['fields'];
    }
}
