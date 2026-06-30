<?php

namespace App\Services\Agreements;

use DOMDocument;
use DOMElement;
use DOMNode;

class AgreementLetterheadPaginator
{
    private const ESTIMATE_SAFETY = 1.0;

    private const BUDGET_FACTOR = 0.99;

    private const BODY_FONT_PT = 8.5;

    private float $budgetPt = 0.0;

    /**
     * Split agreement HTML into page chunks that fit the letterhead content zone.
     *
     * @return list<string>
     */
    public function paginate(string $bodyHtml, array $marginsMm, float $pageHeightMm = 297, bool $forPdf = false): array
    {
        $top = (float) ($marginsMm['top'] ?? 38);
        $bottom = (float) ($marginsMm['bottom'] ?? 15);
        $pdfTopExtra = $forPdf ? (float) config('agreement_letterhead.pdf_content_top_extra_mm', 5) : 0.0;
        $contentHeightMm = max(40, $pageHeightMm - $top - $bottom - $pdfTopExtra);
        $this->budgetPt = $contentHeightMm * (72 / 25.4) * self::BUDGET_FACTOR;

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

        $children = $this->expandNodes($dom, $root);

        if ($children === []) {
            return [$bodyHtml];
        }

        $pages = [];
        $currentNodes = [];
        $usedPt = 0.0;

        foreach ($children as $child) {
            $this->appendNode($dom, $child, $pages, $currentNodes, $usedPt);
        }

        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
        }

        $pages = array_values(array_filter($pages, static function (string $html): bool {
            return trim(strip_tags($html)) !== '';
        }));

