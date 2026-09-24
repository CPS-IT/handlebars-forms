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
use EliasHaeussler\PHPUnitAttributes;
use PHPUnit\Framework;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * HandlebarsFormRendererTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\Domain\Renderer\HandlebarsFormRenderer::class)]
final class HandlebarsFormRendererTest extends TestingFramework\Core\Functional\FunctionalTestCase
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

    private string $typoScriptSetup;

    public function setUp(): void
    {
        parent::setUp();

        $typoScriptSetup = file_get_contents(dirname(__DIR__, 2) . '/Fixtures/Configuration/TypoScript/setup.typoscript');

        self::assertIsString($typoScriptSetup);

        $this->typoScriptSetup = $typoScriptSetup;
    }

    #[Framework\Attributes\Test]
    public function renderUsesFluidViewOutsideOfContentObjectRendering(): void
    {
        $actual = $this->renderForm(typoScriptSetup: $this->typoScriptSetup, withContentObject: false);

        self::assertStringContainsString('<form', $actual);
        self::assertStringContainsString('id="test-form"', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderUsesFluidViewIfNoHandlebarsFormsConfigurationIsAvailable(): void
    {
        $actual = $this->renderForm();

        self::assertStringContainsString('<form', $actual);
        self::assertStringContainsString('id="test-form"', $actual);
    }

    #[Framework\Attributes\Test]
    public function renderUsesDefaultHandlebarsFormsConfiguration(): void
    {
        $actual = $this->renderForm(['identifier' => 'other-form'], $this->typoScriptSetup);

        self::assertSame('default:default', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderMergesFormSpecificHandlebarsFormsConfiguration(): void
    {
        $actual = $this->renderForm(typoScriptSetup: $this->typoScriptSetup);

        self::assertSame('custom:identifier', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderMergesHandlebarsFormsConfigurationForOriginalFormIdentifier(): void
    {
        $actual = $this->renderForm(
            [
                'renderingOptions' => [
                    '_originalIdentifier' => 'original-form',
                ],
            ],
            $this->typoScriptSetup,
        );

        self::assertSame('custom:original identifier', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderMergesHandlebarsFormsConfigurationForFormPersistenceIdentifier(): void
    {
        $actual = $this->renderForm(
            [
                'persistenceIdentifier' => 'persisted-form',
                'renderingOptions' => [
                    '_originalIdentifier' => 'original-form',
                ],
            ],
            $this->typoScriptSetup,
        );

        self::assertSame('custom:persistence identifier', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderUsesTemplateNameOfFormRuntimeIfNoTemplateNameIsConfigured(): void
    {
        $typoScriptSetup = <<<'TYPOSCRIPT'
plugin.tx_form.handlebarsForms.default {
  templateRootPaths.10 = EXT:handlebars_forms/Tests/Functional/Fixtures/Resources/Private/Templates/Handlebars
  variables.variant = TEXT
  variables.variant.value = default
}
TYPOSCRIPT;

        $actual = $this->renderForm(typoScriptSetup: $typoScriptSetup);

        self::assertSame('form:default', trim($actual));
    }

    #[Framework\Attributes\Test]
    public function renderDisablesCacheOnSubmittedForms(): void
    {
        $this->renderForm(typoScriptSetup: $this->typoScriptSetup, method: 'POST', cacheInstruction: $cacheInstruction);

        self::assertFalse($cacheInstruction->isCachingAllowed());
    }

    #[Framework\Attributes\Test]
    public function renderKeepsCacheEnabledOnNonSubmittedForms(): void
    {
        $this->renderForm(typoScriptSetup: $this->typoScriptSetup, cacheInstruction: $cacheInstruction);

        self::assertTrue($cacheInstruction->isCachingAllowed());
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~13.4.0')]
    public function renderTriggersBeforeRenderingHookOnTypo3V13(): void
    {
        $hook = new Tests\Functional\Fixtures\Classes\DummyBeforeRenderingHook();

        Core\Utility\GeneralUtility::addInstance(Tests\Functional\Fixtures\Classes\DummyBeforeRenderingHook::class, $hook);

        self::assertIsArray($GLOBALS['TYPO3_CONF_VARS']);

        $GLOBALS['TYPO3_CONF_VARS'] = Core\Utility\ArrayUtility::setValueByPath(
            $GLOBALS['TYPO3_CONF_VARS'],
            ['SC_OPTIONS', 'ext/form', 'beforeRendering'],
            [Tests\Functional\Fixtures\Classes\DummyBeforeRenderingHook::class],
        );

        $this->renderForm(typoScriptSetup: $this->typoScriptSetup, formRuntime: $formRuntime);

        self::assertSame($formRuntime, $hook->formRuntime);
        self::assertSame($formRuntime->getFormDefinition(), $hook->renderable);
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function renderDispatchesBeforeRenderableIsRenderedEventOnTypo3V14(): void
    {
        $dispatchedEvent = null;

        $container = $this->getContainer();

        self::assertInstanceOf(DependencyInjection\ContainerInterface::class, $container);

        $container->set(
            'handlebars-forms.test.before-renderable-is-rendered-listener',
            static function (Form\Event\BeforeRenderableIsRenderedEvent $event) use (&$dispatchedEvent) {
                $dispatchedEvent = $event;
            },
        );

        $this->get(Core\EventDispatcher\ListenerProvider::class)->addListener(
            Form\Event\BeforeRenderableIsRenderedEvent::class,
            'handlebars-forms.test.before-renderable-is-rendered-listener',
        );

        $this->renderForm(typoScriptSetup: $this->typoScriptSetup, formRuntime: $formRuntime);

        self::assertInstanceOf(Form\Event\BeforeRenderableIsRenderedEvent::class, $dispatchedEvent);
        self::assertSame($formRuntime, $dispatchedEvent->formRuntime);
        self::assertSame($formRuntime->getFormDefinition(), $dispatchedEvent->renderable);
    }

    /**
     * @param array<string, mixed> $formDefinitionOverrides
     * @param-out Frontend\Cache\CacheInstruction $cacheInstruction
     * @param-out Form\Domain\Runtime\FormRuntime $formRuntime
     */
    private function renderForm(
        array $formDefinitionOverrides = [],
        string $typoScriptSetup = '',
        bool $withContentObject = true,
        string $method = 'GET',
        ?Frontend\Cache\CacheInstruction &$cacheInstruction = null,
        ?Form\Domain\Runtime\FormRuntime &$formRuntime = null,
    ): string {
        $request = $this->buildExtbaseRequest(typoScriptSetup: $typoScriptSetup)->withMethod($method);
        $requestCacheInstruction = $request->getAttribute('frontend.cache.instruction');

        self::assertInstanceOf(Frontend\Cache\CacheInstruction::class, $requestCacheInstruction);

        $cacheInstruction = $requestCacheInstruction;

        if ($withContentObject) {
            $contentObjectRenderer = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
            $request = $request->withAttribute('currentContentObject', $contentObjectRenderer);
            $contentObjectRenderer->setRequest($request);
        }

        $configurationManager = $this->get(Extbase\Configuration\ConfigurationManagerInterface::class);
        $configurationManager->setRequest($request);
        $configurationManager->setConfiguration([
            'extensionName' => 'Form',
            'pluginName' => 'Formframework',
        ]);

        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
            array_replace_recursive(
                [
                    'identifier' => 'test-form',
                    'type' => 'Form',
                    'prototypeName' => 'standard',
                    'label' => 'Test form',
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
                            ],
                        ],
                    ],
                ],
                $formDefinitionOverrides,
            ),
            'standard',
            $request,
        );

        $formRuntime = $formDefinition->bind($request);

        $subject = $this->get(Src\Domain\Renderer\HandlebarsFormRenderer::class);
        $subject->setFormRuntime($formRuntime);

        return $subject->render();
    }
}
