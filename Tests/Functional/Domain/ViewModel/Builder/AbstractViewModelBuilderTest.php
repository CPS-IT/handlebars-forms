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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\Domain\ViewModel\Builder;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use PHPUnit\Framework;
use TYPO3\CMS\Form;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * AbstractViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\AbstractViewModelBuilder::class)]
final class AbstractViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Tests\Functional\Fixtures\Classes\DummyViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
                'properties' => [
                    'fluidAdditionalAttributes' => [
                        'placeholder' => 'Enter your name',
                    ],
                ],
            ],
            [
                'identifier' => 'email',
                'type' => 'Email',
                'label' => 'Email',
            ],
            [
                'identifier' => 'row',
                'type' => 'GridRow',
                'label' => 'Row',
                'renderables' => [
                    [
                        'identifier' => 'first-name',
                        'type' => 'Text',
                        'label' => 'First name',
                    ],
                    [
                        'identifier' => 'last-name',
                        'type' => 'Text',
                        'label' => 'Last name',
                    ],
                ],
            ],
        ]);

        $this->subject = new Tests\Functional\Fixtures\Classes\DummyViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForSupportedType(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsFalseForUnsupportedType(): void
    {
        self::assertFalse($this->subject->supports($this->getElement('email')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelOfRenderedRenderable(): void
    {
        $element = $this->getElement('name');
        $viewModel = new Src\Domain\ViewModel\SimpleViewModel($element);

        $this->subject->viewModel = $viewModel;

        self::assertSame($viewModel, $this->subject->build($element, $this->renderingContext));
    }

    #[Framework\Attributes\Test]
    public function buildFallsBackToViewModelOfRenderRenderableViewHelperIfRenderableIsNotRendered(): void
    {
        $element = $this->getElement('name');

        $actual = $this->subject->build($element, $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);
        self::assertSame($element, $actual->getRenderable());
        self::assertInstanceOf(
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            $actual->viewHelperInvocationResult->viewHelper,
        );
    }

    #[Framework\Attributes\Test]
    public function buildAppliesGridColumnClassesToTagOfRenderableWithinGridRow(): void
    {
        $element = $this->getElement('first-name');
        $tag = new FluidStandalone\Core\ViewHelper\TagBuilder('input');
        $tag->addAttribute('class', 'form-control');

        $this->subject->viewModel = new Src\Domain\ViewModel\StandaloneTagViewModel($element, $tag);
        $this->subject->build($element, $this->renderingContext);

        self::assertSame('form-control col-6 col-sm-6 col-md-6 col-lg-6 col-xl-6 col-xxl-6', $tag->getAttribute('class'));
    }

    #[Framework\Attributes\Test]
    public function buildDoesNotApplyGridColumnClassesToRenderableOutsideOfGridRow(): void
    {
        $element = $this->getElement('name');
        $tag = new FluidStandalone\Core\ViewHelper\TagBuilder('input');
        $tag->addAttribute('class', 'form-control');

        $this->subject->viewModel = new Src\Domain\ViewModel\StandaloneTagViewModel($element, $tag);
        $this->subject->build($element, $this->renderingContext);

        self::assertSame('form-control', $tag->getAttribute('class'));
    }

    #[Framework\Attributes\Test]
    public function buildDoesNotApplyGridColumnClassesToRootRenderable(): void
    {
        $tag = new FluidStandalone\Core\ViewHelper\TagBuilder('form');

        $this->subject->viewModel = new Src\Domain\ViewModel\StandaloneTagViewModel($this->formRuntime, $tag);
        $this->subject->build($this->formRuntime, $this->renderingContext);

        self::assertNull($tag->getAttribute('class'));
    }

    #[Framework\Attributes\Test]
    public function buildIgnoresGridColumnClassesIfViewModelIsNotTagAware(): void
    {
        $element = $this->getElement('first-name');
        $viewModel = new Src\Domain\ViewModel\SimpleViewModel($element);

        $this->subject->viewModel = $viewModel;

        self::assertSame($viewModel, $this->subject->build($element, $this->renderingContext));
    }

    #[Framework\Attributes\Test]
    public function renderAdditionalAttributesReturnsTranslatedAdditionalAttributes(): void
    {
        $actual = $this->subject->callRenderAdditionalAttributes($this->getElement('name'), $this->renderingContext);

        self::assertSame(['placeholder' => 'Enter your name'], $actual);
    }

    #[Framework\Attributes\Test]
    public function renderAdditionalAttributesReturnsEmptyArrayIfNoAdditionalAttributesAreConfigured(): void
    {
        $actual = $this->subject->callRenderAdditionalAttributes($this->getElement('email'), $this->renderingContext);

        self::assertSame([], $actual);
    }
}
