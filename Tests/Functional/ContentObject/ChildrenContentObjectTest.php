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
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * ChildrenContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\ChildrenContentObject::class)]
final class ChildrenContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\ChildrenContentObject $subject;

    /**
     * @var list<array{array<string|int, mixed>, Src\Domain\ViewModel\ViewModel|null, mixed, mixed}>
     */
    private array $processorCalls = [];

    public function setUp(): void
    {
        parent::setUp();

        $request = $this->buildServerRequest();

        $this->initializeTypoScriptFrontendController();

        $this->cObj = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->setRequest($request);
        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('name', 'Text');
        $this->contextStack = new Src\ContentObject\Context\ContextStack();
        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->subject = new Src\ContentObject\ChildrenContentObject();
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
        $this->subject->setRequest($request);
        $this->subject->setContentObjectRenderer($this->cObj);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfViewModelIsNotComposite(): void
    {
        $this->pushContext(new Src\Domain\ViewModel\SimpleViewModel($this->renderable));

        self::assertNull($this->valueCollector->load($this->subject->render()));
        self::assertSame([], $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfViewModelHasNoChildren(): void
    {
        $this->pushContext(new Src\Domain\ViewModel\ViewModelCollection($this->renderable, []));

        self::assertNull($this->valueCollector->load($this->subject->render()));
        self::assertSame([], $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function renderProcessesConfigurationForEachChildViewModel(): void
    {
        $first = new Src\Domain\ViewModel\SimpleViewModel($this->renderable, ['foo' => 'first']);
        $second = new Src\Domain\ViewModel\SimpleViewModel($this->renderable, ['foo' => 'second']);

        $this->pushContext(
            new Src\Domain\ViewModel\ViewModelCollection($this->renderable, ['a' => $first, 'b' => $second]),
        );

        $actual = $this->valueCollector->load($this->subject->render(['foo' => 'bar']));

        self::assertSame([['foo' => 'bar'], ['foo' => 'bar']], $actual);
        self::assertSame(
            [
                [['foo' => 'bar'], $first, 2, 0],
                [['foo' => 'bar'], $second, 2, 1],
            ],
            $this->processorCalls,
        );
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~13.4.0')]
    public function renderResetsRegistersAfterChildViewModelsAreProcessedOnTypo3V13(): void
    {
        $this->pushContext(
            new Src\Domain\ViewModel\ViewModelCollection(
                $this->renderable,
                [new Src\Domain\ViewModel\SimpleViewModel($this->renderable)],
            ),
        );

        $this->subject->render();

        self::assertNull($this->readRegister('HBS_CHILDREN_COUNT'));
        self::assertNull($this->readRegister('HBS_CHILDREN_CURRENT'));
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function renderKeepsLastRegisterValuesAfterChildViewModelsAreProcessedOnTypo3V14(): void
    {
        $this->pushContext(
            new Src\Domain\ViewModel\ViewModelCollection(
                $this->renderable,
                [new Src\Domain\ViewModel\SimpleViewModel($this->renderable)],
            ),
        );

        $this->subject->render();

        // Register values cannot be removed from the register stack in TYPO3 v14
        self::assertSame(1, $this->readRegister('HBS_CHILDREN_COUNT'));
        self::assertSame(0, $this->readRegister('HBS_CHILDREN_CURRENT'));
    }

    private function pushContext(Src\Domain\ViewModel\ViewModel $viewModel): void
    {
        $this->contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $this->renderable,
                $viewModel,
                self::createStub(Fluid\Core\Rendering\RenderingContext::class),
                self::createStub(Form\Domain\Runtime\FormRuntime::class),
                function (
                    array $configuration,
                    ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
                    ?Src\Domain\ViewModel\ViewModel $viewModel,
                ) {
                    $this->processorCalls[] = [
                        $configuration,
                        $viewModel,
                        $this->readRegister('HBS_CHILDREN_COUNT'),
                        $this->readRegister('HBS_CHILDREN_CURRENT'),
                    ];

                    return $configuration;
                },
            ),
        );
    }

    private function readRegister(string $key): mixed
    {
        // Pass empty field array to avoid page record lookup, which is not available in this test
        return $this->cObj->getData('register:' . $key, []);
    }
}
