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
 * NavigationContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\NavigationContentObject::class)]
final class NavigationContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Src\ContentObject\NavigationContentObject $subject;

    /**
     * @var list<array{array<string|int, mixed>, Form\Domain\Model\Renderable\RootRenderableInterface|null, Src\Domain\ViewModel\ViewModel|null}>
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
                'renderingOptions' => [
                    'submitButtonLabel' => 'Send',
                ],
                'renderables' => [
                    [
                        'identifier' => 'page-1',
                        'type' => 'Page',
                        'label' => 'Page 1',
                        'renderingOptions' => [
                            'nextButtonLabel' => 'Continue',
                        ],
                    ],
                    [
                        'identifier' => 'page-2',
                        'type' => 'Page',
                        'label' => 'Page 2',
                        'renderingOptions' => [
                            'previousButtonLabel' => 'Back',
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
        $this->subject = new Src\ContentObject\NavigationContentObject(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsEmptyListIfRenderableIsNoFormRuntime(): void
    {
        $this->pushContext($this->formRuntime->getFormDefinition()->getPageByIndex(0));

        self::assertSame([], $this->valueCollector->load($this->subject->render()));
        self::assertSame([], $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesNextPageOnFirstPage(): void
    {
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load($this->subject->render());

        self::assertSame(['page-2'], $actual);
        self::assertCount(1, $this->processorCalls);
        self::assertSame([], $this->processorCalls[0][0]);
        self::assertInstanceOf(Src\Domain\ViewModel\SimpleViewModel::class, $this->processorCalls[0][2]);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesPreviousPageAndSubmitOnLastPage(): void
    {
        $this->formRuntime->overrideCurrentPage(1);
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load($this->subject->render());

        self::assertSame(['page-1', 'test-form'], $actual);
        self::assertCount(2, $this->processorCalls);
        self::assertSame($this->formRuntime, $this->processorCalls[1][1]);
    }

    #[Framework\Attributes\Test]
    public function renderBuildsButtonViewModelForConfiguredNextPage(): void
    {
        $this->pushContext($this->formRuntime);

        $this->subject->render([
            'nextPage.' => [
                'foo' => 'bar',
            ],
        ]);

        self::assertCount(1, $this->processorCalls);
        self::assertSame(['foo' => 'bar'], $this->processorCalls[0][0]);
        self::assertButtonViewModel('Continue', '1', $this->processorCalls[0][2]);
    }

    #[Framework\Attributes\Test]
    public function renderBuildsButtonViewModelsForConfiguredPreviousPageAndSubmit(): void
    {
        $this->formRuntime->overrideCurrentPage(1);
        $this->pushContext($this->formRuntime);

        $this->subject->render([
            'previousPage.' => [
                'foo' => 'bar',
            ],
            'submit.' => [
                'baz' => 'qux',
            ],
        ]);

        self::assertCount(2, $this->processorCalls);
        self::assertSame(['foo' => 'bar'], $this->processorCalls[0][0]);
        self::assertButtonViewModel('Back', '0', $this->processorCalls[0][2]);
        self::assertSame(['baz' => 'qux'], $this->processorCalls[1][0]);
        self::assertButtonViewModel('Send', '2', $this->processorCalls[1][2]);
    }

    #[Framework\Attributes\Test]
    public function renderSkipsElementsWhichAreProcessedToNull(): void
    {
        $this->formRuntime->overrideCurrentPage(1);
        $this->pushContext($this->formRuntime);

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'previousPage.' => [
                    'skip' => '1',
                ],
            ]),
        );

        self::assertSame(['test-form'], $actual);
        self::assertCount(2, $this->processorCalls);
    }

    private static function assertButtonViewModel(
        string $expectedLabel,
        string $expectedValue,
        ?Src\Domain\ViewModel\ViewModel $viewModel,
    ): void {
        self::assertInstanceOf(Src\Domain\ViewModel\FormFieldViewModel::class, $viewModel);
        self::assertSame($expectedLabel, $viewModel->label->getContent());
        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $viewModel->element);
        self::assertSame($expectedValue, $viewModel->element->getTag()->getAttribute('value'));
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
                    $this->processorCalls[] = [$configuration, $renderable, $viewModel];

                    if (array_key_exists('skip', $configuration)) {
                        return null;
                    }

                    return $renderable?->getIdentifier();
                },
            ),
        );
    }
}
