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
 * HiddenViewModelBuilderTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\ViewModel\Builder\HiddenViewModelBuilder::class)]
final class HiddenViewModelBuilderTest extends ViewModelBuilderTestCase
{
    private Src\Domain\ViewModel\Builder\HiddenViewModelBuilder $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->buildFormRuntime([
            [
                'identifier' => 'hidden',
                'type' => 'Hidden',
                'label' => 'Hidden',
                'defaultValue' => 'foo',
                'properties' => [
                    'elementClassAttribute' => 'hidden-field',
                ],
            ],
            [
                'identifier' => 'honeypot',
                'type' => 'Honeypot',
                'label' => 'Honeypot',
            ],
            [
                'identifier' => 'name',
                'type' => 'Text',
                'label' => 'Name',
            ],
        ]);

        $this->subject = new Src\Domain\ViewModel\Builder\HiddenViewModelBuilder(
            $this->get(Src\Fluid\ViewHelperInvoker::class),
        );
    }

    #[Framework\Attributes\Test]
    public function supportsReturnsTrueForHiddenAndHoneypot(): void
    {
        self::assertTrue($this->subject->supports($this->getElement('hidden')));
        self::assertTrue($this->subject->supports($this->getElement('honeypot')));
        self::assertFalse($this->subject->supports($this->getElement('name')));
    }

    #[Framework\Attributes\Test]
    public function buildReturnsViewModelWithRenderedHiddenField(): void
    {
        $actual = $this->subject->build($this->getElement('hidden'), $this->renderingContext);

        self::assertInstanceOf(Src\Domain\ViewModel\ViewHelperContainedViewModel::class, $actual);

        $tag = $actual->getTag();

        self::assertSame('input', $tag->getTagName());
        self::assertSame('hidden', $tag->getAttribute('type'));
        self::assertSame('test-form-hidden', $tag->getAttribute('id'));
        self::assertSame('hidden-field', $tag->getAttribute('class'));
    }
}
