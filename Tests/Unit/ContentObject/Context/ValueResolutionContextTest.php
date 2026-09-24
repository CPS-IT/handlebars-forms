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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\ContentObject\Context;

use CPSIT\Typo3HandlebarsForms as Src;
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * ValueResolutionContextTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\Context\ValueResolutionContext::class)]
final class ValueResolutionContextTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\Renderable\RootRenderableInterface $renderable;
    private Src\Domain\ViewModel\ViewModel $viewModel;
    private Fluid\Core\Rendering\RenderingContext $renderingContext;
    private Form\Domain\Runtime\FormRuntime $formRuntime;

    /**
     * @var list<array{array<string|int, mixed>, Form\Domain\Model\Renderable\RootRenderableInterface|null, Src\Domain\ViewModel\ViewModel|null}>
     */
    private array $processorCalls = [];

    private Src\ContentObject\Context\ValueResolutionContext $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class);
        $this->viewModel = self::createStub(Src\Domain\ViewModel\ViewModel::class);
        $this->renderingContext = self::createStub(Fluid\Core\Rendering\RenderingContext::class);
        $this->formRuntime = self::createStub(Form\Domain\Runtime\FormRuntime::class);
        $this->subject = new Src\ContentObject\Context\ValueResolutionContext(
            $this->renderable,
            $this->viewModel,
            $this->renderingContext,
            $this->formRuntime,
            function (
                array $configuration,
                ?Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
                ?Src\Domain\ViewModel\ViewModel $viewModel,
            ) {
                $this->processorCalls[] = [$configuration, $renderable, $viewModel];

                return 'processed';
            },
        );
    }

    #[Framework\Attributes\Test]
    public function processReturnsNullIfNoRenderableProcessorIsConfigured(): void
    {
        $subject = new Src\ContentObject\Context\ValueResolutionContext(
            $this->renderable,
            $this->viewModel,
            $this->renderingContext,
            $this->formRuntime,
        );

        self::assertNull($subject->process(['foo' => 'bar']));
    }

    #[Framework\Attributes\Test]
    public function processPassesDefaultArgumentsToRenderableProcessor(): void
    {
        self::assertSame('processed', $this->subject->process());
        self::assertSame([[[], null, null]], $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function processPassesGivenArgumentsToRenderableProcessor(): void
    {
        $renderable = self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class);
        $viewModel = self::createStub(Src\Domain\ViewModel\ViewModel::class);

        self::assertSame('processed', $this->subject->process(['foo' => 'bar'], $renderable, $viewModel));
        self::assertSame([[['foo' => 'bar'], $renderable, $viewModel]], $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function withRenderableReturnsNewContextWithGivenRenderable(): void
    {
        $renderable = self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class);

        $actual = $this->subject->withRenderable($renderable);

        self::assertNotSame($this->subject, $actual);
        self::assertSame($renderable, $actual->renderable);
        self::assertSame($this->viewModel, $actual->viewModel);
        self::assertSame($this->renderingContext, $actual->renderingContext);
        self::assertSame($this->formRuntime, $actual->formRuntime);
        self::assertSame($this->renderable, $this->subject->renderable);
    }

    #[Framework\Attributes\Test]
    public function withRenderableRetainsRenderableProcessor(): void
    {
        $renderable = self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class);

        self::assertSame('processed', $this->subject->withRenderable($renderable)->process());
        self::assertCount(1, $this->processorCalls);
    }

    #[Framework\Attributes\Test]
    public function withViewModelReturnsNewContextWithGivenViewModel(): void
    {
        $viewModel = self::createStub(Src\Domain\ViewModel\ViewModel::class);

        $actual = $this->subject->withViewModel($viewModel);

        self::assertNotSame($this->subject, $actual);
        self::assertSame($this->renderable, $actual->renderable);
        self::assertSame($viewModel, $actual->viewModel);
        self::assertSame($this->renderingContext, $actual->renderingContext);
        self::assertSame($this->formRuntime, $actual->formRuntime);
        self::assertSame($this->viewModel, $this->subject->viewModel);
    }

    #[Framework\Attributes\Test]
    public function withViewModelRetainsRenderableProcessor(): void
    {
        $viewModel = self::createStub(Src\Domain\ViewModel\ViewModel::class);

        self::assertSame('processed', $this->subject->withViewModel($viewModel)->process());
        self::assertCount(1, $this->processorCalls);
    }
}