        return $pages !== [] ? $pages : [$bodyHtml];
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendNode(
        DOMDocument $dom,
        DOMNode $child,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        if ($child instanceof DOMElement) {
            $tag = strtolower($child->tagName);

            if ($tag === 'table') {
                $this->appendTableParts($dom, $child, $pages, $currentNodes, $usedPt);

                return;
            }

            if (in_array($tag, ['ul', 'ol'], true)) {
                $this->appendListParts($dom, $child, $pages, $currentNodes, $usedPt);

                return;
            }
        }

        $estimate = $this->safeEstimate($this->estimateNodeHeightPt($child));
        $remaining = $this->budgetPt - $usedPt;

        if ($estimate <= $remaining) {
            $currentNodes[] = $child;
            $usedPt += $estimate;

            return;
        }

        if ($estimate > $this->budgetPt && $child instanceof DOMElement) {
            $this->flushPage($dom, $pages, $currentNodes, $usedPt);
            $this->appendOversizedElement($dom, $child, $pages, $currentNodes, $usedPt);

            return;
        }

        $this->flushPage($dom, $pages, $currentNodes, $usedPt);

        $currentNodes[] = $child;
        $usedPt += $estimate;
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendListParts(
        DOMDocument $dom,
        DOMElement $list,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        foreach ($this->splitList($dom, $list) as $part) {
            $this->appendListPartElement($dom, $part, $pages, $currentNodes, $usedPt);
        }
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendListPartElement(
        DOMDocument $dom,
        DOMElement $part,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        $partEstimate = $this->safeEstimate($this->estimateNodeHeightPt($part));
        $remaining = $this->budgetPt - $usedPt;

        if ($partEstimate <= $remaining) {
            $currentNodes[] = $part;
            $usedPt += $partEstimate;

            return;
        }

        if ($partEstimate > $this->budgetPt) {
            $li = $part->getElementsByTagName('li')->item(0);
            $tag = strtolower($part->tagName);

            if ($li instanceof DOMElement && in_array($tag, ['ul', 'ol'], true)) {
                $chunks = $this->splitListItem($dom, $li);

                if (count($chunks) <= 1) {
                    $this->flushPage($dom, $pages, $currentNodes, $usedPt);
                    $currentNodes[] = $part;
                    $usedPt += $partEstimate;

                    return;
                }

                $baseStart = $tag === 'ol' ? max(1, (int) ($part->getAttribute('start') ?: 1)) : 1;
                $liIndex = 0;

                foreach ($chunks as $liPart) {
                    $wrapper = $dom->createElement($tag);
                    $this->copyListAttributes($part, $wrapper);

                    if ($tag === 'ol') {
                        $this->setOrderedListStart($wrapper, $baseStart + $liIndex);
                    }

                    $wrapper->appendChild($liPart);
                    $this->appendListPartElement($dom, $wrapper, $pages, $currentNodes, $usedPt);
                    $liIndex++;
                }

                return;
            }

            $this->flushPage($dom, $pages, $currentNodes, $usedPt);
            $currentNodes[] = $part;
            $usedPt += $partEstimate;

            return;
        }

        $this->flushPage($dom, $pages, $currentNodes, $usedPt);

        $currentNodes[] = $part;
        $usedPt += $partEstimate;
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function flushPage(
        DOMDocument $dom,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        if ($currentNodes === []) {
            return;
        }

        $pages[] = $this->joinHtml($dom, $currentNodes);
        $currentNodes = [];
        $usedPt = 0.0;
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendOversizedElement(
        DOMDocument $dom,
        DOMElement $element,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        $tag = strtolower($element->tagName);

        if (in_array($tag, ['ul', 'ol'], true)) {
            $this->appendListParts($dom, $element, $pages, $currentNodes, $usedPt);

            return;
        }

        if ($tag === 'table') {
            $this->appendTableParts($dom, $element, $pages, $currentNodes, $usedPt);

            return;
        }

        if (in_array($tag, ['div', 'section', 'article', 'main', 'figure', 'center'], true)) {
            foreach ($this->expandNodes($dom, $element) as $part) {
                $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return;
        }

        if (in_array($tag, ['p', 'blockquote'], true)) {
            foreach ($this->splitTextBlock($dom, $element) as $part) {
                $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return;
        }

        if ($tag === 'li') {
            foreach ($this->splitListItem($dom, $element) as $liPart) {
                $wrapper = $dom->createElement('ol');
                $wrapper->appendChild($liPart);
                $this->appendListPartElement($dom, $wrapper, $pages, $currentNodes, $usedPt);
            }

            return;
        }

        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
            $currentNodes = [];
            $usedPt = 0.0;
        }

        $currentNodes[] = $element;
        $usedPt = $this->safeEstimate($this->estimateNodeHeightPt($element));
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendTableParts(
        DOMDocument $dom,
        DOMElement $table,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        $remaining = max(40.0, $this->budgetPt - $usedPt);

        foreach ($this->splitTable($dom, $table, $remaining) as $part) {
            $this->appendTablePartElement($dom, $part, $pages, $currentNodes, $usedPt);
        }
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendTablePartElement(
        DOMDocument $dom,
        DOMElement $part,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        $partEstimate = $this->safeEstimate($this->estimateNodeHeightPt($part));
        $remaining = $this->budgetPt - $usedPt;

        if ($partEstimate <= $remaining) {
            $currentNodes[] = $part;
            $usedPt += $partEstimate;

            return;
        }

        $this->flushPage($dom, $pages, $currentNodes, $usedPt);

        $currentNodes[] = $part;
        $usedPt += $partEstimate;
    }

    /**
     * @return list<DOMNode>
     */
    private function expandNodes(DOMDocument $dom, DOMElement $root): array
    {
        $items = [];

        foreach ($root->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text = trim(preg_replace('/\s+/u', ' ', $child->textContent ?? '') ?? '');
                if ($text !== '') {
                    foreach ($this->splitPlainText($dom, $text) as $paragraph) {
                        $items[] = $paragraph;
                    }
                }

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['div', 'section', 'article', 'main', 'figure', 'center'], true)) {
                foreach ($this->expandNodes($dom, $child) as $nested) {
                    $items[] = $nested;
                }

                continue;
            }

            if (in_array($tag, ['p', 'blockquote'], true)) {
                foreach ($this->splitTextBlock($dom, $child) as $part) {
                    $items[] = $part;
                }

                continue;
            }

            $items[] = $child;
        }

        return $items;
    }

    /**
     * @return list<DOMElement>
     */
    private function splitList(DOMDocument $dom, DOMElement $list): array
    {
        $tag = strtolower($list->tagName);
        $parts = [];
        $index = 0;
        $baseStart = $tag === 'ol' ? max(1, (int) ($list->getAttribute('start') ?: 1)) : 1;

        foreach ($list->childNodes as $child) {
            if (! $child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $single = $dom->createElement($tag);
            $this->copyListAttributes($list, $single);

            if ($tag === 'ol') {
                $this->setOrderedListStart($single, $baseStart + $index);
            }

            $single->appendChild($child->cloneNode(true));
            $parts[] = $single;
            $index++;
        }

        return $parts !== [] ? $parts : [$list];
    }

    private function copyListAttributes(DOMElement $source, DOMElement $target): void
    {
        foreach (['class', 'style', 'type'] as $attribute) {
            if ($source->hasAttribute($attribute)) {
                $target->setAttribute($attribute, $source->getAttribute($attribute));
            }
        }
    }

    private function setOrderedListStart(DOMElement $list, int $start): void
    {
        if ($start > 1) {
            $list->setAttribute('start', (string) $start);
        }
    }

    /**
     * @return list<DOMElement>
     */
    private function splitTextBlock(DOMDocument $dom, DOMElement $block): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $block->textContent ?? '') ?? '');
        if ($text === '') {
            return [$block];
        }

        $estimate = $this->safeEstimate($this->estimateTextHeightPt($text, 9.5) + 4);
        if ($estimate <= $this->budgetPt) {
            return [$block];
        }

        $parts = [];
        $tag = strtolower($block->tagName);
        $chunks = $this->chunkText($text, 380);

        foreach ($chunks as $chunk) {
            $node = $dom->createElement($tag);
            if ($block->hasAttribute('class')) {
                $node->setAttribute('class', $block->getAttribute('class'));
            }
            if ($block->hasAttribute('style')) {
                $node->setAttribute('style', $block->getAttribute('style'));
            }
            $node->appendChild($dom->createTextNode($chunk));
            $parts[] = $node;
        }

        return $parts !== [] ? $parts : [$block];
    }

    /**
     * @return list<DOMElement>
     */
    private function splitListItem(DOMDocument $dom, DOMElement $li): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $li->textContent ?? '') ?? '');
        if ($text === '') {
            return [$li];
        }

        $estimate = $this->safeEstimate($this->estimateTextHeightPt($text, 9.5) + 8);
        if ($estimate <= $this->budgetPt) {
            return [$li];
        }

        $parts = [];
        foreach ($this->chunkText($text, 380) as $chunk) {
            $node = $dom->createElement('li');
            if ($li->hasAttribute('class')) {
                $node->setAttribute('class', $li->getAttribute('class'));
            }
            if ($li->hasAttribute('style')) {
                $node->setAttribute('style', $li->getAttribute('style'));
            }
            $node->appendChild($dom->createTextNode($chunk));
            $parts[] = $node;
        }

        return $parts !== [] ? $parts : [$li];
    }

