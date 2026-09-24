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
use PHPUnit\Framework;
use Psr\Http\Message;
use TYPO3\CMS\Core;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * CanUpdateRegisterTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversTrait(Src\ContentObject\CanUpdateRegister::class)]
final class CanUpdateRegisterTest extends TestingFramework\Core\Functional\FunctionalTestCase
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

    private Message\ServerRequestInterface $request;
    private Frontend\ContentObject\ContentObjectRenderer $cObj;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = $this->buildServerRequest();

        $GLOBALS['TSFE'] = new Frontend\Controller\TypoScriptFrontendController();

        $this->cObj = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->cObj->setRequest($this->request);
    }

    #[Framework\Attributes\Test]
    public function updateRegisterSetsRegisterValueInTypoScriptFrontendControllerOnTypo3V13(): void
    {
        $subject = $this->createSubject(13);

        $subject->callUpdateRegister('FOO', 42);

        self::assertSame(42, $this->readRegister('FOO'));
    }

    #[Framework\Attributes\Test]
    public function updateRegisterRemovesRegisterValueFromTypoScriptFrontendControllerOnTypo3V13(): void
    {
        $subject = $this->createSubject(13);

        $subject->callUpdateRegister('FOO', 42);
        $subject->callUpdateRegister('FOO');

        self::assertNull($this->readRegister('FOO'));
    }

    #[Framework\Attributes\Test]
    public function updateRegisterDoesNothingOnTypo3V14IfRegisterStackIsNotAvailable(): void
    {
        $subject = $this->createSubject(14);

        $subject->callUpdateRegister('FOO', 42);

        self::assertNull($this->readRegister('FOO'));
    }

    #[Framework\Attributes\Test]
    public function updateRegisterDoesNothingOnUnsupportedTypo3Version(): void
    {
        $subject = $this->createSubject(12);

        $subject->callUpdateRegister('FOO', 42);

        self::assertNull($this->readRegister('FOO'));
    }

    private function createSubject(int $majorVersion): Tests\Functional\Fixtures\Classes\DummyRegisterContentObject
    {
        $typo3Version = self::createStub(Core\Information\Typo3Version::class);
        $typo3Version->method('getMajorVersion')->willReturn($majorVersion);

        $subject = new Tests\Functional\Fixtures\Classes\DummyRegisterContentObject($typo3Version);
        $subject->setRequest($this->request);
        $subject->setContentObjectRenderer($this->cObj);

        return $subject;
    }

    private function readRegister(string $key): mixed
    {
        // Pass empty field array to avoid page record lookup, which is not available in this test
        return $this->cObj->getData('register:' . $key, []);
    }
}
