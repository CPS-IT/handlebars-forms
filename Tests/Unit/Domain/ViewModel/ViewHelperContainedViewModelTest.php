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

namespace CPSIT\Typo3HandlebarsForms\Tests\Unit\Domain\ViewModel;

use CPSIT\Typo3HandlebarsForms as Src;
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewHelperContainedViewModelTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\ViewHelperContainedViewModel::class)]
final class ViewHelperContainedViewModelTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;
    private FluidStandalone\Core\ViewHelper\TagBuilder $tag;
    private Src\Fluid\ViewHelperInvocationResult $viewHelperInvocationResult;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('name', 'Text');
        $this->tag = new FluidStandalone\Core\ViewHelper\TagBuilder('input');
        $this->tag->addAttribute('name', 'foo');
        $this->viewHelperInvocationResult = new Src\Fluid\ViewHelperInvocationResult(
            self::createStub(FluidStandalone\Core\ViewHelper\ViewHelperInterface::class),
            self::createStub(Fluid\Core\Rendering\RenderingContext::class),
            '<input name="foo" />',
            $this->tag,
        );
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesTagAttributesAsArrayValues(): void
    {
        $subject = new Src\Domain\ViewModel\ViewHelperContainedViewModel($this->renderable, $this->viewHelperInvocationResult);

        self::assertSame(['name' => 'foo'], $subject->getArrayCopy());
    }

    #[Framework\Attributes\Test]
    public function getRenderableReturnsRenderable(): void
    {
        $subject = new Src\Domain\ViewModel\ViewHelperContainedViewModel($this->renderable, $this->viewHelperInvocationResult);

        self::assertSame($this->renderable, $subject->getRenderable());
    }

    #[Framework\Attributes\Test]
    public function getChildrenReturnsEmptyListByDefault(): void
    {
        $subject = new Src\Domain\ViewModel\ViewHelperContainedViewModel($this->renderable, $this->viewHelperInvocationResult);

        self::assertSame([], $subject->getChildren());
    }

    #[Framework\Attributes\Test]
    public function getChildrenReturnsGivenChildren(): void
    {
        $children = [
            new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
        ];

        $subject = new Src\Domain\ViewModel\ViewHelperContainedViewModel(
            $this->renderable,
            $this->viewHelperInvocationResult,
            $children,
        );

        self::assertSame($children, $subject->getChildren());
    }

    #[Framework\Attributes\Test]
    public function getTagReturnsTagOfViewHelperInvocationResult(): void
    {
        $subject = new Src\Domain\ViewModel\ViewHelperContainedViewModel($this->renderable, $this->viewHelperInvocationResult);

        self::assertSame($this->tag, $subject->getTag());
    }
}
