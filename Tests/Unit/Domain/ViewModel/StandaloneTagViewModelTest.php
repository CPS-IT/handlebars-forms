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
 * StandaloneTagViewModelTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\StandaloneTagViewModel::class)]
final class StandaloneTagViewModelTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;
    private FluidStandalone\Core\ViewHelper\TagBuilder $tag;
    private Src\Domain\ViewModel\StandaloneTagViewModel $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('name', 'Text');
        $this->tag = new FluidStandalone\Core\ViewHelper\TagBuilder('input');
        $this->tag->addAttribute('name', 'foo');
        $this->subject = new Src\Domain\ViewModel\StandaloneTagViewModel($this->renderable, $this->tag);
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesTagAttributesAsArrayValues(): void
    {
        self::assertSame(['name' => 'foo'], $this->subject->getArrayCopy());
    }

    #[Framework\Attributes\Test]
    public function getRenderableReturnsRenderable(): void
    {
        self::assertSame($this->renderable, $this->subject->getRenderable());
    }

    #[Framework\Attributes\Test]
    public function getTagReturnsTag(): void
    {
        self::assertSame($this->tag, $this->subject->getTag());
    }
}
