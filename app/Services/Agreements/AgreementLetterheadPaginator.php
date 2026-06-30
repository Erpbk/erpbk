<?php

namespace App\Services\Agreements;

use DOMDocument;
use DOMElement;
use DOMNode;

class AgreementLetterheadPaginator
{
    private const ESTIMATE_SAFETY = 1.05;

    private const BUDGET_FACTOR = 0.97;

    private float $budgetPt = 0.0;

    /**
     * Split agreement HTML into page chunks that fit the letterhead content zone.
     *
     * @return list<string>
     */
    public function paginate(string $bodyHtml, array $marginsMm, float $pageHeightMm = 297): array
    {
        $top = (float) ($marginsMm['top'] ?? 38);
        $bottom = (float) ($marginsMm['bottom'] ?? 15);
        $contentHeightMm = max(40, $pageHeightMm - $top - $bottom);
<<<<<<< Updated upstream
        $budgetPt = $contentHeightMm * (72 / 25.4) * 0.88;
=======
        $this->budgetPt = $contentHeightMm * (72 / 25.4) * self::BUDGET_FACTOR;
>>>>>>> Stashed changes

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

<<<<<<< Updated upstream
        $blocks = $this->collectPaginatableNodes($root, $dom);
=======
        $children = $this->expandNodes($dom, $root);
>>>>>>> Stashed changes

        if ($blocks === []) {
            return $this->hasMeaningfulHtml($bodyHtml) ? [$bodyHtml] : [];
        }

        $pages = [];
        $currentNodes = [];
        $usedPt = 0.0;

<<<<<<< Updated upstream
        foreach ($blocks as $child) {
            $estimate = $this->estimateNodeHeightPt($child);
=======
        foreach ($children as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'table') {
                $this->appendTableParts($dom, $child, $pages, $currentNodes, $usedPt);
>>>>>>> Stashed changes

                continue;
            }

            $this->appendNode($dom, $child, $pages, $currentNodes, $usedPt);
        }

        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
        }

<<<<<<< Updated upstream
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
=======
        $pages = array_values(array_filter($pages, static function (string $html): bool {
            return trim(strip_tags($html)) !== '';
        }));

        return $pages !== [] ? $pages : [$bodyHtml];
>>>>>>> Stashed changes
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
        $estimate = $this->safeEstimate($this->estimateNodeHeightPt($child));

        if ($usedPt + $estimate > $this->budgetPt && $currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
            $currentNodes = [];
            $usedPt = 0.0;
        }

        if ($estimate > $this->budgetPt && $child instanceof DOMElement) {
            $this->appendOversizedElement($dom, $child, $pages, $currentNodes, $usedPt);

            return;
        }

        $currentNodes[] = $child;
        $usedPt += $estimate;
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
            $items = $this->splitList($dom, $element);
            if (count($items) === 1 && $this->safeEstimate($this->estimateNodeHeightPt($items[0])) > $this->budgetPt) {
                $li = $items[0]->getElementsByTagName('li')->item(0);
                if ($li instanceof DOMElement) {
                    foreach ($this->splitListItem($dom, $li) as $liPart) {
                        $wrapper = $dom->createElement($tag);
                        if ($element->hasAttribute('class')) {
                            $wrapper->setAttribute('class', $element->getAttribute('class'));
                        }
                        if ($element->hasAttribute('style')) {
                            $wrapper->setAttribute('style', $element->getAttribute('style'));
                        }
                        $wrapper->appendChild($liPart);
                        $this->appendNode($dom, $wrapper, $pages, $currentNodes, $usedPt);
                    }

                    return;
                }
            }

            foreach ($items as $part) {
                $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
            }

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
                $this->appendNode($dom, $wrapper, $pages, $currentNodes, $usedPt);
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
        $parts = $this->splitTable($dom, $table, $remaining);

        foreach ($parts as $part) {
            $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
        }
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

            if (in_array($tag, ['ul', 'ol'], true)) {
                foreach ($this->splitList($dom, $child) as $listPart) {
                    $items[] = $listPart;
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

        foreach ($list->childNodes as $child) {
            if (! $child instanceof DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $single = $dom->createElement($tag);
            if ($list->hasAttribute('class')) {
                $single->setAttribute('class', $list->getAttribute('class'));
            }
            if ($list->hasAttribute('style')) {
                $single->setAttribute('style', $list->getAttribute('style'));
            }
            $single->appendChild($child->cloneNode(true));
            $parts[] = $single;
        }

        return $parts !== [] ? $parts : [$list];
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

            return $text === '' ? 0.0 : $this->estimateTextHeightPt($text, 9.5);
        }

        $tag = strtolower($node->tagName);

        return match ($tag) {
            'table' => $this->estimateTableHeightPt($node),
            'ul', 'ol' => $this->estimateListHeightPt($node),
<<<<<<< Updated upstream
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => 22 + $this->estimateTextHeightPt($node->textContent ?? '', 10.5),
            'hr' => 14,
            'img' => 90,
=======
            'h1', 'h2', 'h3', 'h4' => 12 + $this->estimateTextHeightPt($node->textContent ?? '', 10.5),
            'hr' => 12,
            'img' => 80,
            'li' => $this->estimateTextHeightPt($node->textContent ?? '', 9.5) + 5,
>>>>>>> Stashed changes
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
                $total += $this->estimateNodeHeightPt($child);
            }
        }

        return max(10.0, $total);
    }

    private function estimateBlockHeightPt(DOMElement $node): float
    {
        $html = strtolower($node->ownerDocument?->saveHTML($node) ?? '');
        $brLines = max(0, substr_count($html, '<br'));
        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
        $textHeight = $this->estimateTextHeightPt($text, 9.5);

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

        $charsPerLine = 94;
        $lineHeight = $fontSizePt * 1.38;
        $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));

        return ($lines * $lineHeight) + 4;
    }
}
