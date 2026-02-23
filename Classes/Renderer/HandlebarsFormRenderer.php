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

namespace CPSIT\Typo3HandlebarsForms\Renderer;

use CPSIT\Typo3Handlebars\View;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;
use TYPO3\CMS\Frontend;

/**
 * HandlebarsFormRenderer
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\Autoconfigure(public: true, shared: false)]
final class HandlebarsFormRenderer extends Form\Domain\Renderer\AbstractElementRenderer
{
    private readonly Core\Information\Typo3Version $typo3Version;

    public function __construct(
        private readonly Extbase\Configuration\ConfigurationManagerInterface $configurationManager,
        private readonly View\HandlebarsViewFactory $viewFactory,
        private readonly Core\TypoScript\TypoScriptService $typoScriptService,
    ) {
        $this->typo3Version = new Core\Information\Typo3Version();
    }

    public function render(): string
    {
        $view = $this->resolveViewFromConfiguration() ?? $this->resolveDefaultView();
        $view->assign('form', $this->formRuntime);

        match ($this->typo3Version->getMajorVersion()) {
            13 => $this->triggerBeforeRenderingHook(),
            14 => $this->triggerBeforeRenderableIsRenderedEvent(),
            default => null,
        };

        if ($view instanceof View\HandlebarsView) {
            $templateName = $view->getTemplateName() ?? $this->formRuntime->getTemplateName();
        } else {
            $templateName = $this->formRuntime->getTemplateName();
        }

        return $view->render($templateName);
    }

    private function resolveViewFromConfiguration(): ?View\HandlebarsView
    {
        $request = $this->formRuntime->getRequest();
        $contentObjectRenderer = $request->getAttribute('currentContentObject');

        // Early return when outside of content object rendering
        if (!($contentObjectRenderer instanceof Frontend\ContentObject\ContentObjectRenderer)) {
            return null;
        }

        // Resolve form configuration from plugin configuration
        $pluginConfiguration = $this->configurationManager->getConfiguration(
            Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
        );
        $formConfiguration = $pluginConfiguration['handlebarsForms'] ?? null;

        // Early return if no handlebars configuration is available
        if (!is_array($formConfiguration)) {
            return null;
        }

        // HANDLEBARSTEMPLATE content object requires TypoScript configuration, so let's convert early
        $typoScriptConfiguration = $this->typoScriptService->convertPlainArrayToTypoScriptArray($formConfiguration);

        // Resolve TypoScript configuration based on form identifier
        $resolvedConfiguration = [];
        $possibleConfigurationKeys = [
            // Fallback
            'default',
            // Unique form identifier
            $this->formRuntime->getIdentifier(),
            // Original form identifier
            $this->formRuntime->getRenderingOptions()['_originalIdentifier'],
            // Form persistence identifier
            $this->formRuntime->getFormDefinition()->getPersistenceIdentifier(),
        ];
        foreach ($possibleConfigurationKeys as $possibleConfigurationKey) {
            if (\array_key_exists($possibleConfigurationKey . '.', $typoScriptConfiguration)) {
                Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
                    $resolvedConfiguration,
                    $typoScriptConfiguration[$possibleConfigurationKey . '.'],
                );
            }
        }

        return new View\HandlebarsView(
            $contentObjectRenderer,
            $this->typoScriptService,
            $resolvedConfiguration,
            $request,
        );
    }

    private function resolveDefaultView(): Core\View\ViewInterface
    {
        $renderingOptions = $this->formRuntime->getRenderingOptions();
        $viewFactoryData = new Core\View\ViewFactoryData(
            templateRootPaths: $renderingOptions['templateRootPaths'] ?? [],
            partialRootPaths: $renderingOptions['partialRootPaths'] ?? [],
            layoutRootPaths: $renderingOptions['layoutRootPaths'] ?? [],
            request: $this->formRuntime->getRequest(),
        );

        return $this->viewFactory->create($viewFactoryData);
    }

    /**
     * @todo Remove once support for TYPO3 v13 is dropped
     */
    private function triggerBeforeRenderingHook(): void
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeRendering'] ?? [] as $className) {
            /* @phpstan-ignore argument.templateType */
            $hookObj = Core\Utility\GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeRendering')) {
                $hookObj->beforeRendering($this->formRuntime, $this->formRuntime->getFormDefinition());
            }
        }
    }

    /**
     * @todo Enable once support for TYPO3 v14 is added
     */
    private function triggerBeforeRenderableIsRenderedEvent(): void
    {
        // $this->eventDispatcher->dispatch(
        //     new Form\Event\BeforeRenderableIsRenderedEvent($this->formRuntime->getFormDefinition(), $this->formRuntime),
        // );
    }
}
