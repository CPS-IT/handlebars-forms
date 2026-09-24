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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\ContentObject;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use DevTheorem\Handlebars;
use PHPUnit\Framework;
use Psr\Http\Message;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * PassthroughContentObjectTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\PassthroughContentObject::class)]
final class PassthroughContentObjectTest extends TestingFramework\Core\Functional\FunctionalTestCase
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

    private Form\Domain\Runtime\FormRuntime $formRuntime;
    private Fluid\Core\Rendering\RenderingContext $renderingContext;
    private Src\ContentObject\Context\ContextStack $contextStack;
    private Src\ContentObject\Context\ValueCollector $valueCollector;
    private Src\ContentObject\PassthroughContentObject $subject;

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $request = $this->buildExtbaseRequest();
        $this->get(Extbase\Configuration\ConfigurationManagerInterface::class)->setRequest($request);

        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
            [
                'identifier' => 'test-form',
                'type' => 'Form',
                'prototypeName' => 'standard',
                'label' => 'Test form',
                'renderingOptions' => [
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
                                'identifier' => 'dummy',
                                'type' => 'Text',
                                'label' => 'Dummy',
                                'renderingOptions' => [
                                    'templateName' => 'Dummy',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'standard',
            $request,
        );

        $this->formRuntime = $formDefinition->bind($request);
        $this->renderingContext = $this->get(Fluid\Core\Rendering\RenderingContextFactory::class)->create();
        $this->renderingContext->setAttribute(Message\ServerRequestInterface::class, $request);

        $this->contextStack = new Src\ContentObject\Context\ContextStack();
        $this->valueCollector = new Src\ContentObject\Context\ValueCollector();
        $this->subject = new Src\ContentObject\PassthroughContentObject(
            $this->get(Src\Domain\Renderer\FluidRenderableRenderer::class),
            $this->get(Core\TypoScript\TypoScriptService::class),
        );
        $this->subject->injectContextStack($this->contextStack);
        $this->subject->injectValueCollector($this->valueCollector);
    }

    #[Framework\Attributes\Test]
    public function renderReturnsRenderedFluidPartialOfRenderable(): void
    {
        $this->pushContext('name');

        $actual = $this->valueCollector->load($this->subject->render());

        self::assertInstanceOf(Handlebars\SafeString::class, $actual);
        self::assertStringContainsString('<input', (string)$actual);
        self::assertStringContainsString('id="test-form-name"', (string)$actual);
    }

    #[Framework\Attributes\Test]
    public function renderPassesEmptyVariablesToFluidPartialIfNoConfigurationIsGiven(): void
    {
        $this->pushContext('dummy');

        $actual = $this->valueCollector->load($this->subject->render());

        self::assertInstanceOf(Handlebars\SafeString::class, $actual);
        self::assertSame('element=dummy|foo=|bar.baz=', trim((string)$actual));
    }

    #[Framework\Attributes\Test]
    public function renderPassesConfigurationAsPlainVariablesToFluidPartial(): void
    {
        $this->pushContext('dummy');

        $actual = $this->valueCollector->load(
            $this->subject->render([
                'foo' => 'hello',
                'bar.' => [
                    'baz' => 'world',
                ],
            ]),
        );

        self::assertInstanceOf(Handlebars\SafeString::class, $actual);
        self::assertSame('element=dummy|foo=hello|bar.baz=world', trim((string)$actual));
    }

    private function pushContext(string $identifier): void
    {
        $renderable = $this->formRuntime->getFormDefinition()->getElementByIdentifier($identifier);

        self::assertInstanceOf(Form\Domain\Model\FormElements\FormElementInterface::class, $renderable);

        $this->contextStack->push(
            new Src\ContentObject\Context\ValueResolutionContext(
                $renderable,
                new Src\Domain\ViewModel\SimpleViewModel($renderable),
                $this->renderingContext,
                $this->formRuntime,
            ),
        );
    }
}
