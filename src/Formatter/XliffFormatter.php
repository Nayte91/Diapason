<?php

declare(strict_types=1);

namespace Diapason\Formatter;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

final class XliffFormatter implements FormatterInterface
{
    /** @param list<string> $blankLineBetween */
    public function __construct(private IndentStyle $indent = IndentStyle::Tab, private bool $newlineAfterTag = true, private array $blankLineBetween = ['group', 'unit']) {}

    public static function configure(): self
    {
        return new self();
    }

    public function withIndent(IndentStyle $indent): self
    {
        $clone = clone $this;
        $clone->indent = $indent;

        return $clone;
    }

    public function withNewlineAfterTag(bool $enabled): self
    {
        $clone = clone $this;
        $clone->newlineAfterTag = $enabled;

        return $clone;
    }

    /** @param list<string> $localNames */
    public function withBlankLineBetween(array $localNames): self
    {
        $clone = clone $this;
        $clone->blankLineBetween = array_values($localNames);

        return $clone;
    }

    public function format(DOMDocument $doc): void
    {
        $root = $doc->documentElement;
        if (!$root instanceof DOMElement) {
            return;
        }

        $this->stripWhitespace($root);

        if (!$this->newlineAfterTag) {
            return;
        }

        $this->indent($root, 0);

        if ($this->blankLineBetween !== []) {
            $this->injectSiblingBreaks($root);
        }
    }

    private function stripWhitespace(DOMNode $node): void
    {
        if (!$node->hasChildNodes()) {
            return;
        }

        $hasElementChild = false;
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $hasElementChild = true;
                break;
            }
        }

        if ($hasElementChild) {
            $toRemove = [];
            foreach ($node->childNodes as $child) {
                if ($child instanceof DOMText && trim($child->nodeValue ?? '') === '') {
                    $toRemove[] = $child;
                }
            }
            foreach ($toRemove as $child) {
                $node->removeChild($child);
            }
        }

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $this->stripWhitespace($child);
            }
        }
    }

    private function indent(DOMElement $element, int $depth): void
    {
        $elementChildren = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $elementChildren[] = $child;
            }
        }

        if ($elementChildren === []) {
            return;
        }

        $unit = $this->indent->unit();
        $childIndent = "\n" . str_repeat($unit, $depth + 1);
        $closingIndent = "\n" . str_repeat($unit, $depth);
        $doc = $element->ownerDocument;
        if (!$doc instanceof DOMDocument) {
            return;
        }

        foreach ($elementChildren as $child) {
            $element->insertBefore($doc->createTextNode($childIndent), $child);
        }

        $lastChild = $elementChildren[count($elementChildren) - 1];
        $afterLast = $lastChild->nextSibling;
        if ($afterLast === null) {
            $element->appendChild($doc->createTextNode($closingIndent));
        }

        foreach ($elementChildren as $child) {
            $this->indent($child, $depth + 1);
        }
    }

    private function injectSiblingBreaks(DOMElement $element): void
    {
        $elementChildren = [];
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $elementChildren[] = $child;
            }
        }

        $count = count($elementChildren);
        for ($i = 0; $i < $count - 1; $i++) {
            $current = $elementChildren[$i];
            $next = $elementChildren[$i + 1];
            $localName = $current->localName;

            if ($localName === null) {
                continue;
            }
            if (!in_array($localName, $this->blankLineBetween, true)) {
                continue;
            }
            if ($current->localName !== $next->localName) {
                continue;
            }
            if ($current->namespaceURI !== $next->namespaceURI) {
                continue;
            }

            $tail = $current->nextSibling;
            if ($tail instanceof DOMText) {
                $tail->nodeValue = "\n" . ($tail->nodeValue ?? '');
            }
        }

        foreach ($elementChildren as $child) {
            $this->injectSiblingBreaks($child);
        }
    }
}