    /**
     * @return list<DOMElement>
     */
    private function splitPlainText(DOMDocument $dom, string $text): array
    {
        $parts = [];
        foreach ($this->chunkText($text, 520) as $chunk) {
            $p = $dom->createElement('p');
            $p->appendChild($dom->createTextNode($chunk));
            $parts[] = $p;
        }

        return $parts;
    }

    /**
     * @return list<string>
     */
    private function chunkText(string $text, int $maxChars): array
    {
        if (mb_strlen($text) <= $maxChars) {
            return [$text];
        }

        $chunks = [];
        $words = preg_split('/\s+/u', $text) ?: [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current . ' ' . $word;
            if (mb_strlen($candidate) > $maxChars && $current !== '') {
                $chunks[] = $current;
                $current = $word;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks !== [] ? $chunks : [$text];
    }

    /**
     * @return list<DOMElement>
     */
    private function splitTable(DOMDocument $dom, DOMElement $table, float $budgetPt): array
    {
        [$headerRows, $bodyRows] = $this->tableRows($table);

        if ($bodyRows === []) {
            return [$table];
        }

        $fullEstimate = $this->safeEstimate($this->estimateTableHeightPt($table));
        if ($fullEstimate <= $budgetPt) {
            return [$table];
        }

        $headerPt = $this->estimateRowsHeightPt($headerRows);
        $chunks = [];
        $batch = [];
        $usedPt = 0.0;

        foreach ($bodyRows as $row) {
            $rowPt = $this->estimateRowHeightPt($row);
            $nextPt = $usedPt + $rowPt + ($batch === [] ? $headerPt : 0);

            $limit = $batch === [] ? $budgetPt : $this->budgetPt;

            if ($nextPt > $limit && $batch !== []) {
                $chunks[] = $this->buildTable($dom, $table, $headerRows, $batch);
                $batch = [];
                $usedPt = 0.0;
                $budgetPt = $this->budgetPt;
                $nextPt = $rowPt + $headerPt;
            }

            $batch[] = $row;
            $usedPt += $rowPt;
        }

        if ($batch !== []) {
            $chunks[] = $this->buildTable($dom, $table, $headerRows, $batch);
        }

        return $chunks !== [] ? $chunks : [$table];
    }

    /**
     * @return array{0: list<DOMElement>, 1: list<DOMElement>}
     */
    private function tableRows(DOMElement $table): array
    {
        $headerRows = [];
        $bodyRows = [];

        $thead = $table->getElementsByTagName('thead')->item(0);
        if ($thead instanceof DOMElement) {
            foreach ($thead->getElementsByTagName('tr') as $tr) {
                if ($tr instanceof DOMElement) {
                    $headerRows[] = $tr;
                }
            }
        }

        $tbody = $table->getElementsByTagName('tbody')->item(0);
        if ($tbody instanceof DOMElement) {
            foreach ($tbody->getElementsByTagName('tr') as $tr) {
                if ($tr instanceof DOMElement) {
                    $bodyRows[] = $tr;
                }
            }
        }

        if ($bodyRows === []) {
            foreach ($table->getElementsByTagName('tr') as $tr) {
                if (! $tr instanceof DOMElement) {
                    continue;
                }

                if ($thead instanceof DOMElement && $this->nodeIsDescendantOf($tr, $thead)) {
                    continue;
                }

                if ($headerRows === [] && $tr->getElementsByTagName('th')->length > 0) {
                    $headerRows[] = $tr;

                    continue;
                }

                $bodyRows[] = $tr;
            }
        }

        return [$headerRows, $bodyRows];
    }

    /**
     * @param  list<DOMElement>  $headerRows
     * @param  list<DOMElement>  $bodyRows
     */
    private function buildTable(
        DOMDocument $dom,
        DOMElement $sourceTable,
        array $headerRows,
        array $bodyRows
    ): DOMElement {
        $table = $dom->createElement('table');

        if ($sourceTable->hasAttribute('class')) {
            $table->setAttribute('class', $sourceTable->getAttribute('class'));
        }

        if ($sourceTable->hasAttribute('style')) {
            $table->setAttribute('style', $sourceTable->getAttribute('style'));
        }

        if ($headerRows !== []) {
            $thead = $dom->createElement('thead');
            foreach ($headerRows as $row) {
                $thead->appendChild($row->cloneNode(true));
            }
            $table->appendChild($thead);
        }

        $tbody = $dom->createElement('tbody');
        foreach ($bodyRows as $row) {
            $tbody->appendChild($row->cloneNode(true));
        }
        $table->appendChild($tbody);

        return $table;
    }

    private function nodeIsDescendantOf(DOMNode $node, DOMElement $ancestor): bool
    {
        $parent = $node->parentNode;
        while ($parent instanceof DOMNode) {
            if ($parent === $ancestor) {
                return true;
            }
            $parent = $parent->parentNode;
        }

        return false;
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

    private function safeEstimate(float $estimate): float
    {
        return max(6.0, $estimate * self::ESTIMATE_SAFETY);
    }

    private function estimateNodeHeightPt(DOMNode $node): float
    {
        if (! $node instanceof DOMElement) {
            $text = trim($node->textContent ?? '');

            return $text === '' ? 0.0 : $this->estimateTextHeightPt($text, self::BODY_FONT_PT);
        }

        $tag = strtolower($node->tagName);

        return match ($tag) {
            'table' => $this->estimateTableHeightPt($node),
            'ul', 'ol' => $this->estimateListHeightPt($node),
            'h1', 'h2', 'h3', 'h4' => 12 + $this->estimateTextHeightPt($node->textContent ?? '', 10.5),
            'hr' => 12,
            'img' => 80,
            'li' => $this->estimateListItemHeightPt($node),
            default => $this->estimateBlockHeightPt($node),
        };
    }

    private function estimateTableHeightPt(DOMElement $table): float
    {
        [$headerRows, $bodyRows] = $this->tableRows($table);

        return $this->estimateRowsHeightPt($headerRows) + array_sum(array_map(
            fn(DOMElement $row): float => $this->estimateRowHeightPt($row),
            $bodyRows
        ));
    }

    /**
     * @param  list<DOMElement>  $rows
     */
    private function estimateRowsHeightPt(array $rows): float
    {
        if ($rows === []) {
            return 0.0;
        }

        return array_sum(array_map(
            fn(DOMElement $row): float => $this->estimateRowHeightPt($row),
            $rows
        )) + 4;
    }

    private function estimateRowHeightPt(DOMElement $row): float
    {
        $maxLines = 1;

        foreach ($row->getElementsByTagName('td') as $cell) {
            if (! $cell instanceof DOMElement) {
                continue;
            }

            $maxLines = max($maxLines, $this->estimateCellLines($cell));
        }

        foreach ($row->getElementsByTagName('th') as $cell) {
            if (! $cell instanceof DOMElement) {
                continue;
            }

            $maxLines = max($maxLines, $this->estimateCellLines($cell));
        }

        return ($maxLines * 15) + 6;
    }

    private function estimateCellLines(DOMElement $cell): int
    {
        $html = strtolower($cell->ownerDocument?->saveHTML($cell) ?? '');
        $brLines = max(0, substr_count($html, '<br'));
        $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent ?? '') ?? '');

        return max(1, $brLines + 1, (int) ceil(mb_strlen($text) / 42));
    }

    private function estimateListHeightPt(DOMElement $list): float
    {
        $total = 4.0;
        foreach ($list->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                $total += $this->estimateListItemHeightPt($child);
            }
        }

        return max(10.0, $total);
    }

