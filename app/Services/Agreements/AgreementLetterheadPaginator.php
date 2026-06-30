<?php

namespace App\Services\Agreements;

use DOMDocument;
use DOMElement;
use DOMNode;

class AgreementLetterheadPaginator
{
    /**
     * Split agreement HTML into page chunks that fit the letterhead content zone.
     *
     * @return list<string>
     */
    public function paginate(string $bodyHtml, array $marginsMm, float $pageHeightMm = 297): array
    {
        $top = (float) ($marginsMm['top'] ?? 48);
        $bottom = (float) ($marginsMm['bottom'] ?? 52);
        $contentHeightMm = max(40, $pageHeightMm - $top - $bottom);
        $budgetPt = $contentHeightMm * (72 / 25.4) * 0.88;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $bodyHtml . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root instanceof DOMElement) {
            return $this->hasMeaningfulHtml($bodyHtml) ? [$bodyHtml] : [];
        }

        $blocks = $this->collectPaginatableNodes($root, $dom);

        if ($blocks === []) {
            return $this->hasMeaningfulHtml($bodyHtml) ? [$bodyHtml] : [];
        }

        $pages = [];
        $currentNodes = [];
        $usedPt = 0.0;

        foreach ($blocks as $child) {
            $estimate = $this->estimateNodeHeightPt($child);

            if ($usedPt + $estimate > $budgetPt && $currentNodes !== []) {
                $pages[] = $this->joinHtml($dom, $currentNodes);
                $currentNodes = [];
                $usedPt = 0.0;
            }

            $currentNodes[] = $child;
            $usedPt += $estimate;

            if ($estimate > $budgetPt && count($currentNodes) === 1) {
                $pages[] = $this->joinHtml($dom, $currentNodes);
                $currentNodes = [];
                $usedPt = 0.0;
            }
        }

        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
        }

        $pages = array_values(array_filter($pages, fn (string $html): bool => $this->hasMeaningfulHtml($html)));

        return $pages !== [] ? $pages : ($this->hasMeaningfulHtml($bodyHtml) ? [$bodyHtml] : []);
    }

    /**
     * @return list<DOMNode>
     */
    private function collectPaginatableNodes(DOMElement $root, DOMDocument $dom): array
    {
        $blocks = [];
        $this->walkNodes($root, $blocks, $dom);

        return $blocks;
    }

    /**
     * @param  list<DOMNode>  $blocks
     */
    private function walkNodes(DOMNode $parent, array &$blocks, DOMDocument $dom): void
    {
        foreach ($parent->childNodes as $node) {
            if ($this->isEmptyNode($node)) {
                continue;
            }

            if ($node->nodeType === XML_TEXT_NODE) {
                $paragraph = $dom->createElement('p');
                $paragraph->textContent = trim((string) $node->textContent);
                $blocks[] = $paragraph;

                continue;
            }

            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);

            if ($tag === 'table') {
                $blocks[] = $node;

                continue;
            }

            if ($tag === 'ul' || $tag === 'ol') {
                $start = $tag === 'ol' ? (int) ($node->getAttribute('start') ?: 1) : 1;
                $index = 0;

                foreach ($node->childNodes as $li) {
                    if (! $li instanceof DOMElement || strtolower($li->tagName) !== 'li') {
                        continue;
                    }

                    if ($this->isEmptyNode($li)) {
                        continue;
                    }

                    $list = $dom->createElement($tag);
                    if ($tag === 'ol' && ($start + $index) > 1) {
                        $list->setAttribute('start', (string) ($start + $index));
                    }
                    $list->appendChild($li->cloneNode(true));
                    $blocks[] = $list;
                    $index++;
                }

                continue;
            }

            if (in_array($tag, ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'blockquote', 'pre', 'img'], true)) {
                $blocks[] = $node;

                continue;
            }

            if (in_array($tag, ['div', 'section', 'article'], true)) {
                $this->walkNodes($node, $blocks, $dom);

                continue;
            }

            $blocks[] = $node;
        }
    }

    private function isEmptyNode(DOMNode $node): bool
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return trim((string) $node->textContent) === '';
        }

        if (! $node instanceof DOMElement) {
            return true;
        }

        if (strtolower($node->tagName) === 'br') {
            return true;
        }

        if (in_array(strtolower($node->tagName), ['img', 'table', 'hr', 'svg'], true)) {
            return false;
        }

        return trim(preg_replace('/\s+/u', '', (string) $node->textContent) ?? '') === '';
    }

    private function hasMeaningfulHtml(string $html): bool
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');

        if ($text !== '') {
            return true;
        }

        return (bool) preg_match('/<(img|table|hr|svg)\b/i', $html);
    }

    /**
     * @param  list<DOMNode>  $nodes
     */
    private function joinHtml(DOMDocument $dom, array $nodes): string
    {
        $html = '';
        foreach ($nodes as $node) {
            $html .= $dom->saveHTML($node);
        }

        return $html;
    }

    private function estimateNodeHeightPt(DOMNode $node): float
    {
        if (! $node instanceof DOMElement) {
            $text = trim($node->textContent ?? '');

            return $text === '' ? 0.0 : $this->estimateTextHeightPt($text, 9.5);
        }

        $tag = strtolower($node->tagName);

        return match ($tag) {
            'table' => $this->estimateTableHeightPt($node),
            'ul', 'ol' => $this->estimateListHeightPt($node),
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => 22 + $this->estimateTextHeightPt($node->textContent ?? '', 10.5),
            'hr' => 14,
            'img' => 90,
            default => $this->estimateBlockHeightPt($node),
        };
    }

    private function estimateTableHeightPt(DOMElement $table): float
    {
        $rows = $table->getElementsByTagName('tr');
        $count = max(1, $rows->length);

        return min(360, ($count * 24) + 16);
    }

    private function estimateListHeightPt(DOMElement $list): float
    {
        $items = $list->getElementsByTagName('li');

        return max(1, $items->length) * 17 + 10;
    }

    private function estimateBlockHeightPt(DOMElement $node): float
    {
        $nestedTables = $node->getElementsByTagName('table');
        if ($nestedTables->length > 0) {
            $total = 10.0;
            foreach ($nestedTables as $table) {
                if ($table instanceof DOMElement) {
                    $total += $this->estimateTableHeightPt($table);
                }
            }

            $text = trim(preg_replace('/\s+/', ' ', strip_tags($node->textContent ?? '')) ?? '');

            return $total + $this->estimateTextHeightPt($text, 9.5);
        }

        $text = trim(preg_replace('/\s+/', ' ', $node->textContent ?? '') ?? '');

        return $this->estimateTextHeightPt($text, 9.5) + 8;
    }

    private function estimateTextHeightPt(string $text, float $fontSizePt): float
    {
        if ($text === '') {
            return 0.0;
        }

        $charsPerLine = 82;
        $lineHeight = $fontSizePt * 1.45;
        $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));

        return ($lines * $lineHeight) + 8;
    }
}
