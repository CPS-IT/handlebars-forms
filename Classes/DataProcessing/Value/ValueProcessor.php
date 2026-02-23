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

namespace CPSIT\Typo3HandlebarsForms\DataProcessing\Value;

use CPSIT\Typo3HandlebarsForms\DataProcessing;
use Symfony\Component\DependencyInjection;
use TYPO3\CMS\Form;

/**
 * ValueProcessor
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
#[DependencyInjection\Attribute\AutoconfigureTag('handlebars_forms.value_processor')]
interface ValueProcessor
{
    /**
     * @param array<string, mixed> $configuration
     */
    public function process(
        Form\Domain\Model\Renderable\RootRenderableInterface $renderable,
        DataProcessing\Renderable\ProcessedRenderable $processedRenderable,
        array $configuration = [],
    ): mixed;

    /**
     * @return non-empty-string
     */
    public static function getName(): string;
}
