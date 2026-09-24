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
 * TranslateErrorContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\TranslateErrorContentObject::class)]
final class TranslateErrorContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Form\Domain\Runtime\FormRuntime $formRuntime;
    private Fluid\Core\Rendering\RenderingContext $renderingContext;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\TranslateErrorContentObject $subject;

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
                'renderingOptions' => [
                    'translation' => [
                        'translationFiles' => [
                            100 => 'EXT:handlebars_forms/Tests/Functional/Fixtures/Resources/Private/Language/locallang.xlf',
                        ],
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
        $this->subject = new Src\ContentObject\TranslateErrorContentObject(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public static function renderReturnsNullIfErrorCodeIsMissingOrInvalidDataProvider(): \Generator
    {
        yield 'missing error code' => [[]];
        yield 'non-numeric error code' => [['errorCode' => 'foo']];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderReturnsNullIfErrorCodeIsMissingOrInvalidDataProvider')]
    public function renderReturnsNullIfErrorCodeIsMissingOrInvalid(array $configuration): void
    {
        $this->pushContext('email');

        self::assertNull($this->valueCollector->load($this->subject->render($configuration)));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsTranslatedErrorMessage(): void
    {
        $this->pushContext('email');

        self::assertSame('This field is mandatory.', $this->subject->render(['errorCode' => '1221560718']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsElementSpecificTranslatedErrorMessage(): void
    {
        $this->pushContext('name');

        self::assertSame('Please enter your name.', $this->subject->render(['errorCode' => 1221560718]));
    }

    #[Framework\Attributes\Test]
    public function renderUsesArgumentsOfMatchingValidationErrorFromRequest(): void
    {
        self::assertInstanceOf(Extbase\Mvc\ExtbaseRequestParameters::class, $this->extbaseRequestParameters);

        $validationResults = new Extbase\Error\Result();
        $validationResults->forProperty('test-form.email')->addError(
            new Extbase\Error\Error('Something went wrong.', 1234567890),
        );
        $validationResults->forProperty('test-form.email')->addError(
            new Extbase\Error\Error('Text is too long.', 1238108069, [5]),
        );

        $this->extbaseRequestParameters->setOriginalRequestMappingResults($validationResults);
        $this->pushContext('email');

        self::assertSame(
            'You must enter text which is no longer than 5 characters.',
            $this->subject->render(['errorCode' => 1238108069]),
        );
    }

    #[Framework\Attributes\Test]
    public function renderReturnsEmptyStringIfErrorCodeCannotBeTranslated(): void
    {
        $this->pushContext('email');

        self::assertSame('', $this->subject->render(['errorCode' => 1234567890]));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsTranslatedErrorMessageOutsideOfExtbaseContext(): void
    {
        $this->renderingContext->setAttribute(Message\ServerRequestInterface::class, $this->buildServerRequest());
        $this->pushContext('email');

        self::assertSame('This field is mandatory.', $this->subject->render(['errorCode' => 1221560718]));
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
            ),
        );
    }
}
