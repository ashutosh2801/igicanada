<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class LegacyHtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'h2', 'h3', 'h4', 'ul', 'ol', 'li', 'strong', 'em', 'b', 'i',
        'a', 'br', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'blockquote',
    ];

    private const REMOVE_WITH_CONTENT = [
        'script', 'style', 'form', 'iframe', 'object', 'embed', 'svg', 'math',
        'input', 'select', 'option', 'textarea', 'button', 'link', 'meta',
    ];

    public function sanitize(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="legacy-content">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('legacy-content');
        if (! $root) {
            return '';
        }

        $this->cleanChildren($root);

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }

        return trim($clean);
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (in_array($tag, self::REMOVE_WITH_CONTENT, true)) {
                $parent->removeChild($node);

                continue;
            }

            $this->cleanChildren($node);

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                while ($node->firstChild) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);

                continue;
            }

            $href = $tag === 'a' ? $node->getAttribute('href') : null;
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $node->removeAttribute($attribute->name);
            }

            if ($tag === 'a') {
                $this->sanitizeLink($node, (string) $href);
            }
        }
    }

    private function sanitizeLink(DOMElement $link, string $originalHref): void
    {
        $href = trim($originalHref);
        if ($href === '' || ! preg_match('#^(https?://|mailto:|/|\#)#i', $href)) {
            $link->removeAttribute('href');

            return;
        }

        $link->setAttribute('href', $href);
        if (preg_match('#^https?://#i', $href)) {
            $link->setAttribute('rel', 'noopener noreferrer');
        }
    }
}
