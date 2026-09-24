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
use PHPUnit\Framework;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * LabelContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\LabelContentObject::class)]
final class LabelContentObjectTest extends TestingFramework\Core\Unit\UnitTestCase
{
    private Form\Domain\Model\FormElements\GenericFormElement $renderable;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\LabelContentObject $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->renderable = new Form\Domain\Model\FormElements\GenericFormElement('name', 'Text');
        $this->renderable->setLabel('Renderable label');
        $this->contextStack = new Src\ContentObject\Context\ContextStack();
        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->subject = new Src\ContentObject\LabelContentObject();
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsLabelOfFormFieldViewModel(): void
    {
        $this->pushContext(
            Src\Domain\ViewModel\FormFieldViewModel::forLabelAndElement(
                'View model label',
                new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
            ),
        );

        self::assertSame('View model label', $this->subject->render());
    }

    #[Framework\Attributes\Test]
    public function renderReturnsNullIfFormFieldViewModelHasNoLabel(): void
    {
        $this->pushContext(
            Src\Domain\ViewModel\FormFieldViewModel::forLabelAndElement(
                null,
                new Src\Domain\ViewModel\SimpleViewModel($this->renderable),
            ),
        );

        self::assertNull($this->valueCollector->load($this->subject->render()));
    }

    #[Framework\Attributes\Test]
    public function renderReturnsLabelOfRenderableIfViewModelIsNoFormFieldViewModel(): void
    {
        $this->pushContext(new Src\Domain\ViewModel\SimpleViewModel($this->renderable));

        self::assertSame('Renderable label', $this->subject->render());
    }

    private function pushContext(Src\Domain\ViewModel\ViewModel $viewModel): void
    {
        $this->contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $this->renderable,
                $viewModel,
                self::createStub(Fluid\Core\Rendering\RenderingContext::class),
                self::createStub(Form\Domain\Runtime\FormRuntime::class),
            ),
        );
    }
}
