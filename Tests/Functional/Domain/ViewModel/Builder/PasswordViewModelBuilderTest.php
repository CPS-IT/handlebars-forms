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
use PHPUnit\Framework;

/**
 * PasswordViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\PasswordViewModelBuilder::class)]
final class PasswordViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\PasswordViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'password',
                'type' => 'Password',
                'label' => 'Password',
                'properties' => [
                    'elementClassAttribute' => 'form-control',
                ],
            ],
            [
                'identifier' => 'advanced-password',
                'type' => 'AdvancedPassword',
                'label' => 'Advanced password',
                'properties' => [
                    'confirmationLabel' => 'Confirm password',
                ],
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\PasswordViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForPasswordAndAdvancedPassword(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('password')));
        self::assertTrue($this->subject->supports($this->getElement('advanced-password')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedPasswordField(): void
    {
        $actual = $this->subject->build($this->getElement('password'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('input', $tag->getTagName());
        self::assertSame('password', $tag->getAttribute('type'));
        self::assertSame('test-form-password', $tag->getAttribute('id'));
        self::assertSame('form-control', $tag->getAttribute('class'));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsCollectionWithPasswordAndConfirmationFieldForAdvancedPassword(): void
    {
        $actual = $this->subject->build($this->getElement('advanced-password'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewModelCollection::class, $actual);

        $children = $actual->getChildren();

        self::assertSame(['passwordField', 'confirmationField'], array_keys($children));
        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $children['passwordField']);
        self::assertSame('test-form-advanced-password', $children['passwordField']->getTag()->getAttribute('id'));
        self::assertInstanceOf(Src\Domain\ViewModel\FormFieldViewModel::class, $children['confirmationField']);
        self::assertSame('Confirm password', $children['confirmationField']->label->getContent());
        self::assertSame('password', $children['confirmationField']->getTag()->getAttribute('type'));
        self::assertSame(
            'test-form-advanced-password-confirmation',
            $children['confirmationField']->getTag()->getAttribute('id'),
        );
    }
}
