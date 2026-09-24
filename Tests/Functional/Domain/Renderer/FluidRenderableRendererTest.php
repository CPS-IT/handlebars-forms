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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\Domain\Renderer;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use PHPUnit\Framework;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * FluidRenderableRendererTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\Renderer\FluidRenderableRenderer::class)]
final class FluidRenderableRendererTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Src\Domain\Renderer\FluidRenderableRenderer $subject;

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $this->request = $this->buildExtbaseRequest();
        $this->get(Extbase\Configuration\ConfigurationManagerInterface::class)->setRequest($this->request);

        $this->subject = $this->get(Src\Domain\Renderer\FluidRenderableRenderer::class);
    }

    #[Framework\Attributes\Test]
    public function renderRendersFluidPartialOfElementWithinFormContext(): void
    {
        $formRuntime = $this->buildFormRuntime();

        $actual = $this->subject->render($this->getElement($formRuntime, 'name'), $formRuntime);

        self::assertStringContainsString('<input', $actual);
        self::assertStringContainsString('id="test-form-name"', $actual);
        // Form object name is only prepended if rendered within <f:form> context
        self::assertStringContainsString('name="test-form[name]"', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderProvidesElementAsElementVariable(): void
    {
        $formRuntime = $this->buildFormRuntime();

        $actual = $this->subject->render($this->getElement($formRuntime, 'variables'), $formRuntime);

        self::assertSame('renderable=variables|element=variables|page=|form=test-form|foo=', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderProvidesPageAsPageVariable(): void
    {
        $formRuntime = $this->buildFormRuntime(['templateName' => 'Variables']);
        $page = $formRuntime->getFormDefinition()->getPageByIndex(1);

        $actual = $this->subject->render($page, $formRuntime);

        self::assertSame('renderable=page-2|element=|page=page-2|form=test-form|foo=', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderProvidesFormRuntimeAsFormVariable(): void
    {
        $formRuntime = $this->buildFormRuntime(['templateName' => 'Variables']);

        $actual = $this->subject->render($formRuntime, $formRuntime);

        self::assertSame('renderable=test-form|element=|page=|form=test-form|foo=', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderPassesAdditionalVariablesToFluidPartial(): void
    {
        $formRuntime = $this->buildFormRuntime();

        $actual = $this->subject->render(
            $this->getElement($formRuntime, 'variables'),
            $formRuntime,
            [
                'foo' => 'bar',
                // Reserved variables must not be overridden
                'renderable' => 'baz',
                'form' => 'baz',
            ],
        );

        self::assertSame('renderable=variables|element=variables|page=|form=test-form|foo=bar', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderIgnoresInvalidTemplateAndLayoutRootPaths(): void
    {
        $formRuntime = $this->buildFormRuntime([
            'templateRootPaths' => 'foo',
            'layoutRootPaths' => 'foo',
        ]);

        $actual = $this->subject->render($this->getElement($formRuntime, 'variables'), $formRuntime);

        self::assertSame('renderable=variables|element=variables|page=|form=test-form|foo=', trim($actual));
    }

    /**
     * @param array<string, mixed> $renderingOptions
     */
    private function buildFormRuntime(array $renderingOptions = []): Form\Domain\Runtime\FormRuntime
    {
        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
            [
                'identifier' => 'test-form',
                'type' => 'Form',
                'prototypeName' => 'standard',
                'label' => 'Test form',
                'renderingOptions' => [
                    ...$renderingOptions,
                    'partialRootPaths' => [
                        100 => 'EXT:handlebars_forms/Tests/Functional/Fixtures/Resources/Private/Partials/',
                    ],
                ],
                'renderables' => [
                    [
                        'identifier' => 'page-1',
                        'type' => 'Page',
                        'label' => 'Page 1',
                        'renderables' => [
                            [
                                'identifier' => 'name',
                                'type' => 'Text',
                                'label' => 'Name',
                            ],
                            [
                                'identifier' => 'variables',
                                'type' => 'Text',
                                'label' => 'Variables',
                                'renderingOptions' => [
                                    'templateName' => 'Variables',
                                ],
                            ],
                        ],
                    ],
                    [
                        'identifier' => 'page-2',
                        'type' => 'Page',
                        'label' => 'Page 2',
                        'renderingOptions' => [
                            'templateName' => 'Variables',
                        ],
                    ],
                ],
            ],
            'standard',
            $this->request,
        );

        return $formDefinition->bind($this->request);
    }

    private function getElement(
        Form\Domain\Runtime\FormRuntime $formRuntime,
        string $identifier,
    ): Form\Domain\Model\FormElements\FormElementInterface {
        $element = $formRuntime->getFormDefinition()->getElementByIdentifier($identifier);

        self::assertInstanceOf(Form\Domain\Model\FormElements\FormElementInterface::class, $element);

        return $element;
    }
}
