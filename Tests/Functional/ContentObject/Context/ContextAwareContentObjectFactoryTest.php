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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\ContentObject\Context;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use PHPUnit\Framework;
use Psr\Http\Message;
use TYPO3\CMS\Frontend;
use TYPO3\TestingFramework;

/**
 * ContextAwareContentObjectFactoryTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\ContentObject\Context\ContextAwareContentObjectFactory::class)]
final class ContextAwareContentObjectFactoryTest extends TestingFramework\Core\Functional\FunctionalTestCase
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
    private Frontend\ContentObject\ContentObjectRenderer $contentObjectRenderer;
    private Frontend\ContentObject\ContentObjectFactory $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = $this->buildServerRequest();
        $this->contentObjectRenderer = $this->get(Frontend\ContentObject\ContentObjectRenderer::class);
        $this->contentObjectRenderer->setRequest($this->request);
        $this->subject = $this->get(Frontend\ContentObject\ContentObjectFactory::class);
    }

    #[Framework\Attributes\Test]
    public function contentObjectFactoryIsDecorated(): void
    {
        self::assertInstanceOf(Src\ContentObject\Context\ContextAwareContentObjectFactory::class, $this->subject);
    }

    #[Framework\Attributes\Test]
    public function getContentObjectReturnsNullIfContentObjectDoesNotExist(): void
    {
        self::assertNull($this->subject->getContentObject('FOO', $this->request, $this->contentObjectRenderer));
    }

    #[Framework\Attributes\Test]
    public function getContentObjectReturnsResolvedContentObjectWrappedInContextAwareContentObject(): void
    {
        $actual = $this->subject->getContentObject('TEXT', $this->request, $this->contentObjectRenderer);

        self::assertInstanceOf(Src\ContentObject\Context\ContextAwareContentObject::class, $actual);
        self::assertInstanceOf(Frontend\ContentObject\TextContentObject::class, $actual->contentObject);
        self::assertSame('foo', $actual->render(['value' => 'foo']));
    }

    #[Framework\Attributes\Test]
    public function getContentObjectReturnsWrappedHandlebarsFormsContentObject(): void
    {
        $actual = $this->subject->getContentObject('HBS_PROPERTY', $this->request, $this->contentObjectRenderer);

        self::assertInstanceOf(Src\ContentObject\Context\ContextAwareContentObject::class, $actual);
        self::assertInstanceOf(Src\ContentObject\PropertyContentObject::class, $actual->contentObject);
    }
}
