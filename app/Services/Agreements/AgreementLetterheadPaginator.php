<?php

namespace App\Services\Agreements;

use DOMDocument;
use DOMElement;
use DOMNode;

class AgreementLetterheadPaginator
{
    private const ESTIMATE_SAFETY = 1.0;

    private const CONTENT_FONT_PT = 8.5;

    private const HEADING_FONT_PT = 10.5;

    private const LINE_HEIGHT_RATIO = 1.35;

    private const CHARS_PER_LINE = 105;

    private const MAX_APPEND_DEPTH = 512;

    private float $budgetPt = 0.0;

    private int $appendDepth = 0;

    /**
     * Split agreement HTML into page chunks that fit the content zone.
     *
     * @return list<string>
     */
    public function paginate(string $bodyHtml, float $contentZoneHeightMm): array
    {
        $this->appendDepth = 0;
        $this->budgetPt = max(40, $contentZoneHeightMm) * (72 / 25.4);

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

        if ($this->fitsSinglePage($children)) {
            return [$bodyHtml];
        }

        $pages = [];
        $currentNodes = [];
        $usedPt = 0.0;

        foreach ($children as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'table') {
                $this->appendTableParts($dom, $child, $pages, $currentNodes, $usedPt);

                continue;
            }

            $this->appendNode($dom, $child, $pages, $currentNodes, $usedPt);
        }

        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
        }

        $pages = array_values(array_filter($pages, static function (string $html): bool {
            return trim(strip_tags($html)) !== '';
        }));

        if ($pages !== []) {
            $pages = $this->rebalancePages($pages);
        }

        return $pages !== [] ? $pages : [$bodyHtml];
    }

    /**
     * Merge trailing short pages so content fills pages like the browser preview.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    private function rebalancePages(array $pages): array
    {
        $maxMerges = max(0, count($pages) - 1);
        $merges = 0;

        while (count($pages) >= 2 && $merges < $maxMerges) {
            $last = $pages[count($pages) - 1];
            if ($this->estimateHtmlHeightPt($last) >= $this->budgetPt * 0.32) {
                break;
            }

            $tail = array_pop($pages);
            $prev = array_pop($pages);
            $combined = $prev . $tail;
            if ($this->estimateHtmlHeightPt($combined) > $this->budgetPt * 0.97) {
                $pages[] = $prev;
                $pages[] = $tail;

                break;
            }

            $pages[] = $combined;
            $merges++;
        }

        return $pages;
    }

    private function estimateHtmlHeightPt(string $html): float
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root instanceof DOMElement) {
            return $this->budgetPt;
        }

        $total = 0.0;
        foreach ($this->expandNodes($dom, $root) as $node) {
            $total += $this->safeEstimate($this->estimateNodeHeightPt($node));
        }

        return $total;
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
        if ($this->appendDepth >= self::MAX_APPEND_DEPTH) {
            $this->forcePlaceOversizedNode($dom, $child, $pages, $currentNodes, $usedPt);

            return;
        }

        $this->appendDepth++;

        try {
            $estimate = $this->safeEstimate($this->estimateNodeHeightPt($child));
            $remaining = $this->budgetPt - $usedPt;

            if ($estimate > $remaining && $currentNodes !== []) {
                if ($this->tryFillRemainingSpace($dom, $child, $remaining, $pages, $currentNodes, $usedPt)) {
                    return;
                }

                $pages[] = $this->joinHtml($dom, $currentNodes);
                $currentNodes = [];
                $usedPt = 0.0;
                $remaining = $this->budgetPt;
            }

            if ($estimate > $remaining && $child instanceof DOMElement) {
                $this->appendOversizedElement($dom, $child, $pages, $currentNodes, $usedPt);

                return;
            }

            $currentNodes[] = $child;
            $usedPt += $estimate;
        } finally {
            $this->appendDepth--;
        }
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
                    $baseStart = $tag === 'ol' ? max(1, (int) ($element->getAttribute('start') ?: 1)) : 1;
                    $liIndex = 0;

                    foreach ($this->splitListItem($dom, $li) as $liPart) {
                        $wrapper = $dom->createElement($tag);
                        $this->copyListAttributes($element, $wrapper);

                        if ($tag === 'ol') {
                            $this->setOrderedListStart($wrapper, $baseStart + $liIndex);
                        }

                        $wrapper->appendChild($liPart);
                        $this->appendNodeOrForce($dom, $wrapper, $pages, $currentNodes, $usedPt);
                        $liIndex++;
                    }

                    return;
                }
            }

            foreach ($items as $part) {
                $this->appendNodeOrForce($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return;
        }

        if ($tag === 'table') {
            $this->appendTableParts($dom, $element, $pages, $currentNodes, $usedPt);

            return;
        }

        if (in_array($tag, ['div', 'section', 'article', 'main', 'figure', 'center'], true)) {
            $parts = $this->expandNodes($dom, $element);
            if ($parts === []) {
                $this->forcePlaceOversizedNode($dom, $element, $pages, $currentNodes, $usedPt);

                return;
            }

            foreach ($parts as $part) {
                $this->appendNodeOrForce($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return;
        }

        if (in_array($tag, ['p', 'blockquote'], true)) {
            $parts = $this->splitTextBlock($dom, $element);
            if (
                count($parts) === 1
                && $this->safeEstimate($this->estimateNodeHeightPt($parts[0])) > $this->budgetPt
            ) {
                $parts = $this->splitTextBlockForBudget($dom, $element, $this->budgetPt);
            }
            if (
                count($parts) === 1
                && $this->safeEstimate($this->estimateNodeHeightPt($parts[0])) > $this->budgetPt
            ) {
                $parts = $this->splitTextBlockForced($dom, $element);
            }

            foreach ($parts as $part) {
                $partEstimate = $this->safeEstimate($this->estimateNodeHeightPt($part));
                if ($partEstimate > $this->budgetPt) {
                    $this->forcePlaceOversizedNode($dom, $part, $pages, $currentNodes, $usedPt);

                    continue;
                }

                $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return;
        }

        if ($tag === 'li') {
            foreach ($this->splitListItem($dom, $element) as $liPart) {
                $wrapper = $dom->createElement('ol');
                $wrapper->appendChild($liPart);
                $partEstimate = $this->safeEstimate($this->estimateNodeHeightPt($wrapper));
                if ($partEstimate > $this->budgetPt) {
                    $this->forcePlaceOversizedNode($dom, $wrapper, $pages, $currentNodes, $usedPt);

                    continue;
                }

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
        $remaining = max(0.0, $this->budgetPt - $usedPt);

        if ($remaining < 14 && $currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
            $currentNodes = [];
            $usedPt = 0.0;
            $remaining = $this->budgetPt;
        }

        $parts = $this->splitTable($dom, $table, $remaining);

        foreach ($parts as $part) {
            $this->appendNodeOrForce($dom, $part, $pages, $currentNodes, $usedPt);
        }
    }

    /**
     * Append a node, or place it alone on a page when it cannot be split further.
     *
     * @param  list<DOMNode>  $currentNodes
     */
    private function appendNodeOrForce(
        DOMDocument $dom,
        DOMNode $node,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        if (! $node instanceof DOMElement) {
            $this->appendNode($dom, $node, $pages, $currentNodes, $usedPt);

            return;
        }

        $tag = strtolower($node->tagName);
        $estimate = $this->safeEstimate($this->estimateNodeHeightPt($node));

        if ($estimate > $this->budgetPt && in_array($tag, ['table', 'ul', 'ol', 'p', 'blockquote', 'li'], true)) {
            $this->forcePlaceOversizedNode($dom, $node, $pages, $currentNodes, $usedPt);

            return;
        }

        $this->appendNode($dom, $node, $pages, $currentNodes, $usedPt);
    }

    /**
     * Place an element that exceeds one page on its own page (last resort).
     *
     * @param  list<DOMNode>  $currentNodes
     */
    private function forcePlaceOversizedNode(
        DOMDocument $dom,
        DOMNode $node,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): void {
        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
            $currentNodes = [];
            $usedPt = 0.0;
        }

        $pages[] = $this->joinHtml($dom, [$node]);
        $usedPt = 0.0;
    }

    /**
     * Pack splittable content into the remaining space before starting a new page.
     *
     * @param  list<DOMNode>  $currentNodes
     */
    private function tryFillRemainingSpace(
        DOMDocument $dom,
        DOMNode $child,
        float $remainingPt,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): bool {
        if ($remainingPt < 14 || ! $child instanceof DOMElement) {
            return false;
        }

        $tag = strtolower($child->tagName);

        if (in_array($tag, ['p', 'blockquote'], true)) {
            $parts = $this->splitTextBlockForBudget($dom, $child, $remainingPt);
            if (count($parts) <= 1) {
                return false;
            }

            $first = $parts[0];
            $firstEstimate = $this->safeEstimate($this->estimateNodeHeightPt($first));
            if ($firstEstimate > $remainingPt) {
                return false;
            }

            $currentNodes[] = $first;
            $usedPt += $firstEstimate;

            for ($i = 1; $i < count($parts); $i++) {
                $pages[] = $this->joinHtml($dom, $currentNodes);
                $currentNodes = [];
                $usedPt = 0.0;
                $this->appendNodeOrForce($dom, $parts[$i], $pages, $currentNodes, $usedPt);
            }

            return true;
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            return $this->tryPackListIntoRemaining($dom, $child, $remainingPt, $pages, $currentNodes, $usedPt);
        }

        if ($tag === 'table') {
            $parts = $this->splitTable($dom, $child, $remainingPt);
            if (count($parts) <= 1) {
                return false;
            }

            $first = $parts[0];
            $firstEstimate = $this->safeEstimate($this->estimateNodeHeightPt($first));
            if ($firstEstimate > $remainingPt) {
                return false;
            }

            $currentNodes[] = $first;
            $usedPt += $firstEstimate;

            for ($i = 1; $i < count($parts); $i++) {
                $this->appendNodeOrForce($dom, $parts[$i], $pages, $currentNodes, $usedPt);
            }

            return true;
        }

        return false;
    }

    /**
     * @param  list<DOMNode>  $currentNodes
     */
    private function tryPackListIntoRemaining(
        DOMDocument $dom,
        DOMElement $list,
        float $remainingPt,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): bool {
        $items = $this->splitList($dom, $list);
        if (count($items) <= 1) {
            return false;
        }

        $packed = [];
        $packedHeight = 0.0;
        $splitAt = 0;

        foreach ($items as $index => $item) {
            $itemEstimate = $this->safeEstimate($this->estimateNodeHeightPt($item));
            if ($packedHeight + $itemEstimate > $remainingPt && $packed !== []) {
                break;
            }

            if ($packedHeight + $itemEstimate <= $remainingPt) {
                $packed[] = $item;
                $packedHeight += $itemEstimate;
                $splitAt = $index + 1;
            } elseif ($packed === []) {
                return false;
            }
        }

        if ($packed === [] || $splitAt >= count($items)) {
            return false;
        }

        foreach ($packed as $item) {
            $currentNodes[] = $item;
        }
        $usedPt += $packedHeight;

        for ($i = $splitAt; $i < count($items); $i++) {
            $this->appendNodeOrForce($dom, $items[$i], $pages, $currentNodes, $usedPt);
        }

        return true;
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

        $estimate = $this->safeEstimate($this->estimateTextHeightPt($text, self::CONTENT_FONT_PT) + 4);
        if ($estimate <= $this->budgetPt) {
            return [$block];
        }

        $parts = [];
        $tag = strtolower($block->tagName);
        $chunks = $this->chunkText($text, (int) (self::CHARS_PER_LINE * 3.5));

        foreach ($chunks as $chunk) {
            $node = $dom->createElement($tag);
            $this->copyElementAttributes($block, $node);
            $node->appendChild($dom->createTextNode($chunk));
            $parts[] = $node;
        }

        return $parts !== [] ? $parts : [$block];
    }

    /**
     * @return list<DOMElement>
     */
    private function splitTextBlockForBudget(DOMDocument $dom, DOMElement $block, float $budgetPt): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $block->textContent ?? '') ?? '');
        if ($text === '') {
            return [$block];
        }

        $fullEstimate = $this->safeEstimate($this->estimateTextHeightPt($text, self::CONTENT_FONT_PT) + 4);
        if ($fullEstimate <= $budgetPt) {
            return [$block];
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $firstWords = [];
        $splitIndex = count($words);

        foreach ($words as $index => $word) {
            $candidateWords = array_merge($firstWords, [$word]);
            $candidate = implode(' ', $candidateWords);
            $estimate = $this->safeEstimate($this->estimateTextHeightPt($candidate, self::CONTENT_FONT_PT) + 4);

            if ($estimate <= $budgetPt) {
                $firstWords[] = $word;
            } else {
                $splitIndex = $index;
                break;
            }
        }

        if ($firstWords === []) {
            return [$block];
        }

        $tag = strtolower($block->tagName);
        $parts = [];

        $first = $dom->createElement($tag);
        $this->copyElementAttributes($block, $first);
        $first->appendChild($dom->createTextNode(implode(' ', $firstWords)));
        $parts[] = $first;

        $restWords = array_slice($words, count($firstWords));
        if ($restWords !== []) {
            $rest = $dom->createElement($tag);
            $this->copyElementAttributes($block, $rest);
            $rest->appendChild($dom->createTextNode(implode(' ', $restWords)));
            $parts = array_merge($parts, $this->splitTextBlock($dom, $rest));
        }

        return $parts;
    }

    /**
     * @return list<DOMElement>
     */
    private function splitTextBlockForced(DOMDocument $dom, DOMElement $block): array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $block->textContent ?? '') ?? '');
        if ($text === '') {
            return [$block];
        }

        $tag = strtolower($block->tagName);
        $parts = [];

        foreach ($this->chunkText($text, (int) (self::CHARS_PER_LINE * 2)) as $chunk) {
            $node = $dom->createElement($tag);
            $this->copyElementAttributes($block, $node);
            $node->appendChild($dom->createTextNode($chunk));
            $parts[] = $node;
        }

        return $parts !== [] ? $parts : [$block];
    }

    private function copyElementAttributes(DOMElement $source, DOMElement $target): void
    {
        foreach (['class', 'style', 'id'] as $attribute) {
            if ($source->hasAttribute($attribute)) {
                $target->setAttribute($attribute, $source->getAttribute($attribute));
            }
        }
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

        $estimate = $this->safeEstimate($this->estimateTextHeightPt($text, self::CONTENT_FONT_PT) + 6);
        if ($estimate <= $this->budgetPt) {
            return [$li];
        }

        $parts = [];
        foreach ($this->chunkText($text, (int) (self::CHARS_PER_LINE * 3.5)) as $chunk) {
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
        foreach ($this->chunkText($text, (int) (self::CHARS_PER_LINE * 5)) as $chunk) {
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
            $headerForRow = $batch === [] ? $headerPt : 0.0;
            $nextPt = $usedPt + $rowPt + $headerForRow;

            $limit = $batch === [] ? $budgetPt : $this->budgetPt;

            if ($batch === [] && $nextPt > $limit) {
                $chunks[] = $this->buildTable($dom, $table, $headerRows, [$row]);

                continue;
            }

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

    /**
     * @param  list<DOMNode>  $nodes
     */
    private function fitsSinglePage(array $nodes): bool
    {
        $total = 0.0;

        foreach ($nodes as $node) {
            $total += $this->safeEstimate($this->estimateNodeHeightPt($node));

            if ($total > $this->budgetPt) {
                return false;
            }
        }

        return true;
    }

    private function safeEstimate(float $estimate): float
    {
        return max(6.0, $estimate * self::ESTIMATE_SAFETY);
    }

    private function estimateNodeHeightPt(DOMNode $node): float
    {
        if (! $node instanceof DOMElement) {
            $text = trim($node->textContent ?? '');

            return $text === '' ? 0.0 : $this->estimateTextHeightPt($text, self::CONTENT_FONT_PT);
        }

        $tag = strtolower($node->tagName);

        return match ($tag) {
            'table' => $this->estimateTableHeightPt($node),
            'ul', 'ol' => $this->estimateListHeightPt($node),
            'h1', 'h2', 'h3', 'h4' => 8 + $this->estimateTextHeightPt($node->textContent ?? '', self::HEADING_FONT_PT),
            'hr' => 10,
            'img' => 72,
            'li' => $this->estimateTextHeightPt($node->textContent ?? '', self::CONTENT_FONT_PT) + 4,
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

        return ($maxLines * (self::CONTENT_FONT_PT * self::LINE_HEIGHT_RATIO)) + 4;
    }

    private function estimateCellLines(DOMElement $cell): int
    {
        $html = strtolower($cell->ownerDocument?->saveHTML($cell) ?? '');
        $brLines = max(0, substr_count($html, '<br'));
        $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent ?? '') ?? '');

        return max(1, $brLines + 1, (int) ceil(mb_strlen($text) / 48));
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
        $textHeight = $this->estimateTextHeightPt($text, self::CONTENT_FONT_PT);

        if ($brLines > 0) {
            $lineHeight = self::CONTENT_FONT_PT * self::LINE_HEIGHT_RATIO;
            $textHeight = max($textHeight, ($brLines + 1) * $lineHeight);
        }

        $nestedTables = $node->getElementsByTagName('table');
        if ($nestedTables->length > 0) {
            $total = 2.0;
            foreach ($nestedTables as $table) {
                if ($table instanceof DOMElement) {
                    $total += $this->estimateTableHeightPt($table);
                }
            }

            return $total + $textHeight;
        }

        return $textHeight + 4;
    }

    private function estimateTextHeightPt(string $text, float $fontSizePt): float
    {
        if ($text === '') {
            return 0.0;
        }

        $lineHeight = $fontSizePt * self::LINE_HEIGHT_RATIO;
        $lines = max(1, (int) ceil(mb_strlen($text) / self::CHARS_PER_LINE));

        return ($lines * $lineHeight) + 2;
    }
}
