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
use EliasHaeussler\PHPUnitAttributes;
use PHPUnit\Framework;
use Psr\Http\Message;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * RenderablesContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\RenderablesContentObject::class)]
final class RenderablesContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Fluid\Core\Rendering\RenderingContext $renderingContext;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\RenderablesContentObject $subject;

    /**
     * @var list<array{array<string|int, mixed>, Form\Domain\Model\Renderable\RootRenderableInterface|null, Src\Domain\ViewModel\ViewModel|null, mixed, mixed}>
     */
    private array $processorCalls = [];

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $request = $this->buildExtbaseRequest();
        $this->get(Extbase\Configuration\ConfigurationManagerInterface::class)->setRequest($request);

        $this->initializeTypoScriptFrontendController();

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
                                'identifier' => 'fieldset',
                                'type' => 'Fieldset',
                                'label' => 'Fieldset',
                                'renderables' => [
                                    [
                                        'identifier' => 'street',
                                        'type' => 'Text',
                                        'label' => 'Street',
                                    ],
                                ],
                            ],
                            [
                                'identifier' => 'disabled',
                                'type' => 'Text',
                                'label' => 'Disabled',
                                'renderingOptions' => [
                                    'enabled' => false,
                                ],
                            ],
                            [
                                'identifier' => 'disabled-fieldset',
                                'type' => 'Fieldset',
                                'label' => 'Disabled fieldset',
                                'renderingOptions' => [
                                    'enabled' => false,
                                ],
                                'renderables' => [
                                    [
                                        'identifier' => 'city',
                                        'type' => 'Text',
                                        'label' => 'City',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'identifier' => 'summary',
                        'type' => 'SummaryPage',
                        'label' => 'Summary',
                    ],
                ],
            ],
            'standard',
            $request,
        );

        $this->cObj = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->setRequest($request);
        $this->formRuntime = $formDefinition->bind($request);
        $this->renderingContext = $this->get(Fluid\Core\Rendering\RenderingContextFactory::class)->create();
        $this->renderingContext->setAttribute(Message\ServerRequestInterface::class, $request);
        $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate(
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            'formRuntime',
            $this->formRuntime,
        );

        $this->contextStack = $this->get(Src\ContentObject\Context\ContextStack::class);
        $this->valueCollector = $this->get(Src\ContentObject\Context\ValueCollector::class);

        // Use content object from factory to test fully wired service
        $contentObject = $this->get(Frontend\ContentObject\ContentObjectFactory::class)->getContentObject(
            'HBS_RENDERABLES',
            $request,
            $this->cObj,
        );

        self::assertInstanceOf(Src\ContentObject\Context\ContextAwareContentObject::class, $contentObject);
        self::assertInstanceOf(Src\ContentObject\RenderablesContentObject::class, $contentObject->contentObject);

        $this->subject = $contentObject->contentObject;
    }

    #[Framework\Attributes\Test]
    public function renderReturnsEmptyListIfRenderableIsNotComposite(): void
    {
        $this->pushContext($this->getElement('name'));

        self::assertSame([], $this->valueCollector->load($this->subject->render(['default.' => []])));
        self::assertSame([], $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesEnabledElementsOfCurrentPageOnFormRuntime(): void
    {
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load($this->subject->render(['default.' => []]));

        self::assertSame(['name', 'fieldset'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesDirectChildrenOfSection(): void
    {
        $this->pushContext($this->getElement('fieldset'));

        $actual = $this->valueCollector->load($this->subject->render(['default.' => []]));

        self::assertSame(['street'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesEnabledRenderablesOfCompositeRenderableRecursively(): void
    {
        $this->pushContext($this->formRuntime->getFormDefinition());

        $actual = $this->valueCollector->load($this->subject->render(['default.' => []]));

        self::assertSame(['page-1', 'name', 'fieldset', 'street', 'summary'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesSummaryPageOnFormRuntime(): void
    {
        $this->formRuntime->overrideCurrentPage(1);
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load($this->subject->render(['SummaryPage.' => []]));

        self::assertSame(['summary'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesAllEnabledElementsOfFormOnSummaryPage(): void
    {
        $this->pushContext($this->formRuntime->getFormDefinition()->getPageByIndex(1));

        $actual = $this->valueCollector->load($this->subject->render(['default.' => []]));

        self::assertSame(['name', 'fieldset', 'street'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesOnlyRenderablesWithTypeSpecificConfigurationIfNoDefaultConfigurationIsGiven(): void
    {
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'Text.' => [
                    'foo' => 'bar',
                ],
            ]),
        );

        self::assertSame(['name'], $actual);
        self::assertCount(1, $this->processorCalls);
        self::assertSame(['foo' => 'bar'], $this->processorCalls[0][0]);
    }

    #[Framework\Attributes\Test]
    public function renderPrefersTypeSpecificConfigurationOverDefaultConfiguration(): void
    {
        $this->pushContext($this->formRuntime);

        $this->subject->render([
            'Text.' => [
                'type' => 'text',
            ],
            'default.' => [
                'type' => 'default',
            ],
        ]);

        self::assertCount(2, $this->processorCalls);
        self::assertSame(['type' => 'text'], $this->processorCalls[0][0]);
        self::assertSame(['type' => 'default'], $this->processorCalls[1][0]);
    }

    #[Framework\Attributes\Test]
    public function renderUsesSimpleViewModelAndEmptyConfigurationIfTypeConfigurationIsInvalid(): void
    {
        $this->pushContext($this->getElement('fieldset'));

        $this->subject->render(['Text.' => 'invalid']);

        self::assertCount(1, $this->processorCalls);
        self::assertSame([], $this->processorCalls[0][0]);
        self::assertInstanceOf(Src\Domain\ViewModel\SimpleViewModel::class, $this->processorCalls[0][2]);
    }

    #[Framework\Attributes\Test]
    public function renderBuildsViewModelUsingSupportedViewModelBuilder(): void
    {
        $this->pushContext($this->formRuntime);

        $this->subject->render(['Text.' => []]);

        self::assertCount(1, $this->processorCalls);
        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $this->processorCalls[0][2]);
        self::assertSame($this->getElement('name'), $this->processorCalls[0][2]->getRenderable());
    }

    #[Framework\Attributes\Test]
    public function renderRendersSingleContentObjectForTypeWithinRenderableContext(): void
    {
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'Text' => 'HBS_LABEL',
                'default.' => [],
            ]),
        );

        self::assertSame(['Name', 'fieldset'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderLoadsNonStringResultOfSingleContentObjectFromValueCollector(): void
    {
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load($this->subject->render(['Fieldset' => 'HBS_RENDERABLES']));

        // Nested HBS_RENDERABLES has no configuration, so it resolves to an empty list
        self::assertSame([[]], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderSkipsRenderablesWhichAreProcessedToNull(): void
    {
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'Text.' => [
                    'skip' => '1',
                ],
                'default.' => [],
            ]),
        );

        self::assertSame(['fieldset'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProvidesRegistersDuringProcessing(): void
    {
        $this->pushContext($this->formRuntime);

        $this->subject->render(['default.' => []]);

        // Disabled elements are skipped, but still counted
        self::assertSame([4, 0], array_slice($this->processorCalls[0], 3));
        self::assertSame([4, 1], array_slice($this->processorCalls[1], 3));
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~13.4.0')]
    public function renderResetsRegistersAfterProcessingOnTypo3V13(): void
    {
        $this->pushContext($this->formRuntime);

        $this->subject->render(['default.' => []]);

        self::assertNull($this->readRegister('HBS_RENDERABLES_COUNT'));
        self::assertNull($this->readRegister('HBS_RENDERABLES_CURRENT'));
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function renderKeepsLastRegisterValuesAfterProcessingOnTypo3V14(): void
    {
        $this->pushContext($this->formRuntime);

        $this->subject->render(['default.' => []]);

        // Register values cannot be removed from the register stack in TYPO3 v14
        self::assertSame(4, $this->readRegister('HBS_RENDERABLES_COUNT'));
        self::assertSame(1, $this->readRegister('HBS_RENDERABLES_CURRENT'));
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
                function (
                    array $configuration,
                    ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
                    ?Src\Domain\ViewModel\ViewModel $viewModel,
                ) {
                    $this->processorCalls[] = [
                        $configuration,
                        $renderable,
                        $viewModel,
                        $this->readRegister('HBS_RENDERABLES_COUNT'),
                        $this->readRegister('HBS_RENDERABLES_CURRENT'),
                    ];

                    if (array_key_exists('skip', $configuration)) {
                        return null;
                    }

                    return $renderable?->getIdentifier();
                },
            ),
        );
    }

    public function tearDown(): void
    {
        // Avoid leaking contexts into other tests, since context stack is a shared service
        while ($this->contextStack->pop() !== null) {
            // Intended empty loop
        }

        parent::tearDown();
    }

    private function readRegister(string $key): mixed
    {
        // Pass empty field array to avoid page record lookup, which is not available in this test
        return $this->cObj->getData('register:' . $key, []);
    }
}