    private function estimateListItemHeightPt(DOMElement $li): float
    {
        $height = 4.0;
        $directText = '';

        foreach ($li->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $directText .= ' ' . ($child->textContent ?? '');

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['ul', 'ol', 'table'], true)) {
                $height += $this->estimateNodeHeightPt($child);

                continue;
            }

            $height += $this->estimateBlockHeightPt($child);
        }

        $directText = trim(preg_replace('/\s+/u', ' ', $directText) ?? '');
        if ($directText !== '') {
            $height += $this->estimateTextHeightPt($directText, self::BODY_FONT_PT);
        }

        return max(8.0, $height);
    }

    private function estimateBlockHeightPt(DOMElement $node): float
    {
        $html = strtolower($node->ownerDocument?->saveHTML($node) ?? '');
        $brLines = max(0, substr_count($html, '<br'));
        $text = trim(preg_replace('/\s+/u', ' ', $this->directTextContent($node)) ?? '');
        $textHeight = $this->estimateTextHeightPt($text, self::BODY_FONT_PT);

        if ($brLines > 0) {
            $textHeight = max($textHeight, ($brLines + 1) * 13.5);
        }

        $nestedTables = $node->getElementsByTagName('table');
        if ($nestedTables->length > 0) {
            $total = 4.0;
            foreach ($nestedTables as $table) {
                if ($table instanceof DOMElement) {
                    $total += $this->estimateTableHeightPt($table);
                }
            }

            return $total + $textHeight;
        }

        return $textHeight + 6;
    }

    private function estimateTextHeightPt(string $text, float $fontSizePt): float
    {
        if ($text === '') {
            return 0.0;
        }

        $charsPerLine = 96;
        $lineHeight = $fontSizePt * 1.4;
        $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));

        return ($lines * $lineHeight) + 4;
    }

    private function directTextContent(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return (string) ($node->textContent ?? '');
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text .= ' ' . ($child->textContent ?? '');

                continue;
            }

            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), ['ul', 'ol', 'table'], true)) {
                continue;
            }

            $text .= ' ' . $this->directTextContent($child);
        }

        return $text;
    }
}
