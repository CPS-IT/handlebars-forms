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
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewModelCollectionTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\ViewModelCollection::class)]
final class ViewModelCollectionTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('colors', 'MultiCheckbox');
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesViewModelsAsArrayValues(): void
    {
        $viewModels = [
            'foo' => new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
        ];

        $subject = new Src\Domain\ViewModel\ViewModelCollection($this->renderable, $viewModels);

        self::assertSame($viewModels, $subject->getArrayCopy());
    }

    #[Framework\Attributes\Test]
    public function getRenderableReturnsRenderable(): void
    {
        $subject = new Src\Domain\ViewModel\ViewModelCollection($this->renderable, []);

        self::assertSame($this->renderable, $subject->getRenderable());
    }

    #[Framework\Attributes\Test]
    public function getChildrenReturnsViewModels(): void
    {
        $viewModels = [
            new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
        ];

        $subject = new Src\Domain\ViewModel\ViewModelCollection($this->renderable, $viewModels);

        self::assertSame($viewModels, $subject->getChildren());
    }

    #[Framework\Attributes\Test]
    public function getTagReturnsTagOfFirstTagAwareViewModel(): void
    {
        $first = new FluidStandalone\Core\ViewHelper\TagBuilder('input');
        $second = new FluidStandalone\Core\ViewHelper\TagBuilder('input');

        $subject = new Src\Domain\ViewModel\ViewModelCollection(
            $this->renderable,
            [
                new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
                new Src\Domain\ViewModel\StandaloneTagViewModel($this->renderable, $first),
                new Src\Domain\ViewModel\StandaloneTagViewModel($this->renderable, $second),
            ],
        );

        self::assertSame($first, $subject->getTag());
    }

    #[Framework\Attributes\Test]
    public function getTagReturnsEmptyTagIfNoViewModelIsTagAware(): void
    {
        $subject = new Src\Domain\ViewModel\ViewModelCollection(
            $this->renderable,
            [
                new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
            ],
        );

        self::assertEquals(new FluidStandalone\Core\ViewHelper\TagBuilder(), $subject->getTag());
    }
}
