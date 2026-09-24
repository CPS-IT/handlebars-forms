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
 * TranslatePropertyContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\TranslatePropertyContentObject::class)]
final class TranslatePropertyContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Src\ContentObject\TranslatePropertyContentObject $subject;

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
                        'renderingOptions' => [
                            'nextButtonLabel' => 'Next',
                        ],
                        'renderables' => [
                            [
                                'identifier' => 'name',
                                'type' => 'Text',
                                'label' => 'Name',
                                'properties' => [
                                    'placeholder' => 'Placeholder',
                                ],
                            ],
                            [
                                'identifier' => 'email',
                                'type' => 'Email',
                                'label' => 'Email',
                                'properties' => [
                                    'placeholder' => 'Email address',
                                ],
                            ],
                        ],
                    ],
                    [
                        'identifier' => 'page-2',
                        'type' => 'Page',
                        'label' => 'Page 2',
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
        $this->subject = new Src\ContentObject\TranslatePropertyContentObject(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    /**
     * @return \Generator<string, array{array<string, mixed>}>
     */
    public static function renderReturnsNullIfPropertyIsMissingOrInvalidDataProvider(): \Generator
    {
        yield 'missing property' => [[]];
        yield 'invalid property' => [['property' => ['placeholder']]];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Framework\Attributes\Test]
    #[Framework\Attributes\DataProvider('renderReturnsNullIfPropertyIsMissingOrInvalidDataProvider')]
    public function renderReturnsNullIfPropertyIsMissingOrInvalid(array $configuration): void
    {
        $this->pushContext($this->getElement('name'));

        self::assertNull($this->valueCollector->load($this->subject->render($configuration)));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsTranslatedProperty(): void
    {
        $this->pushContext($this->getElement('name'));

        self::assertSame('Translated placeholder', $this->subject->render(['property' => 'placeholder']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsUntranslatedPropertyIfNoTranslationIsAvailable(): void
    {
        $this->pushContext($this->getElement('email'));

        self::assertSame('Email address', $this->subject->render(['property' => 'placeholder']));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsTranslatedRenderingOptionIfConfigured(): void
    {
        $this->pushContext($this->formRuntime->getFormDefinition()->getPageByIndex(0));

        $actual = $this->subject->render([
            'property' => 'nextButtonLabel',
            'argumentName' => 'renderingOptionProperty',
        ]);

        self::assertSame('Translated next button label', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderFallsBackToPropertyArgumentIfConfiguredArgumentNameIsUnsupported(): void
    {
        $this->pushContext($this->getElement('name'));

        $actual = $this->subject->render([
            'property' => 'placeholder',
            'argumentName' => 'foo',
        ]);

        self::assertSame('Translated placeholder', $actual);
    }

    private function getElement(string $identifier): Form\Domain\Model\FormElements\FormElementInterface
    {
        $element = $this->formRuntime->getFormDefinition()->getElementByIdentifier($identifier);

        self::assertInstanceOf(Form\Domain\Model\FormElements\FormElementInterface::class, $element);

        return $element;
    }

    private function pushContext(Form\Domain\Model\Renderable\RootRenderableInterface $renderable): void
    {
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
