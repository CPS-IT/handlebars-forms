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

namespace CPSIT\Typo3HandlebarsForms\Tests\Functional\EventListener;

use CPSIT\Typo3HandlebarsForms as Src;
use CPSIT\Typo3HandlebarsForms\Tests;
use EliasHaeussler\PHPUnitAttributes;
use PHPUnit\Framework;
use TYPO3\CMS\Extbase;
use TYPO3\CMS\Form;
use TYPO3\TestingFramework;

/**
 * CurrentPageInitializedListenerTest
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[Framework\Attributes\CoversClass(Src\EventListener\CurrentPageInitializedListener::class)]
final class CurrentPageInitializedListenerTest extends TestingFramework\Core\Functional\FunctionalTestCase
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

    private ?Extbase\Mvc\ExtbaseRequestParameters $extbaseRequestParameters = null;
    private Form\Domain\Runtime\FormRuntime $formRuntime;
    private Src\EventListener\CurrentPageInitializedListener $subject;

    public function setUp(): void
    {
        parent::setUp();

        $request = $this->buildExtbaseRequest($this->extbaseRequestParameters);

        /** @var Form\Domain\Model\FormDefinition $formDefinition */
        $formDefinition = $this->get(Form\Domain\Factory\ArrayFormFactory::class)->build(
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
                    ],
                    [
                        'identifier' => 'page-2',
                        'type' => 'Page',
                        'label' => 'Page 2',
                    ],
                    [
                        'identifier' => 'page-3',
                        'type' => 'Page',
                        'label' => 'Page 3',
                        'renderables' => [
                            [
                                'identifier' => 'required-field',
                                'type' => 'Text',
                                'label' => 'Required field',
                                'validators' => [
                                    ['identifier' => 'NotEmpty'],
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
        $this->subject = new Src\EventListener\CurrentPageInitializedListener();
    }

    #[Framework\Attributes\Test]
    #[PHPUnitAttributes\Attribute\RequiresPackage('typo3/cms-core', '~14.3.0')]
    public function invokeUpdatesCurrentPageOfEvent(): void
    {
        $this->enableFastForwardSubmit();

        $this->extbaseRequestParameters?->setArgument('__currentPage', 3);
        $this->extbaseRequestParameters?->setArgument('required-field', 'foo');

        $event = new Form\Event\AfterCurrentPageIsResolvedEvent(
            $this->getFirstPage(),
            $this->formRuntime,
            $this->getFirstPage(),
            $this->formRuntime->getRequest(),
        );

        ($this->subject)($event);

        self::assertNull($event->currentPage);
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsNullIfCurrentPageIsNull(): void
    {
        $this->enableFastForwardSubmit();

        self::assertNull($this->subject->afterInitializeCurrentPage($this->formRuntime, null, $this->getFirstPage()));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsCurrentPageIfFastForwardSubmitIsNotAllowed(): void
    {
        $this->extbaseRequestParameters?->setArgument('__currentPage', 3);

        $page = $this->formRuntime->getFormDefinition()->getPageByIndex(0);

        self::assertSame($page, $this->subject->afterInitializeCurrentPage($this->formRuntime, $page, $page));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsCurrentPageIfRequestedPageIsNotLastPage(): void
    {
        $this->enableFastForwardSubmit();

        $this->extbaseRequestParameters?->setArgument('__currentPage', 2);

        $page = $this->formRuntime->getFormDefinition()->getPageByIndex(0);

        self::assertSame($page, $this->subject->afterInitializeCurrentPage($this->formRuntime, $page, $page));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsCurrentPageIfNoPageIsRequested(): void
    {
        $this->enableFastForwardSubmit();

        $page = $this->formRuntime->getFormDefinition()->getPageByIndex(0);

        self::assertSame($page, $this->subject->afterInitializeCurrentPage($this->formRuntime, $page, $page));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsCurrentPageIfLastDisplayedPageIsNull(): void
    {
        $this->enableFastForwardSubmit();

        $this->extbaseRequestParameters?->setArgument('__currentPage', 3);
        $this->extbaseRequestParameters?->setArgument('required-field', 'foo');

        $page = $this->getFirstPage();

        self::assertSame($page, $this->subject->afterInitializeCurrentPage($this->formRuntime, $page, null));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsNullIfLastPageIsRequestedAndSkippedPagesAreValid(): void
    {
        $this->enableFastForwardSubmit();

        $this->extbaseRequestParameters?->setArgument('__currentPage', 3);
        $this->extbaseRequestParameters?->setArgument('required-field', 'foo');

        $page = $this->getFirstPage();

        self::assertNull($this->subject->afterInitializeCurrentPage($this->formRuntime, $page, $page));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageReturnsCurrentPageIfSkippedPagesAreInvalid(): void
    {
        $this->enableFastForwardSubmit();

        $this->extbaseRequestParameters?->setArgument('__currentPage', 3);

        $page = $this->getFirstPage();

        self::assertSame($page, $this->subject->afterInitializeCurrentPage($this->formRuntime, $page, $page));
    }

    #[Framework\Attributes\Test]
    public function afterInitializeCurrentPageDoesNotRevalidateLastDisplayedPage(): void
    {
        $this->enableFastForwardSubmit();

        $this->extbaseRequestParameters?->setArgument('__currentPage', 3);

        $firstPage = $this->getFirstPage();
        $thirdPage = $this->formRuntime->getFormDefinition()->getPageByIndex(2);

        // The last displayed page is already validated by FormRuntime, so it must not be validated again here
        self::assertNull($this->subject->afterInitializeCurrentPage($this->formRuntime, $firstPage, $thirdPage));
    }

    private function getFirstPage(): Form\Domain\Model\FormElements\Page
    {
        return $this->formRuntime->getFormDefinition()->getPageByIndex(0);
    }

    private function enableFastForwardSubmit(): void
    {
        $this->formRuntime->getFormDefinition()->setRenderingOption('allowFastForwardSubmit', true);
    }
}
