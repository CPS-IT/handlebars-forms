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
 * CountrySelectViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\CountrySelectViewModelBuilder::class)]
final class CountrySelectViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\CountrySelectViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'country',
                'type' => 'CountrySelect',
                'label' => 'Country',
                'properties' => [
                    'elementClassAttribute' => 'form-select',
                    'prependOptionLabel' => 'Please choose',
                    'prependOptionValue' => '',
                    'onlyCountries' => ['DE', 'AT', 'CH'],
                    'excludeCountries' => ['CH'],
                ],
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\CountrySelectViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForCountrySelect(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('country')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedCountrySelect(): void
    {
        $actual = $this->subject->build($this->getElement('country'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);
        self::assertIsString($actual->viewHelperInvocationResult->content);

        $tag = $actual->getTag();
        $content = $actual->viewHelperInvocationResult->content;

        self::assertSame('select', $tag->getTagName());
        self::assertSame('test-form-country', $tag->getAttribute('id'));
        self::assertSame('form-select', $tag->getAttribute('class'));
        self::assertStringContainsString('<option value="">Please choose</option>', $content);
        self::assertStringContainsString('value="AT"', $content);
        self::assertStringContainsString('value="DE"', $content);
        self::assertStringNotContainsString('value="CH"', $content);
        self::assertStringNotContainsString('value="FR"', $content);
        self::assertLessThan(strpos($content, 'value="DE"'), strpos($content, 'value="AT"'));
    }
}
