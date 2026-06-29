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
        $budgetPt = $contentHeightMm * (72 / 25.4) * 0.72;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $bodyHtml . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root) {
            return [$bodyHtml];
        }

        $children = [];
        foreach ($root->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE && trim($child->textContent ?? '') === '') {
                continue;
            }
            $children[] = $child;
        }

        if ($children === []) {
            return [$bodyHtml];
        }

        $pages = [];
        $currentNodes = [];
        $usedPt = 0.0;

        foreach ($children as $child) {
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

        return $pages !== [] ? $pages : [$bodyHtml];
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
            'h1', 'h2', 'h3', 'h4' => 22 + $this->estimateTextHeightPt($node->textContent ?? '', 10.5),
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
