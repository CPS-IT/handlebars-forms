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
 * ContextStackTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\Context\ContextStack::class)]
final class ContextStackTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Src\ContentObject\Context\ContextStack $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->subject = new Src\ContentObject\Context\ContextStack();
    }

    #[Framework\Attributes\Test]
    public function popReturnsNullIfStackIsEmpty(): void
    {
        self::assertNull($this->subject->pop());
    }

    #[Framework\Attributes\Test]
    public function popRemovesAndReturnsContextsInReverseOrder(): void
    {
        $first = $this->createContext();
        $second = $this->createContext();

        $this->subject->push($first);
        $this->subject->push($second);

        self::assertSame($second, $this->subject->pop());
        self::assertSame($first, $this->subject->pop());
        self::assertNull($this->subject->pop());
    }

    #[Framework\Attributes\Test]
    public function currentReturnsNullIfStackIsEmpty(): void
    {
        self::assertNull($this->subject->current());
    }

    #[Framework\Attributes\Test]
    public function currentReturnsLastPushedContextWithoutRemovingIt(): void
    {
        $first = $this->createContext();
        $second = $this->createContext();

        $this->subject->push($first);
        $this->subject->push($second);

        self::assertSame($second, $this->subject->current());
        self::assertSame($second, $this->subject->current());
    }

    #[Framework\Attributes\Test]
    public function currentReturnsPreviousContextAfterPop(): void
    {
        $first = $this->createContext();
        $second = $this->createContext();

        $this->subject->push($first);
        $this->subject->push($second);
        $this->subject->pop();

        self::assertSame($first, $this->subject->current());
    }

    private function createContext(): Src\ContentObject\Context\ValueResolutionContext
    {
        return new Src\ContentObject\Context\ValueResolutionContext(
            self::createStub(Form\Domain\Model\Renderable\RootRenderableInterface::class),
            self::createStub(Src\Domain\ViewModel\ViewModel::class),
            self::createStub(Fluid\Core\Rendering\RenderingContext::class),
            self::createStub(Form\Domain\Runtime\FormRuntime::class),
        );
    }
}
