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

namespace CPSIT\Typo3HandlebarsForms\Domain\ViewModel;

use CPSIT\Typo3HandlebarsForms\Exception;
use TYPO3\CMS\Form;

/**
 * FormValueContext
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 *
 * @extends \ArrayObject<string|int, mixed>
 */
final class FormValueViewModel extends \ArrayObject implements CompositeViewModel
{
    public function __construct(
        public readonly Form\Domain\Model\FormElements\FormElementInterface $element,
        public readonly mixed $value,
        public readonly mixed $processedValue = null,
        public readonly bool $isMultiValue = false,
        public readonly bool $isSection = false,
    ) {
        parent::__construct([
            'value' => $this->value,
            'processedValue' => $this->processedValue,
            'isMultiValue' => $this->isMultiValue,
            'isSection' => $this->isSection,
        ]);
    }

    /**
     * @param array<string, mixed> $context
     * @throws Exception\FormValueContextIsInvalid
     */
    public static function fromArray(array $context): self
    {
        $element = $context['element'] ?? null;
        $value = $context['value'] ?? null;
        $processedValue = $context['processedValue'] ?? null;
        $isMultiValue = $context['isMultiValue'] ?? false;
        $isSection = $context['isSection'] ?? false;

        if (!($element instanceof Form\Domain\Model\FormElements\FormElementInterface)) {
            throw new Exception\FormValueContextIsInvalid();
        }

        if (is_scalar($isMultiValue)) {
            $isMultiValue = (bool)$isMultiValue;
        } else {
            $isMultiValue = false;
        }

        if (is_scalar($isSection)) {
            $isSection = (bool)$isSection;
        } else {
            $isSection = false;
        }

        return new self($element, $value, $processedValue, $isMultiValue, $isSection);
    }

    public function getRenderable(): Form\Domain\Model\FormElements\FormElementInterface
    {
        return $this->element;
    }

    public function getChildren(): array
    {
        if (!is_array($this->value) || !is_array($this->processedValue)) {
            return [];
        }

        $children = [];

        foreach ($this->processedValue as $key => $processedValue) {
            $value = $this->value[$key] ?? null;
            $children[$key] = new self($this->element, $value, $processedValue);
        }

        return $children;
    }
}
