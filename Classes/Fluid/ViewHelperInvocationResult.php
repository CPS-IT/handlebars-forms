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

namespace CPSIT\Typo3HandlebarsForms\Fluid;

use CPSIT\Typo3HandlebarsForms\Domain;
use TYPO3\CMS\Fluid;
use TYPO3Fluid\Fluid as FluidStandalone;

/**
 * ViewHelperInvocationResult
 *
 * @author Elias Häußler <e.haeussler@familie-redlich.de>
 * @license GPL-2.0-or-later
 */
final readonly class ViewHelperInvocationResult
{
    public function __construct(
        public FluidStandalone\Core\ViewHelper\ViewHelperInterface $viewHelper,
        public Fluid\Core\Rendering\RenderingContext $renderingContext,
        public mixed $content,
        public FluidStandalone\Core\ViewHelper\TagBuilder $tag = new FluidStandalone\Core\ViewHelper\TagBuilder(),
    ) {}

    /**
     * @param non-empty-string $tagName
     * @return list<Domain\Renderable\ViewModel\ViewModel>
     */
    public function extractChildNodes(string $tagName): array
    {
        $content = $this->tag->getContent();
        $children = [];

        // Early return if no content is provided
        if ($content === null) {
            return [];
        }

        // Enforce UTF-8 on the rendered tag content
        $useInternalErrors = libxml_use_internal_errors(true);
        $html = '<?xml encoding="UTF-8">' . $content;

        // Load tag content as DOMDocument
        try {
            $dom = new \DOMDocument();
            $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        } finally {
            libxml_use_internal_errors($useInternalErrors);
        }

        // Parse nodes
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//' . $tagName);

        // Early return if queried nodes are invalid
        if (!($nodes instanceof \DOMNodeList)) {
            return [];
        }

        // Convert nodes to view models
        foreach ($nodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue;
            }

            $tag = new FluidStandalone\Core\ViewHelper\TagBuilder($tagName, trim($node->textContent));

            /** @var \DOMAttr $attribute */
            foreach ($node->attributes as $attribute) {
                $tag->addAttribute($attribute->name, $attribute->value);
            }

            $children[] = new Domain\Renderable\ViewModel\ViewModel($this->renderingContext, null, $tag);
        }

        return $children;
    }
}
