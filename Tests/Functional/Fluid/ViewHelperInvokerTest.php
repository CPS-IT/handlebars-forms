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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\Fluid;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use PHPUnit\Framework;
use Psr\Http\Message;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewHelperInvokerTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Fluid\ViewHelperInvoker::class)]
final class ViewHelperInvokerTest extends TestingFramework\Core\Functional\FunctionalTestCase
{
    use Tests\FrontendRequestTrait;

    protected array $coreExtensionsToLoad = [
        'form',
    ];

    protected array $testExtensionsToLoad = [
        'handlebars',
        'handlebars_forms',
        'typed_extconf',
    ];

    private Extbase\Mvc\Request $request;
    private Fluid\Core\Rendering\RenderingContext $renderingContext;
    private Src\Fluid\ViewHelperInvoker $subject;

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $this->request = $this->buildExtbaseRequest();
        $this->get(Extbase\Configuration\ConfigurationManagerInterface::class)->setRequest($this->request);

        $this->renderingContext = $this->get(Fluid\Core\Rendering\RenderingContextFactory::class)->create();
        $this->renderingContext->setAttribute(Message\ServerRequestInterface::class, $this->request);
        $this->subject = $this->get(Src\Fluid\ViewHelperInvoker::class);
    }

    #[Framework\Attributes\Test]
    public function invokeRendersViewHelperWithGivenArguments(): void
    {
        $actual = $this->subject->invoke(
            $this->renderingContext,
            FluidStandalone\ViewHelpers\Format\CaseViewHelper::class,
            [
                'value' => 'foo',
                'mode' => 'upper',
            ],
        );

        self::assertSame('FOO', $actual->content);
        self::assertInstanceOf(FluidStandalone\ViewHelpers\Format\CaseViewHelper::class, $actual->viewHelper);
        self::assertSame($this->renderingContext, $actual->renderingContext);
    }

    #[Framework\Attributes\Test]
    public function invokeUsesClosureToRenderChildren(): void
    {
        $actual = $this->subject->invoke(
            $this->renderingContext,
            FluidStandalone\ViewHelpers\Format\CaseViewHelper::class,
            [],
            static fn() => 'foo',
        );

        self::assertSame('FOO', $actual->content);
    }

    #[Framework\Attributes\Test]
    public function invokeRendersEmptyChildrenIfNoClosureIsGiven(): void
    {
        $actual = $this->subject->invoke(
            $this->renderingContext,
            FluidStandalone\ViewHelpers\Format\CaseViewHelper::class,
        );

        self::assertSame('', $actual->content);
    }

    #[Framework\Attributes\Test]
    public function invokeReturnsUntouchedTagBuilderForNonTagBasedViewHelpers(): void
    {
        $actual = $this->subject->invoke(
            $this->renderingContext,
            FluidStandalone\ViewHelpers\Format\CaseViewHelper::class,
            [
                'value' => 'foo',
            ],
        );

        self::assertSame('', $actual->tag->getTagName());
        self::assertSame([], $actual->tag->getAttributes());
    }

    #[Framework\Attributes\Test]
    public function invokeInjectsTagBuilderIntoTagBasedViewHelperAndPassesItToClosure(): void
    {
        $closureTag = null;

        $actual = $this->subject->invoke(
            $this->renderingContext,
            Fluid\ViewHelpers\Link\ExternalViewHelper::class,
            [
                'uri' => 'https://example.com',
            ],
            static function (?FluidStandalone\Core\ViewHelper\TagBuilder $tag = null) use (&$closureTag) {
                $closureTag = $tag;

                return 'Example';
            },
        );

        self::assertSame('<a href="https://example.com">Example</a>', $actual->content);
        self::assertSame($actual->tag, $closureTag);
        self::assertSame('a', $actual->tag->getTagName());
        self::assertSame('https://example.com', $actual->tag->getAttribute('href'));
    }

    #[Framework\Attributes\Test]
    public function translateElementPropertyReturnsTranslatedProperty(): void
    {
        $formRuntime = $this->buildFormRuntime();
        $element = $formRuntime->getFormDefinition()->getElementByIdentifier('name');

        self::assertInstanceOf(Form\Domain\Model\FormElements\FormElementInterface::class, $element);

        $actual = $this->subject->translateElementProperty($this->renderingContext, $element, 'placeholder');

        self::assertSame('Translated placeholder', $actual);
    }

    #[Framework\Attributes\Test]
    public function translateElementPropertyReturnsTranslatedRenderingOptionProperty(): void
    {
        $formRuntime = $this->buildFormRuntime();
        $page = $formRuntime->getFormDefinition()->getPageByIndex(0);

        $actual = $this->subject->translateElementProperty(
            $this->renderingContext,
            $page,
            'nextButtonLabel',
            'renderingOptionProperty',
        );

        self::assertSame('Translated next button label', $actual);
    }

    private function buildFormRuntime(): Form\Domain\Runtime\FormRuntime
    {
        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
            [
                'identifier' => 'test-form',
                'type' => 'Form',
                'prototypeName' => 'standard',
                'label' => 'Test form',
                'renderingOptions' => [
                    'translation' => [
                        'translationFiles' => [
                            100 => 'EXT:handlebars_forms/Tests/Functional/Fixtures/Resources/Private/Language/locallang.xlf',
                        ],
                    ],
                ],
                'renderables' => [
                    [
                        'identifier' => 'page-1',
                        'type' => 'Page',
                        'label' => 'Page 1',
                        'renderingOptions' => [
                            'nextButtonLabel' => 'Next',
                        ],
                        'renderables' => [
                            [
                                'identifier' => 'name',
                                'type' => 'Text',
                                'label' => 'Name',
                                'properties' => [
                                    'placeholder' => 'Placeholder',
                                ],
                            ],
                        ],
                    ],
                    [
                        'identifier' => 'page-2',
                        'type' => 'Page',
                        'label' => 'Page 2',
                    ],
                ],
            ],
            'standard',
            $this->request,
        );

        $formRuntime = $formDefinition->bind($this->request);

        // Translation view helper requires form runtime within view helper variable container
        $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate(
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            'formRuntime',
            $formRuntime,
        );

        return $formRuntime;
    }
}
