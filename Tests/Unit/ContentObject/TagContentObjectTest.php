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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\ContentObject;

use CPSIT\Typo3HandlebarsForms as Src;
use DevTheorem\Handlebars;
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * TagContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\TagContentObject::class)]
final class TagContentObjectTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\Renderable\RootRenderableInterface $renderable;
    private FluidStandalone\Core\ViewHelper\TagBuilder $tag;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\TagContentObject $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class);
        $this->tag = new FluidStandalone\Core\ViewHelper\TagBuilder('input', 'foo');
        $this->tag->addAttribute('id', 'bar');
        $this->contextStack = new Src\ContentObject\Context\ContextStack();
        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->subject = new Src\ContentObject\TagContentObject();
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfViewModelIsNotTagAware(): void
    {
        $this->pushContext(self::createStub(Src\Domain\ViewModel\ViewModel::class));

        self::assertNull($this->valueCollector->load($this->subject->render()));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsTagContentIfNoAttributeIsConfigured(): void
    {
        $this->pushContext();

        self::assertEquals(
            new Handlebars\SafeString('foo'),
            $this->valueCollector->load($this->subject->render()),
        );
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfNoAttributeIsConfiguredAndTagHasNoContent(): void
    {
        $this->tag->setContent(null);
        $this->pushContext();

        self::assertNull($this->valueCollector->load($this->subject->render()));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsConfiguredTagAttribute(): void
    {
        $this->pushContext();

        self::assertEquals(
            new Handlebars\SafeString('bar'),
            $this->valueCollector->load($this->subject->render(['attribute' => 'id'])),
        );
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfConfiguredTagAttributeDoesNotExist(): void
    {
        $this->pushContext();

        self::assertNull($this->valueCollector->load($this->subject->render(['attribute' => 'name'])));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfConfiguredAttributeIsInvalid(): void
    {
        $this->pushContext();

        self::assertNull($this->valueCollector->load($this->subject->render(['attribute' => ['id']])));
    }

    private function pushContext(?Src\Domain\ViewModel\ViewModel $viewModel = null): void
    {
        $this->contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $this->renderable,
                $viewModel ?? new Src\Domain\ViewModel\StandaloneTagViewModel($this->renderable, $this->tag),
                self::createStub(Fluid\Core\Rendering\RenderingContext::class),
                self::createStub(Form\Domain\Runtime\FormRuntime::class),
            ),
        );
    }
}
