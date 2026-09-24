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
use TYPO3\CMS\Form;

/**
 * FieldsetViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\FieldsetViewModelBuilder::class)]
final class FieldsetViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\FieldsetViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'fieldset',
                'type' => 'Fieldset',
                'label' => 'Fieldset',
                'properties' => [
                    'elementClassAttribute' => 'form-fieldset',
                    'fluidAdditionalAttributes' => [
                        'data-foo' => 'bar',
                    ],
                ],
            ],
            [
                'identifier' => 'fieldset-without-class',
                'type' => 'Fieldset',
                'label' => 'Fieldset without class',
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\FieldsetViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForFieldset(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('fieldset', Form\Domain\Model\FormElements\FormElementInterface::class)));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithFieldsetTag(): void
    {
        $actual = $this->subject->build($this->getElement('fieldset', Form\Domain\Model\FormElements\Section::class), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\StandaloneTagViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('fieldset', $tag->getTagName());
        self::assertSame(['data-foo' => 'bar', 'class' => 'form-fieldset'], $tag->getAttributes());
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithFieldsetTagWithDefaultClassAndWithoutAdditionalAttributes(): void
    {
        $element = $this->getElement('fieldset-without-class', Form\Domain\Model\FormElements\Section::class);

        $actual = $this->subject->build($element, $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\StandaloneTagViewModel::class, $actual);
        // Default value of "elementClassAttribute" is defined in EXT:form's Fieldset prototype (differs between TYPO3 versions)
        self::assertSame(
            ['class' => $element->getProperties()['elementClassAttribute']],
            $actual->getTag()->getAttributes(),
        );
    }
}
