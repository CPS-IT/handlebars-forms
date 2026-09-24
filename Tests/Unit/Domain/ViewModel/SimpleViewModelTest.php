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

/**
 * SimpleViewModelTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\SimpleViewModel::class)]
final class SimpleViewModelTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('name', 'Text');
    }

    #[Framework\Attributes\Test]
    public function getRenderableReturnsRenderable(): void
    {
        $subject = new Src\Domain\ViewModel\SimpleViewModel($this->renderable);

        self::assertSame($this->renderable, $subject->getRenderable());
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesContextAsArrayValues(): void
    {
        $subject = new Src\Domain\ViewModel\SimpleViewModel($this->renderable, ['foo' => 'bar']);

        self::assertSame('bar', $subject['foo']);
        self::assertSame(['foo' => 'bar'], $subject->getArrayCopy());
    }

    #[Framework\Attributes\Test]
    public function constructorProvidesEmptyContextByDefault(): void
    {
        $subject = new Src\Domain\ViewModel\SimpleViewModel($this->renderable);

        self::assertSame([], $subject->getArrayCopy());
    }
}
