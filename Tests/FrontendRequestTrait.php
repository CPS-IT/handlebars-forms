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

namespace CPSIT\Typo3HandlebarsForms\Tests;

use Psr\Http\Message;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Core;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Frontend;

/**
 * FrontendRequestTrait
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 * @internal
 */
trait FrontendRequestTrait
{
    /**
     * @param-out Frontend\Cache\CacheInstruction $cacheInstruction
     * @param-out Core\TypoScript\FrontendTypoScript $frontendTypoScript
     */
    protected function buildServerRequest(
        ?Frontend\Cache\CacheInstruction &$cacheInstruction = null,
        ?Core\TypoScript\FrontendTypoScript &$frontendTypoScript = null,
        string $typoScriptSetup = '',
    ): Message\ServerRequestInterface {
        $cacheInstruction ??= new Frontend\Cache\CacheInstruction();

        if ($frontendTypoScript === null) {
            $astBuilder = new Core\TypoScript\AST\AstBuilder(new Core\EventDispatcher\NoopEventDispatcher());
            $factory = new Core\TypoScript\TypoScriptStringFactory(
                new DependencyInjection\Container(),
                new Core\TypoScript\Tokenizer\LossyTokenizer(),
            );
            // Form setup must be registered via TypoScript in TYPO3 v13 (it's auto-discovered since TYPO3 v14.2)
            // @todo Remove once support for TYPO3 v13 is dropped
            if ((new Core\Information\Typo3Version())->getMajorVersion() < 14) {
                $typoScriptSetup = 'plugin.tx_form.settings.yamlConfigurations.10 = EXT:form/Configuration/Yaml/FormSetup.yaml'
                    . PHP_EOL . $typoScriptSetup;
            }

            $rootNode = $factory->parseFromString($typoScriptSetup, $astBuilder);

            $frontendTypoScript = new Core\TypoScript\FrontendTypoScript($rootNode, [], [], []);
            $frontendTypoScript->setSetupTree($rootNode);
            $frontendTypoScript->setSetupArray($rootNode->toArray());
            $frontendTypoScript->setConfigArray([]);
        }

        $frontendUser = new Frontend\Authentication\FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();

        $serverRequest = new Core\Http\ServerRequest('https://typo3-testing.local/');
        $serverRequest = $serverRequest
            ->withAttribute('applicationType', Core\Core\SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('frontend.cache.instruction', $cacheInstruction)
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.user', $frontendUser)
            ->withAttribute(
                'language',
                new Core\Site\Entity\SiteLanguage(0, 'en_US.UTF-8', new Core\Http\Uri('/'), ['typo3Language' => 'default']),
            )
            ->withAttribute('normalizedParams', new Core\Http\NormalizedParams([], [], 'index.php', '/'))
        ;

        // Registers are managed by a register stack within the current request since TYPO3 v14
        if ((new Core\Information\Typo3Version())->getMajorVersion() >= 14) {
            $serverRequest = $serverRequest->withAttribute('frontend.register.stack', new Frontend\ContentObject\RegisterStack());
        }

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        return $serverRequest;
    }

    /**
     * Registers are managed by the TypoScript frontend controller in TYPO3 v13.
     *
     * @todo Remove once support for TYPO3 v13 is dropped
     */
    protected function initializeTypoScriptFrontendController(): void
    {
        if ((new Core\Information\Typo3Version())->getMajorVersion() < 14) {
            $GLOBALS['TSFE'] = new Frontend\Controller\TypoScriptFrontendController();
        }
    }

    /**
     * @param-out Extbase\Mvc\ExtbaseRequestParameters $extbaseRequestParameters
     */
    protected function buildExtbaseRequest(
        ?Extbase\Mvc\ExtbaseRequestParameters &$extbaseRequestParameters = null,
        string $typoScriptSetup = '',
    ): Extbase\Mvc\Request {
        $serverRequest = $this->buildServerRequest(typoScriptSetup: $typoScriptSetup);

        if ($extbaseRequestParameters === null) {
            $extbaseRequestParameters = new Extbase\Mvc\ExtbaseRequestParameters('Vendor\\Extension\\Controller\\FooController');
            $extbaseRequestParameters->setControllerActionName('baz');
        }

        $serverRequest = $serverRequest->withAttribute('extbase', $extbaseRequestParameters);

        $GLOBALS['TYPO3_REQUEST'] = $serverRequest;

        return new Extbase\Mvc\Request($serverRequest);
    }
}
