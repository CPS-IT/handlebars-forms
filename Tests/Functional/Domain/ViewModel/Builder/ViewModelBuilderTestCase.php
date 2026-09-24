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

use CPSIT\Typo3HandlebarsForms\Tests;
use Psr\Http\Message;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Fluid;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * ViewModelBuilderTestCase
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
abstract class ViewModelBuilderTestCase extends TestingFramework\Core\Functional\FunctionalTestCase
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

    protected Extbase\Mvc\Request $request;
    protected Form\Domain\Runtime\FormRuntime $formRuntime;
    protected Fluid\Core\Rendering\RenderingContext $renderingContext;

    public function setUp(): void
    {
        parent::setUp();

        // Build and inject Extbase request object
        $this->request = $this->buildExtbaseRequest(typoScriptSetup: $this->getTypoScriptSetup());
        $this->get(Extbase\Configuration\ConfigurationManagerInterface::class)->setRequest($this->request);

        $this->renderingContext = $this->get(Fluid\Core\Rendering\RenderingContextFactory::class)->create();
        $this->renderingContext->setAttribute(Message\ServerRequestInterface::class, $this->request);
    }

    /**
     * @param list<array<string, mixed>> $renderables
     */
    protected function buildFormRuntime(array $renderables): Form\Domain\Runtime\FormRuntime
    {
        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
            [
                'identifier' => 'test-form',
                'type' => 'Form',
                'prototypeName' => 'standard',
                'label' => 'Test form',
                'renderingOptions' => [
                    // Avoid auto-generated honeypot elements with random identifiers
                    'honeypot' => [
                        'enable' => false,
                    ],
                ],
                'renderables' => [
                    [
                        'identifier' => 'page-1',
                        'type' => 'Page',
                        'label' => 'Page 1',
                        'renderables' => $renderables,
                    ],
                ],
            ],
            'standard',
            $this->request,
        );

        $this->formRuntime = $formDefinition->bind($this->request);

        // Form view helpers require form runtime within view helper variable container
        $this->renderingContext->getViewHelperVariableContainer()->addOrUpdate(
            Form\ViewHelpers\RenderRenderableViewHelper::class,
            'formRuntime',
            $this->formRuntime,
        );

        return $this->formRuntime;
    }

    /**
     * @template T of Form\Domain\Model\FormElements\FormElementInterface
     * @param class-string<T> $className
     * @return T
     */
    protected function getElement(
        string $identifier,
        string $className = Form\Domain\Model\FormElements\GenericFormElement::class,
    ): Form\Domain\Model\FormElements\FormElementInterface {
        $element = $this->formRuntime->getFormDefinition()->getElementByIdentifier($identifier);

        self::assertInstanceOf($className, $element);

        return $element;
    }

    protected function getTypoScriptSetup(): string
    {
        return '';
    }
}
