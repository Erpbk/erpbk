<?php

namespace App\Services\Agreements;

use DOMDocument;
use DOMElement;
use DOMNode;

class AgreementLetterheadPaginator
{
    private const CONTENT_FONT_PT = 11.0;

    private const HEADING_FONT_PT = 14.0;

    /**
     * Dompdf/Chrome-era defaults. Live mPDF path uses tighter metrics via
     * estimateScaleFactor() / defaultLineHeightRatio() / paragraphMarginEm().
     */
    private const ESTIMATE_TO_CHROME = 1.08;

    private const ESTIMATE_TO_MPDF = 1.03;

    private const LINE_HEIGHT_RATIO_CHROME = 1.5;

    /** Matches AgreementPdfService mPDF lineHeight * 0.9 when config is 1.5. */
    private const LINE_HEIGHT_RATIO_MPDF = 1.35;

    private const PARAGRAPH_MARGIN_EM_CHROME = 0.5;

    /** Matches letterhead.blade.php mPDF .content p { margin: 0 0 0.32em }. */
    private const PARAGRAPH_MARGIN_EM_MPDF = 0.32;

    /** @deprecated Prefer estimateScaleFactor(). */
    private const ESTIMATE_TO_DOMPDF = self::ESTIMATE_TO_CHROME;

    /** @deprecated Prefer defaultLineHeightRatio(). */
    private const LINE_HEIGHT_RATIO = self::LINE_HEIGHT_RATIO_CHROME;

    private const CHARS_PER_LINE = 92;

    private const MAX_APPEND_DEPTH = 512;

    private float $budgetPt = 0.0;

    private int $appendDepth = 0;

    /** chrome | mpdf | html (preview). Drives height-estimate calibration. */
    private string $pdfEngine = 'mpdf';

    /**
     * Split agreement HTML into page chunks that fit the content zone
     * (page height minus the category's top and bottom margins).
     * Insert → Page break markers are hard splits. Visual editor gutters
     * (.word-page-gap) are stripped and never become PDF pages.
     *
     * @return list<string>
     */
    public function paginate(string $bodyHtml, float $contentZoneHeightMm, ?string $pdfEngine = null): array
    {
        $this->appendDepth = 0;
        $this->pdfEngine = $this->normalizePdfEngine($pdfEngine);
        $this->budgetPt = max(40, $contentZoneHeightMm) * (72 / 25.4);
        $bodyHtml = $this->normalizeBodyHtml($bodyHtml);

        $pages = [];
        foreach ($this->splitForcedBreakSegments($bodyHtml) as $segment) {
            if ($this->isBlankHtml($segment)) {
                continue;
            }

            $pages = array_merge($pages, $this->paginateUnmarked($segment));
        }

        return $pages !== [] ? $pages : [$bodyHtml];
    }

    /**
     * @return list<string>
     */
    private function paginateUnmarked(string $bodyHtml): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $bodyHtml . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
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
            if ($this->isForcedPageBreak($child)) {
                if ($currentNodes !== []) {
                    $pages[] = $this->joinHtml($dom, $currentNodes);
                    $currentNodes = [];
                    $usedPt = 0.0;
                }

                continue;
            }

            if ($this->isVisuallyEmpty($child)) {
                continue;
            }

            if ($child instanceof DOMElement && strtolower($child->tagName) === 'table') {
                $this->appendTableParts($dom, $child, $pages, $currentNodes, $usedPt);

                continue;
            }

            $this->appendNode($dom, $child, $pages, $currentNodes, $usedPt);
        }

        if ($currentNodes !== []) {
            $pages[] = $this->joinHtml($dom, $currentNodes);
        }

        $pages = array_values(array_filter($pages, fn (string $html): bool => ! $this->isBlankHtml($html)));

        if ($pages !== []) {
            $pages = $this->rebalancePages($pages);
            $pages = $this->pullLeadingBlocks($pages);
            $pages = $this->fillUnderflowPages($pages);
            $pages = $this->rebalancePages($pages);
            $pages = $this->absorbTinyIntermediatePages($pages);
            $pages = $this->attachOrphanImagePages($pages);
        }

        return $pages !== [] ? $pages : [$bodyHtml];
    }

    /**
     * Pack consecutive short pages so each page uses the content zone.
     * Over-estimated heights previously left page 2/4 almost empty.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    private function rebalancePages(array $pages): array
    {
        if (count($pages) < 2) {
            return $pages;
        }

        $filled = [];
        foreach ($pages as $page) {
            if ($filled === []) {
                $filled[] = $page;

                continue;
            }

            $prev = array_pop($filled);
            $combined = $prev . $page;
            if ($this->estimateHtmlHeightPt($combined) <= $this->packBudgetPt()) {
                $filled[] = $combined;

                continue;
            }

            $filled[] = $prev;
            $filled[] = $page;
        }

        return $filled;
    }

    /**
     * A trailing photo should sit on the last content sheet. Empty editor
     * paragraphs packed ahead of it must not force a blank extra page.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    private function attachOrphanImagePages(array $pages): array
    {
        while (count($pages) >= 2) {
            $last = $pages[array_key_last($pages)];
            if (! $this->isImageOnlyHtml($last)) {
                break;
            }

            $prevIndex = count($pages) - 2;
            $prev = $this->stripTrailingEmptyBlocks($pages[$prevIndex]);
            if ($this->estimateHtmlHeightPt($prev.$last) > $this->packBudgetPt()) {
                $pages[$prevIndex] = $prev;

                break;
            }

            $pages[$prevIndex] = $prev.$last;
            array_pop($pages);
        }

        return $this->attachShortTrailingPages(array_values($pages));
    }

    /**
     * Pull a short final sheet (signature / title line) onto the previous page.
     * Orphan-heading rules otherwise leave "Name & Signature" alone and mPDF may
     * suppress that trailing near-empty page.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    private function attachShortTrailingPages(array $pages): array
    {
        while (count($pages) >= 2) {
            $last = $pages[array_key_last($pages)];
            if ($this->isBlankHtml($last)) {
                array_pop($pages);

                continue;
            }

            $text = html_entity_decode(strip_tags($last), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;
            $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
            $estPt = $this->estimateHtmlHeightPt($last);
            $short = mb_strlen($text) <= 120 || $estPt <= $this->packBudgetPt() * 0.18;
            if (! $short) {
                break;
            }

            $prevIndex = count($pages) - 2;
            $prev = $this->stripTrailingEmptyBlocks($pages[$prevIndex]);
            $combined = $prev.$last;
            // Modest over-budget slack: PHP estimates still run a bit high vs mPDF paint.
            $limit = $this->packBudgetPt() * ($this->usesMpdfMetrics() ? 1.08 : 1.02);
            if ($this->estimateHtmlHeightPt($combined) > $limit) {
                $pages[$prevIndex] = $prev;

                break;
            }

            $pages[$prevIndex] = $combined;
            array_pop($pages);
        }

        return array_values($pages);
    }

    private function isImageOnlyHtml(string $html): bool
    {
        if (preg_match('/<img\b/i', $html) !== 1) {
            return false;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;

        return trim($text) === '';
    }

    private function stripTrailingEmptyBlocks(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        while ($root->lastChild) {
            $last = $root->lastChild;
            if ($last->nodeType === XML_TEXT_NODE && trim((string) $last->textContent) === '') {
                $root->removeChild($last);

                continue;
            }

            if ($last instanceof DOMElement && $this->isEmptyFlowBlock($last)) {
                $root->removeChild($last);

                continue;
            }

            break;
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    private function isEmptyFlowBlock(DOMElement $node): bool
    {
        if ($node->getElementsByTagName('img')->length > 0) {
            return false;
        }

        $tag = strtolower($node->tagName);
        if (! in_array($tag, ['p', 'div', 'blockquote', 'center', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            return false;
        }

        $text = html_entity_decode(strip_tags($node->ownerDocument?->saveHTML($node) ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;

        return trim($text) === '';
    }

    /**
     * mPDF paint often finishes short of PHP height estimates (esp. tables),
     * leaving multi-mm empty bottoms on "full" pages when bottom margin is 0.
     * After the normal pull pass, keep drawing from the next page while a
     * near-full non-final page still has estimate headroom under an overshoot
     * budget. Pages with clear estimated unused space keep the normal budget
     * so accurately estimated sheets are not over-filled into a clip.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    /**
     * Merge very short middle sheets into neighbors. Fill/orphan co-pull can
     * leave a 1–2 block stranded page between fuller sheets.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    private function absorbTinyIntermediatePages(array $pages): array
    {
        if (count($pages) < 3) {
            return $pages;
        }

        $tinyLimit = $this->packBudgetPt() * 0.22;
        $index = 1;
        while ($index < count($pages) - 1) {
            $est = $this->estimateHtmlHeightPt($pages[$index]);
            if ($est > $tinyLimit) {
                $index++;

                continue;
            }

            $prev = $this->stripTrailingEmptyBlocks($pages[$index - 1]);
            $combinedPrev = $prev . $pages[$index];
            $prevLimit = $this->usesMpdfMetrics()
                ? $this->packBudgetPt() + $this->mpdfFillOvershootPt()
                : $this->packBudgetPt();
            if ($this->estimateHtmlHeightPt($combinedPrev) <= $prevLimit) {
                $pages[$index - 1] = $combinedPrev;
                array_splice($pages, $index, 1);

                continue;
            }

            $next = $pages[$index] . $pages[$index + 1];
            if ($this->estimateHtmlHeightPt($next) <= $this->packBudgetPt() * ($this->usesMpdfMetrics() ? 1.08 : 1.02)) {
                $pages[$index + 1] = $next;
                array_splice($pages, $index, 1);

                continue;
            }

            $index++;
        }

        return array_values($pages);
    }

    private function fillUnderflowPages(array $pages): array
    {
        if (count($pages) < 2 || ! $this->usesMpdfMetrics()) {
            return $pages;
        }

        $index = 0;
        while ($index < count($pages) - 1) {
            $limit = $this->fillPackLimitPt($pages[$index]);
            $est = $this->estimateHtmlHeightPt($pages[$index]);
            // Stop when within ~3mm of the fill limit (safety against clip).
            if ($limit - $est < $this->fillSafetyEpsilonPt()) {
                $index++;

                continue;
            }

            $moved = $this->moveLeadingMarkup($pages[$index], $pages[$index + 1], $limit);
            if ($moved === null) {
                $index++;

                continue;
            }

            $pages[$index] = $moved[0];
            if ($this->isBlankHtml($moved[1])) {
                array_splice($pages, $index + 1, 1);

                continue;
            }

            $pages[$index + 1] = $moved[1];
        }

        return array_values($pages);
    }

    /**
     * Near-full pages get modest estimate overshoot; sparse pages use the
     * true content-zone budget so we do not stack content onto under-estimated
     * sheets (which already paint close to the bottom).
     */
    private function fillPackLimitPt(string $pageHtml): float
    {
        $budget = $this->packBudgetPt();
        $est = $this->estimateHtmlHeightPt($pageHtml);
        if ($est >= $budget - $this->fillNearFullEpsilonPt()) {
            return $budget + $this->mpdfFillOvershootPt();
        }

        return $budget;
    }

    /** ~3mm — treat estimate within this of budget as "full". */
    private function fillNearFullEpsilonPt(): float
    {
        // ~4mm — only pages that already look full get estimate overshoot.
        return 22.0;
    }

    /** ~2mm leftover under the fill limit before stopping. */
    private function fillSafetyEpsilonPt(): float
    {
        return 7.0;
    }

    /**
     * Extra estimate room (~18mm) when a page already looks full — closes the
     * systematic PHP-vs-mPDF gap without lowering the global scale (which would
     * over-pack accurately estimated text pages).
     */
    private function mpdfFillOvershootPt(): float
    {
        // ~8mm estimate slack for title+first-line co-pull on near-full pages.
        return 0.0;
    }

    /**
     * After whole-page merges, move leading blocks from the next page onto any
     * page that still has leftover content-zone space.
     *
     * @param  list<string>  $pages
     * @return list<string>
     */
    private function pullLeadingBlocks(array $pages): array
    {
        if (count($pages) < 2) {
            return $pages;
        }

        $index = 0;
        while ($index < count($pages) - 1) {
            $moved = $this->moveLeadingMarkup($pages[$index], $pages[$index + 1]);
            if ($moved === null) {
                $index++;

                continue;
            }

            $pages[$index] = $moved[0];
            if ($this->isBlankHtml($moved[1])) {
                array_splice($pages, $index + 1, 1);

                continue;
            }

            $pages[$index + 1] = $moved[1];
        }

        return array_values($pages);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function moveLeadingMarkup(string $current, string $next, ?float $packLimitPt = null): ?array
    {
        $limit = $packLimitPt ?? $this->packBudgetPt();
        $parts = $this->htmlToMarkupParts($next);
        if ($parts === []) {
            return null;
        }

        $first = array_shift($parts);
        if ($this->isBlankHtml($first)) {
            $combinedBlank = $current . $first;
            if ($this->estimateHtmlHeightPt($combinedBlank) <= $limit) {
                return [$combinedBlank, implode('', $parts)];
            }

            return null;
        }

        // Keep section titles with their following body. Prefer pulling the
        // title + next block (or a slice) together so leftover zone is used
        // without stranding "d. Target Revisions" alone on the prior sheet.
        if ($this->markupIsOrphanHeading($first) && ! $this->isBlankHtml(implode('', $parts))) {
            return $this->moveOrphanHeadingWithBody($current, $first, $parts, $limit);
        }

        $combined = $current . $first;
        if ($this->estimateHtmlHeightPt($combined) <= $limit) {
            return [$combined, implode('', $parts)];
        }

        $leftover = $limit - $this->estimateHtmlHeightPt($current);
        if ($leftover < self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
            return null;
        }

        $sliced = $this->sliceMarkupForBudget($first, $leftover);
        if ($sliced === null) {
            return null;
        }

        return [$current . $sliced[0], $sliced[1] . implode('', $parts)];
    }

    /**
     * @param  list<string>  $parts
     * @return array{0: string, 1: string}|null
     */
    private function moveOrphanHeadingWithBody(string $current, string $heading, array $parts, float $limit): ?array
    {
        $headingEst = $this->estimateHtmlHeightPt($heading);
        $leftover = $limit - $this->estimateHtmlHeightPt($current);
        if ($leftover < $headingEst + self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
            return null;
        }

        $body = array_shift($parts);
        if ($body === null || $this->isBlankHtml($body)) {
            return null;
        }

        $together = $current . $heading . $body;
        if ($this->estimateHtmlHeightPt($together) <= $limit) {
            return [$together, implode('', $parts)];
        }

        $bodyBudget = $leftover - $headingEst;
        if ($bodyBudget < self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
            return null;
        }

        $sliced = $this->sliceMarkupForBudget($body, $bodyBudget);
        if ($sliced === null) {
            return null;
        }

        return [$current . $heading . $sliced[0], $sliced[1] . implode('', $parts)];
    }

    private function markupIsOrphanHeading(string $html): bool
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        $root = $dom->getElementById('root');
        if (! $root instanceof DOMElement) {
            return false;
        }

        $elements = [];
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $elements[] = $child;
            }
        }

        return count($elements) === 1 && $this->isOrphanHeading($elements[0]);
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private function sliceMarkupForBudget(string $html, float $budgetPt): ?array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root instanceof DOMElement) {
            return null;
        }

        $nodes = [];
        foreach ($this->expandNodes($dom, $root) as $node) {
            if ($this->isForcedPageBreak($node) || $this->isVisuallyEmpty($node)) {
                continue;
            }
            $nodes[] = $node;
        }

        if ($nodes === []) {
            return null;
        }

        $packed = [];
        $packedHeight = 0.0;
        $splitAt = 0;

        foreach ($nodes as $index => $node) {
            $estimate = $this->safeEstimate($this->estimateNodeHeightPt($node));
            if ($packedHeight + $estimate <= $budgetPt) {
                $packed[] = $node;
                $packedHeight += $estimate;
                $splitAt = $index + 1;

                continue;
            }

            if ($node instanceof DOMElement) {
                $sliced = $this->sliceElementForBudget($dom, $node, $budgetPt - $packedHeight);
                if ($sliced !== null) {
                    $packed[] = $sliced[0];
                    $rest = array_merge([$sliced[1]], array_slice($nodes, $index + 1));

                    return [$this->joinHtml($dom, $packed), $this->joinHtml($dom, $rest)];
                }
            }

            break;
        }

        if ($packed === [] || $splitAt >= count($nodes)) {
            return null;
        }

        return [
            $this->joinHtml($dom, $packed),
            $this->joinHtml($dom, array_slice($nodes, $splitAt)),
        ];
    }

    /**
     * @return list<string>
     */
    private function htmlToMarkupParts(string $html): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root instanceof DOMElement) {
            return [$html];
        }

        $parts = [];
        foreach ($this->expandNodes($dom, $root) as $node) {
            if ($this->isForcedPageBreak($node) || $this->isVisuallyEmpty($node)) {
                continue;
            }

            $parts[] = $this->joinHtml($dom, [$node]);
        }

        return $parts;
    }

    private function estimateHtmlHeightPt(string $html): float
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
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

                // Print/PDF start a new letterhead sheet for every chunk. Do not
                // flush a page that still has usable content-zone space — split
                // the next block onto this page instead of leaving a white gap.
                // Exception: never squeeze a table/list under a section title —
                // that strands the title alone when the fragment is empty/tiny.
                if (
                    $remaining >= $this->minUsefulLeftoverPt()
                    && $child instanceof DOMElement
                    && ! $this->shouldDeferOversizedToKeepHeading($currentNodes, $child)
                    && $this->continueOversizedOnCurrentPage($dom, $child, $pages, $currentNodes, $usedPt)
                ) {
                    return;
                }

                $carry = $this->popTrailingHeading($currentNodes, $usedPt);
                if ($currentNodes !== []) {
                    $pages[] = $this->joinHtml($dom, $currentNodes);
                }
                $currentNodes = $carry;
                $usedPt = 0.0;
                foreach ($currentNodes as $carried) {
                    $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($carried));
                }
                $remaining = $this->budgetPt - $usedPt;
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
     * Keep a section title with the block that follows it instead of leaving
     * the heading alone at the bottom of a page.
     *
     * @param  list<DOMNode>  $currentNodes
     * @return list<DOMNode>
     */
    private function popTrailingHeading(array &$currentNodes, float &$usedPt): array
    {
        if ($currentNodes === []) {
            return [];
        }

        $last = $currentNodes[count($currentNodes) - 1];
        if (! $last instanceof DOMElement) {
            return [];
        }

        if (! $this->isOrphanHeading($last)) {
            return [];
        }

        array_pop($currentNodes);
        $usedPt = max(0.0, $usedPt - $this->safeEstimate($this->estimateNodeHeightPt($last)));

        return [$last];
    }

    private function minUsefulLeftoverPt(): float
    {
        // One-and-a-half lines is enough to attempt a split; three lines left
        // large bottoms empty when the next block was only slightly taller.
        return self::CONTENT_FONT_PT * $this->defaultLineHeightRatio() * 1.5;
    }

    /**
     * Word/TinyMCE subsection labels ("b. Uniform and Professional Conduct")
     * are often a short <p>/<strong>, not h1–h4. Emphasis-only titles such as
     * "Contract Highlights" are treated the same way.
     */
    private function isOrphanHeading(DOMElement $node): bool
    {
        if ($this->hasBlockChildren($node)) {
            return false;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            return true;
        }

        $class = strtolower($node->getAttribute('class'));
        if (str_contains($class, 'clause-title')) {
            return true;
        }

        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
        if ($text === '' || mb_strlen($text) > 90) {
            return false;
        }

        if (preg_match('/^(?:[a-z]|[ivxlcdm]+|\d+)[.)]\s+\S/iu', $text)) {
            return true;
        }

        if ($this->isEmphasisOnlyLine($node)) {
            return true;
        }

        // normalizeHtml often unwraps <strong>/<u>, leaving plain title lines.
        return $this->isPlainTitleLine($text);
    }

    /**
     * Short Title-Case lines such as "Contract Highlights" or "Name & Signature".
     */
    private function isPlainTitleLine(string $text): bool
    {
        if (mb_strlen($text) > 60 || preg_match('/[.!?]$/u', $text)) {
            return false;
        }

        if (! preg_match('/^[A-Z0-9]/u', $text)) {
            return false;
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        if ($words === [] || count($words) > 8) {
            return false;
        }

        $titleWords = 0;
        foreach ($words as $word) {
            $bare = preg_replace('/[^A-Za-z0-9]/u', '', $word) ?? '';
            if ($bare === '') {
                continue;
            }
            // Allow small connectors inside titles ("and", "of", "&").
            if (preg_match('/^(?:and|or|of|the|for|to|a|an|&)$/iu', $bare)) {
                $titleWords++;

                continue;
            }
            if (preg_match('/^[A-Z0-9]/u', $bare)) {
                $titleWords++;
            }
        }

        $significant = count(array_filter(
            $words,
            static fn (string $word): bool => preg_replace('/[^A-Za-z0-9]/u', '', $word) !== ''
        ));

        return $significant > 0 && $titleWords >= $significant;
    }

    /**
     * TinyMCE section titles are often a whole line wrapped in strong/b/u/em/span
     * with no numbered prefix ("Contract Highlights"). Ordinary sentences that
     * only bold a word in the middle still have bare text nodes and must not match.
     */
    private function isEmphasisOnlyLine(DOMElement $node): bool
    {
        // Do not treat generic <span style="font-size:..."> wrappers as emphasis —
        // that falsely blocked pulls of ordinary lead-in lines onto prior pages.
        $emphasisTags = ['strong', 'b', 'u', 'em'];
        $ignorableTags = ['br', 'wbr', 'hr'];
        $hasEmphasisText = false;

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                if (trim($child->textContent ?? '') !== '') {
                    return false;
                }

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (in_array($tag, $ignorableTags, true)) {
                continue;
            }

            if (! in_array($tag, $emphasisTags, true)) {
                return false;
            }

            if (trim($child->textContent ?? '') !== '') {
                $hasEmphasisText = true;
            }
        }

        return $hasEmphasisText;
    }

    /**
     * When the page already ends on a section title, do not carve a fragment of
     * the next table/list into the leftover gap — move the title with the block.
     *
     * @param  list<DOMNode>  $currentNodes
     */
    private function shouldDeferOversizedToKeepHeading(array $currentNodes, DOMElement $child): bool
    {
        if ($currentNodes === []) {
            return false;
        }

        $last = $currentNodes[count($currentNodes) - 1];
        if (! $last instanceof DOMElement || ! $this->isOrphanHeading($last)) {
            return false;
        }

        $tag = strtolower($child->tagName);

        return in_array($tag, ['table', 'ul', 'ol'], true);
    }

    /**
     * @param  list<string>  $pages
     * @param  list<DOMNode>  $currentNodes
     */
    private function continueOversizedOnCurrentPage(
        DOMDocument $dom,
        DOMElement $child,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): bool {
        $remaining = $this->budgetPt - $usedPt;
        $sliced = $this->sliceElementForBudget($dom, $child, $remaining);
        if ($sliced !== null) {
            $currentNodes[] = $sliced[0];
            $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($sliced[0]));
            $this->appendNode($dom, $sliced[1], $pages, $currentNodes, $usedPt);

            return true;
        }

        $tag = strtolower($child->tagName);
        if (
            in_array($tag, ['p', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)
            && ! $this->hasBlockChildren($child)
        ) {
            $parts = $this->splitTextBlockForBudget($dom, $child, $remaining);
            if (count($parts) > 1) {
                $currentNodes[] = $parts[0];
                $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($parts[0]));
                for ($i = 1; $i < count($parts); $i++) {
                    $this->appendNode($dom, $parts[$i], $pages, $currentNodes, $usedPt);
                }

                return true;
            }

            $text = trim(preg_replace('/\s+/u', ' ', $child->textContent ?? '') ?? '');
            $textPt = $this->safeEstimate(
                $this->estimateTextHeightPt(
                    $text,
                    $this->styleFontSizePt($child),
                    $this->styleLineHeightRatio($child)
                ) + $this->textBlockPaddingPt()
            );
            if ($text !== '' && $textPt <= $remaining) {
                $currentNodes[] = $child;
                $usedPt += $textPt;

                return true;
            }
        }

        $flow = $this->flattenElement($dom, $child);
        if (count($flow) > 1) {
            foreach ($flow as $part) {
                $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return true;
        }

        return false;
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

        $flow = $this->flattenElement($dom, $element);
        if (count($flow) > 1) {
            foreach ($flow as $part) {
                $this->appendNode($dom, $part, $pages, $currentNodes, $usedPt);
            }

            return;
        }

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
        $tableEstimate = $this->safeEstimate($this->estimateTableHeightPt($table));

        // Keep section titles with their table: if the full table cannot fit in
        // the leftover gap under a title, start a new sheet with the title.
        if (
            $currentNodes !== []
            && $this->shouldDeferOversizedToKeepHeading($currentNodes, $table)
            && $tableEstimate > $remaining
        ) {
            $carry = $this->popTrailingHeading($currentNodes, $usedPt);
            if ($currentNodes !== []) {
                $pages[] = $this->joinHtml($dom, $currentNodes);
            }
            $currentNodes = $carry;
            $usedPt = 0.0;
            foreach ($currentNodes as $carried) {
                $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($carried));
            }
            $remaining = max(0.0, $this->budgetPt - $usedPt);
        } elseif ($remaining < self::CONTENT_FONT_PT * $this->defaultLineHeightRatio() && $currentNodes !== []) {
            $carry = $this->popTrailingHeading($currentNodes, $usedPt);
            if ($currentNodes !== []) {
                $pages[] = $this->joinHtml($dom, $currentNodes);
            }
            $currentNodes = $carry;
            $usedPt = 0.0;
            foreach ($currentNodes as $carried) {
                $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($carried));
            }
            $remaining = max(0.0, $this->budgetPt - $usedPt);
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

        if ($tag === 'table' && $estimate > $this->budgetPt) {
            [, $bodyRows] = $this->tableRows($node);
            if (count($bodyRows) > 1) {
                $this->appendTableParts($dom, $node, $pages, $currentNodes, $usedPt);

                return;
            }
        }

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
            $carry = $this->popTrailingHeading($currentNodes, $usedPt);
            if ($currentNodes !== []) {
                $pages[] = $this->joinHtml($dom, $currentNodes);
            }
            $currentNodes = $carry;
            $usedPt = 0.0;
            foreach ($currentNodes as $carried) {
                $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($carried));
            }
        }

        // Keep a carried section title with this oversized block on the next sheet.
        if ($currentNodes !== []) {
            $currentNodes[] = $node;
            $pages[] = $this->joinHtml($dom, $currentNodes);
            $currentNodes = [];
            $usedPt = 0.0;

            return;
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
        if ($remainingPt < self::CONTENT_FONT_PT * $this->defaultLineHeightRatio() || ! $child instanceof DOMElement) {
            return false;
        }

        if ($this->shouldDeferOversizedToKeepHeading($currentNodes, $child)) {
            return false;
        }

        $tag = strtolower($child->tagName);

        if ($this->hasBlockChildren($child)) {
            $flow = $this->flattenElement($dom, $child);
            if (count($flow) > 1) {
                return $this->tryPackFlowIntoRemaining($dom, $flow, $remainingPt, $pages, $currentNodes, $usedPt);
            }
        }

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

        $flow = $this->flattenElement($dom, $child);
        if (count($flow) > 1) {
            return $this->tryPackFlowIntoRemaining($dom, $flow, $remainingPt, $pages, $currentNodes, $usedPt);
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
        $items = $this->listFlowNodes($dom, $list);
        if (count($items) <= 1) {
            return $this->tryPackSingleListItemIntoRemaining($dom, $list, $remainingPt, $pages, $currentNodes, $usedPt);
        }

        $packed = [];
        $packedHeight = 0.0;
        $splitAt = 0;

        foreach ($items as $index => $item) {
            $itemEstimate = $this->safeEstimate($this->estimateNodeHeightPt($item));
            if ($packedHeight + $itemEstimate > $remainingPt && $packed !== []) {
                $leftover = $remainingPt - $packedHeight;
                if ($item instanceof DOMElement && $leftover >= self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
                    $sliced = $this->sliceElementForBudget($dom, $item, $leftover);
                    if ($sliced !== null) {
                        foreach ($packed as $node) {
                            $currentNodes[] = $node;
                        }
                        $currentNodes[] = $sliced[0];
                        $usedPt += $packedHeight + $this->safeEstimate($this->estimateNodeHeightPt($sliced[0]));
                        $this->appendNode($dom, $sliced[1], $pages, $currentNodes, $usedPt);
                        for ($i = $index + 1; $i < count($items); $i++) {
                            $this->appendNode($dom, $items[$i], $pages, $currentNodes, $usedPt);
                        }

                        return true;
                    }
                }

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
            $this->appendNode($dom, $items[$i], $pages, $currentNodes, $usedPt);
        }

        return true;
    }

    /**
     * Pack independently flowable nodes into leftover page space.
     *
     * @param  list<DOMNode>  $flow
     * @param  list<DOMNode>  $currentNodes
     */
    private function tryPackFlowIntoRemaining(
        DOMDocument $dom,
        array $flow,
        float $remainingPt,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): bool {
        $packed = [];
        $packedHeight = 0.0;
        $splitAt = 0;

        foreach ($flow as $index => $node) {
            if ($this->isForcedPageBreak($node) || $this->isVisuallyEmpty($node)) {
                $splitAt = $index + 1;

                continue;
            }

            $estimate = $this->safeEstimate($this->estimateNodeHeightPt($node));
            if ($packedHeight + $estimate <= $remainingPt) {
                $packed[] = $node;
                $packedHeight += $estimate;
                $splitAt = $index + 1;

                continue;
            }

            $leftover = $remainingPt - $packedHeight;
            if ($node instanceof DOMElement && $leftover >= self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
                $sliced = $this->sliceElementForBudget($dom, $node, $leftover);
                if ($sliced !== null) {
                    foreach ($packed as $item) {
                        $currentNodes[] = $item;
                    }
                    $currentNodes[] = $sliced[0];
                    $usedPt += $packedHeight + $this->safeEstimate($this->estimateNodeHeightPt($sliced[0]));
                    $this->appendNode($dom, $sliced[1], $pages, $currentNodes, $usedPt);
                    foreach (array_slice($flow, $index + 1) as $rest) {
                        $this->appendNode($dom, $rest, $pages, $currentNodes, $usedPt);
                    }

                    return true;
                }
            }

            if ($packed === []) {
                return false;
            }

            break;
        }

        if ($packed === [] || $splitAt >= count($flow)) {
            return false;
        }

        foreach ($packed as $item) {
            $currentNodes[] = $item;
        }
        $usedPt += $packedHeight;

        foreach (array_slice($flow, $splitAt) as $rest) {
            $this->appendNode($dom, $rest, $pages, $currentNodes, $usedPt);
        }

        return true;
    }

    /**
     * Split one list item against leftover space so a heading does not sit
     * alone on a page while its body starts on the next page.
     *
     * @param  list<DOMNode>  $currentNodes
     */
    private function tryPackSingleListItemIntoRemaining(
        DOMDocument $dom,
        DOMElement $list,
        float $remainingPt,
        array &$pages,
        array &$currentNodes,
        float &$usedPt
    ): bool {
        $li = $this->firstListItem($list);
        if (! $li instanceof DOMElement) {
            return false;
        }

        $inner = $this->expandNodes($dom, $li);
        if ($inner === []) {
            $parts = $this->splitTextBlockForBudget($dom, $this->listItemAsParagraph($dom, $li), $remainingPt);
            if (count($parts) <= 1) {
                return false;
            }

            $currentNodes[] = $this->wrapNodesInList($dom, $list, [$parts[0]]);
            $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($parts[0]));
            foreach (array_slice($parts, 1) as $part) {
                $this->appendNode($dom, $this->wrapNodesInList($dom, $list, [$part]), $pages, $currentNodes, $usedPt);
            }

            return true;
        }

        $packed = [];
        $packedHeight = 0.0;
        $splitAt = 0;

        foreach ($inner as $index => $node) {
            $itemEstimate = $this->safeEstimate($this->estimateNodeHeightPt($node));
            if ($packedHeight + $itemEstimate <= $remainingPt) {
                $packed[] = $node;
                $packedHeight += $itemEstimate;
                $splitAt = $index + 1;

                continue;
            }

            if ($packed === [] && $node instanceof DOMElement) {
                $sliced = $this->sliceElementForBudget($dom, $node, $remainingPt);
                if ($sliced === null) {
                    return false;
                }

                $currentNodes[] = $this->wrapNodesInList($dom, $list, [$sliced[0]]);
                $usedPt += $this->safeEstimate($this->estimateNodeHeightPt($sliced[0]));
                $this->appendNode($dom, $sliced[1], $pages, $currentNodes, $usedPt);
                foreach (array_slice($inner, 1) as $rest) {
                    $this->appendNode($dom, $rest, $pages, $currentNodes, $usedPt);
                }

                return true;
            }

            $leftover = $remainingPt - $packedHeight;
            if ($node instanceof DOMElement && $leftover >= self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
                $sliced = $this->sliceElementForBudget($dom, $node, $leftover);
                if ($sliced !== null) {
                    foreach ($packed as $item) {
                        $currentNodes[] = $item;
                    }
                    $currentNodes[] = $sliced[0];
                    $usedPt += $packedHeight + $this->safeEstimate($this->estimateNodeHeightPt($sliced[0]));
                    $this->appendNode($dom, $sliced[1], $pages, $currentNodes, $usedPt);
                    foreach (array_slice($inner, $index + 1) as $rest) {
                        $this->appendNode($dom, $rest, $pages, $currentNodes, $usedPt);
                    }

                    return true;
                }
            }

            break;
        }

        if ($packed === [] || $splitAt >= count($inner)) {
            return false;
        }

        foreach ($packed as $item) {
            $currentNodes[] = $item;
        }
        $usedPt += $packedHeight;

        foreach (array_slice($inner, $splitAt) as $rest) {
            $this->appendNode($dom, $rest, $pages, $currentNodes, $usedPt);
        }

        return true;
    }

    /**
     * @return array{0: DOMElement, 1: DOMElement}|null
     */
    private function sliceElementForBudget(DOMDocument $dom, DOMElement $element, float $budgetPt): ?array
    {
        $tag = strtolower($element->tagName);

        if (in_array($tag, ['p', 'blockquote'], true)) {
            $parts = $this->splitTextBlockForBudget($dom, $element, $budgetPt);
            if (count($parts) < 2) {
                return null;
            }

            $rest = $dom->createElement($tag);
            $this->copyElementAttributes($element, $rest);
            for ($i = 1; $i < count($parts); $i++) {
                foreach ($parts[$i]->childNodes as $child) {
                    $rest->appendChild($child->cloneNode(true));
                }
            }

            return [$parts[0], $rest];
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            return $this->packFlowSplit($dom, $this->listFlowNodes($dom, $element), $budgetPt);
        }

        if ($tag === 'table') {
            $parts = $this->splitTable($dom, $element, $budgetPt);
            if (count($parts) < 2) {
                return null;
            }

            if (count($parts) === 2) {
                return [$parts[0], $parts[1]];
            }

            $rest = $dom->createElement('div');
            for ($i = 1; $i < count($parts); $i++) {
                $rest->appendChild($parts[$i]->cloneNode(true));
            }

            return [$parts[0], $rest];
        }

        if (in_array($tag, ['p', 'blockquote', 'li'], true) && ! $this->hasBlockChildren($element)) {
            $parts = $this->splitTextBlockForBudget($dom, $element, $budgetPt);
            if (count($parts) < 2) {
                return null;
            }

            $restTag = $tag === 'li' ? 'p' : $tag;
            $rest = $dom->createElement($restTag);
            $this->copyElementAttributes($element, $rest);
            for ($i = 1; $i < count($parts); $i++) {
                foreach ($parts[$i]->childNodes as $child) {
                    $rest->appendChild($child->cloneNode(true));
                }
            }

            return [$parts[0], $rest];
        }

        if (in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'section', 'article', 'li'], true) && $this->hasBlockChildren($element)) {
            return $this->packFlowSplit($dom, $this->flattenElement($dom, $element), $budgetPt);
        }

        return null;
    }

    /**
     * @param  list<DOMNode>  $flow
     * @return array{0: DOMElement, 1: DOMElement}|null
     */
    private function packFlowSplit(DOMDocument $dom, array $flow, float $budgetPt): ?array
    {
        if (count($flow) < 2) {
            return null;
        }

        $packed = [];
        $packedHeight = 0.0;
        $splitAt = 0;

        foreach ($flow as $index => $node) {
            $estimate = $this->safeEstimate($this->estimateNodeHeightPt($node));
            if ($packedHeight + $estimate <= $budgetPt) {
                $packed[] = $node;
                $packedHeight += $estimate;
                $splitAt = $index + 1;

                continue;
            }

            $leftover = $budgetPt - $packedHeight;
            if ($node instanceof DOMElement && $leftover >= self::CONTENT_FONT_PT * $this->defaultLineHeightRatio()) {
                $sliced = $this->sliceElementForBudget($dom, $node, $leftover);
                if ($sliced !== null) {
                    $packed[] = $sliced[0];
                    $first = $dom->createElement('div');
                    foreach ($packed as $packedNode) {
                        $first->appendChild($packedNode->cloneNode(true));
                    }
                    $rest = $dom->createElement('div');
                    $rest->appendChild($sliced[1]->cloneNode(true));
                    foreach (array_slice($flow, $index + 1) as $restNode) {
                        $rest->appendChild($restNode->cloneNode(true));
                    }

                    return [$first, $rest];
                }
            }

            break;
        }

        if ($packed === [] || $splitAt >= count($flow)) {
            return null;
        }

        $first = $dom->createElement('div');
        foreach ($packed as $node) {
            $first->appendChild($node->cloneNode(true));
        }
        $rest = $dom->createElement('div');
        foreach (array_slice($flow, $splitAt) as $node) {
            $rest->appendChild($node->cloneNode(true));
        }

        return [$first, $rest];
    }

    private function listItemAsParagraph(DOMDocument $dom, DOMElement $li): DOMElement
    {
        $p = $dom->createElement('p');
        $p->appendChild($dom->createTextNode(trim(preg_replace('/\s+/u', ' ', $li->textContent ?? '') ?? '')));

        return $p;
    }

    /**
     * @return list<string>
     */
    private function splitForcedBreakSegments(string $bodyHtml): array
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-root">' . $bodyHtml . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('agreement-root');
        if (! $root instanceof DOMElement) {
            return [$bodyHtml];
        }

        $segments = [];
        $current = [];

        foreach ($this->expandNodes($dom, $root) as $child) {
            if ($this->isForcedPageBreak($child)) {
                if ($current !== []) {
                    $segments[] = $this->joinHtml($dom, $current);
                    $current = [];
                }

                continue;
            }

            $current[] = $child;
        }

        if ($current !== []) {
            $segments[] = $this->joinHtml($dom, $current);
        }

        return $segments !== [] ? $segments : [$bodyHtml];
    }

    /**
     * Strip TinyMCE sheet gutters. Keep Insert → Page break markers so authors
     * can start the next PDF sheet. Word paste CSS breaks are ignored.
     */
    private function normalizeBodyHtml(string $html): string
    {
        $html = preg_replace('/<div[^>]*(?:data-word-page-gap|word-page-gap)[^>]*>.*?<\/div>/is', '', $html) ?? $html;
        $html = preg_replace('/<div[^>]*class="[^"]*word-letterhead-[^"]*"[^>]*>.*?<\/div>/is', '', $html) ?? $html;
        $html = preg_replace('/<!--\s*pagebreak\s*-->/i', '<p data-agreement-page-break="1" class="agreement-page-break">&nbsp;</p>', $html) ?? $html;
        $html = preg_replace('/page-break-(?:before|after|inside)\s*:\s*[^;\'"]+;?/i', '', $html) ?? $html;
        $html = preg_replace('/(?<!-)break-(?:before|after|inside)\s*:\s*[^;\'"]+;?/i', '', $html) ?? $html;
        $html = preg_replace('/mso-(?:break-type|page-break(?:-before|-after)?)\s*:\s*[^;\'"]+;?/i', '', $html) ?? $html;

        return $html;
    }

    private function isForcedPageBreak(DOMNode $node): bool
    {
        if (! $node instanceof DOMElement) {
            return false;
        }

        if ($node->getAttribute('data-agreement-page-break') === '1') {
            return true;
        }

        $class = strtolower($node->getAttribute('class'));
        if (str_contains($class, 'mce-pagebreak')) {
            return true;
        }

        $style = strtolower($node->getAttribute('style'));
        if (str_contains($style, 'page-break-before: always') || str_contains($style, 'break-before: page')) {
            return true;
        }

        return false;
    }

    private function isVisuallyEmpty(DOMNode $node): bool
    {
        if ($this->isForcedPageBreak($node)) {
            return false;
        }

        if ($node instanceof DOMElement) {
            $tag = strtolower($node->tagName);
            $class = strtolower($node->getAttribute('class'));
            if (str_contains($class, 'mce-pagebreak') || str_contains($class, 'word-page-gap')) {
                return true;
            }
            if (in_array($tag, ['p', 'div', 'blockquote', 'center', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li'], true)) {
                return false;
            }
            if ($tag === 'img' && trim($node->getAttribute('src')) !== '') {
                return false;
            }
            if ($tag === 'table' && $node->getElementsByTagName('tr')->length > 0) {
                return false;
            }

            $html = $node->ownerDocument?->saveHTML($node) ?? '';
        } else {
            $html = (string) $node->textContent;
        }

        return $this->isBlankHtml($html);
    }

    private function isBlankHtml(string $html): bool
    {
        if (preg_match('/<img\b/i', $html) === 1) {
            return false;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\x{00A0}/u', ' ', $text) ?? $text;

        return trim($text) === '';
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

            if ($this->isForcedPageBreak($child)) {
                $items[] = $child;

                continue;
            }

            if ($this->isSpacerOnly($child) || $this->isVisuallyEmpty($child)) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if ($this->isInlineTag($tag)) {
                continue;
            }

            if (in_array($tag, ['p', 'blockquote'], true) && ! $this->hasBlockChildren($child)) {
                foreach ($this->splitTextBlock($dom, $child) as $part) {
                    $items[] = $part;
                }

                continue;
            }

            foreach ($this->flattenElement($dom, $child) as $part) {
                $items[] = $part;
            }
        }

        return $items;
    }

    /**
     * Turn a stored block into independently packable nodes.
     * Word/TinyMCE often wraps a whole numbered section in one heading, list
     * item, or ol whose children are h2/p/table without wrapping li tags.
     *
     * @return list<DOMNode>
     */
    private function flattenElement(DOMDocument $dom, DOMElement $element): array
    {
        if ($this->isSpacerOnly($element) || ($this->isVisuallyEmpty($element) && ! $this->isForcedPageBreak($element))) {
            return [];
        }

        $tag = strtolower($element->tagName);

        if (in_array($tag, ['ul', 'ol'], true)) {
            return $this->listFlowNodes($dom, $element);
        }

        if (in_array($tag, ['div', 'section', 'article', 'main', 'figure', 'center', 'li'], true)) {
            if (! $this->hasBlockChildren($element)) {
                return [$element];
            }

            $expanded = $this->expandNodes($dom, $element);

            return $expanded !== [] ? $expanded : [$element];
        }

        if (in_array($tag, ['h1', 'h2', 'h3', 'h4'], true) && $this->hasBlockChildren($element)) {
            return $this->unpackCompositeHeading($dom, $element);
        }

        if (in_array($tag, ['p', 'blockquote'], true) && $this->hasBlockChildren($element)) {
            $expanded = $this->expandNodes($dom, $element);

            return $expanded !== [] ? $expanded : [$element];
        }

        return [$element];
    }

    /**
     * @return list<DOMNode>
     */
    private function unpackCompositeHeading(DOMDocument $dom, DOMElement $heading): array
    {
        $title = $dom->createElement($heading->tagName);
        $this->copyElementAttributes($heading, $title);

        foreach (iterator_to_array($heading->childNodes) as $child) {
            if ($child instanceof DOMElement && $this->isBlockTag($child->tagName)) {
                continue;
            }

            $title->appendChild($child->cloneNode(true));
        }

        $nodes = [];
        if (! $this->isBlankHtml($dom->saveHTML($title) ?: '')) {
            $nodes[] = $title;
        }

        foreach ($this->directElementChildren($heading) as $child) {
            if (! $this->isBlockTag($child->tagName)) {
                continue;
            }

            foreach ($this->flattenElement($dom, $child) as $part) {
                $nodes[] = $part;
            }
        }

        return $nodes !== [] ? $nodes : [$heading];
    }

    private function hasBlockChildren(DOMElement $element): bool
    {
        foreach ($element->childNodes as $child) {
            if ($child instanceof DOMElement && $this->isBlockTag($child->tagName)) {
                return true;
            }
        }

        return false;
    }

    private function isBlockTag(string $tag): bool
    {
        return in_array(strtolower($tag), [
            'p', 'div', 'table', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'blockquote', 'section', 'article', 'main', 'header', 'footer', 'figure',
            'center', 'pre', 'hr',
        ], true);
    }

    private function isInlineTag(string $tag): bool
    {
        return in_array(strtolower($tag), [
            'span', 'strong', 'b', 'em', 'i', 'u', 'a', 'br', 'sub', 'sup',
            'font', 'small', 'label', 'strike', 's', 'code',
        ], true);
    }

    private function isSpacerOnly(DOMNode $node): bool
    {
        if (! $node instanceof DOMElement) {
            return false;
        }

        $style = strtolower($node->getAttribute('style'));
        $hasFixedHeight = (bool) preg_match('/(?:^|;)\s*(?:min-)?height\s*:\s*\d/', $style);

        return $hasFixedHeight && $this->isVisuallyEmpty($node);
    }

    /**
     * Flatten list items that wrap a whole section (title + body + nested blocks)
     * into independently packable nodes. Simple one-line items stay as lists.
     *
     * @return list<DOMNode>
     */
    private function listFlowNodes(DOMDocument $dom, DOMElement $list): array
    {
        $items = [];
        $index = 0;
        $tag = strtolower($list->tagName);
        $baseStart = $tag === 'ol' ? max(1, (int) ($list->getAttribute('start') ?: 1)) : 1;

        foreach ($list->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            if ($this->isSpacerOnly($child) || $this->isVisuallyEmpty($child)) {
                continue;
            }

            if (strtolower($child->tagName) !== 'li') {
                foreach ($this->flattenElement($dom, $child) as $part) {
                    $items[] = $part;
                }

                continue;
            }

            $inner = $this->expandNodes($dom, $child);
            if ($this->listItemIsComposite($inner)) {
                foreach ($inner as $nested) {
                    $items[] = $nested;
                }
            } else {
                $items[] = $this->wrapSingleListItem($dom, $list, $child, $baseStart + $index);
            }

            $index++;
        }

        return $items !== [] ? $items : [$list];
    }

    /**
     * @param  list<DOMNode>  $inner
     */
    private function listItemIsComposite(array $inner): bool
    {
        if (count($inner) > 1) {
            return true;
        }

        $only = $inner[0] ?? null;
        if (! $only instanceof DOMElement) {
            return false;
        }

        $tag = strtolower($only->tagName);
        if (in_array($tag, ['table', 'ul', 'ol', 'div', 'section', 'article'], true)) {
            return true;
        }

        return in_array($tag, ['h1', 'h2', 'h3', 'h4', 'p', 'blockquote'], true)
            && $this->hasBlockChildren($only);
    }

    private function wrapSingleListItem(DOMDocument $dom, DOMElement $list, DOMElement $li, int $start): DOMElement
    {
        $tag = strtolower($list->tagName);
        $single = $dom->createElement($tag);
        $this->copyListAttributes($list, $single);

        if ($tag === 'ol') {
            $this->setOrderedListStart($single, $start);
        }

        $single->appendChild($li->cloneNode(true));

        return $single;
    }

    /**
     * @param  list<DOMNode>  $nodes
     */
    private function wrapNodesInList(DOMDocument $dom, DOMElement $sourceList, array $nodes): DOMElement
    {
        $tag = in_array(strtolower($sourceList->tagName), ['ul', 'ol'], true)
            ? strtolower($sourceList->tagName)
            : 'ul';
        $list = $dom->createElement($tag);
        $this->copyListAttributes($sourceList, $list);
        if ($tag === 'ol' && $sourceList->hasAttribute('start')) {
            $list->setAttribute('start', $sourceList->getAttribute('start'));
        }

        $li = $dom->createElement('li');
        foreach ($nodes as $node) {
            $li->appendChild($node->cloneNode(true));
        }
        $list->appendChild($li);

        return $list;
    }

    private function firstListItem(DOMElement $list): ?DOMElement
    {
        foreach ($list->childNodes as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'li') {
                return $child;
            }
        }

        return null;
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
        if ($block->getElementsByTagName('img')->length > 0) {
            return [$block];
        }

        $text = trim(preg_replace('/\s+/u', ' ', $block->textContent ?? '') ?? '');
        if ($text === '') {
            return [$block];
        }

        $fontPt = $this->styleFontSizePt($block);
        $lineHeightRatio = $this->styleLineHeightRatio($block);
        $estimate = $this->safeEstimate($this->estimateTextHeightPt($text, $fontPt, $lineHeightRatio) + $this->textBlockPaddingPt());
        if ($estimate <= $this->budgetPt) {
            return [$block];
        }

        $parts = [];
        $tag = strtolower($block->tagName);
        $chunks = $this->chunkText($text, (int) ($this->charsPerLineForFont($fontPt) * 3.5));

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
        if ($block->getElementsByTagName('img')->length > 0) {
            return [$block];
        }

        $text = trim(preg_replace('/\s+/u', ' ', $block->textContent ?? '') ?? '');
        if ($text === '') {
            return [$block];
        }

        $fontPt = $this->styleFontSizePt($block);
        $lineHeightRatio = $this->styleLineHeightRatio($block);
        $fullEstimate = $this->safeEstimate($this->estimateTextHeightPt($text, $fontPt, $lineHeightRatio) + $this->textBlockPaddingPt());
        if ($fullEstimate <= $budgetPt) {
            return [$block];
        }

        $words = preg_split('/\s+/u', $text) ?: [];
        $firstWords = [];
        $splitIndex = count($words);

        foreach ($words as $index => $word) {
            $candidateWords = array_merge($firstWords, [$word]);
            $candidate = implode(' ', $candidateWords);
            $estimate = $this->safeEstimate($this->estimateTextHeightPt($candidate, $fontPt, $lineHeightRatio) + $this->textBlockPaddingPt());

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
        if ($block->getElementsByTagName('img')->length > 0) {
            return [$block];
        }

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
        if ($li->getElementsByTagName('img')->length > 0) {
            return [$li];
        }

        $text = trim(preg_replace('/\s+/u', ' ', $li->textContent ?? '') ?? '');
        if ($text === '') {
            return [$li];
        }

        $fontPt = $this->styleFontSizePt($li);
        $lineHeightRatio = $this->styleLineHeightRatio($li);
        $estimate = $this->safeEstimate($this->estimateTextHeightPt($text, $fontPt, $lineHeightRatio) + $this->listItemPaddingPt());
        if ($estimate <= $this->budgetPt) {
            return [$li];
        }

        $parts = [];
        foreach ($this->chunkText($text, (int) ($this->charsPerLineForFont($fontPt) * 3.5)) as $chunk) {
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

        $headerPt = $this->estimateRowsHeightPt($headerRows, $table);
        $chunks = [];
        $batch = [];
        $usedPt = 0.0;

        foreach ($bodyRows as $row) {
            $rowPt = $this->estimateRowHeightPt($row, $table);
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

        foreach ($this->directElementChildren($table) as $section) {
            $tag = strtolower($section->tagName);

            if ($tag === 'thead') {
                $headerRows = array_merge($headerRows, $this->directRows($section));
            } elseif (in_array($tag, ['tbody', 'tfoot'], true)) {
                $bodyRows = array_merge($bodyRows, $this->directRows($section));
            } elseif ($tag === 'tr') {
                if ($headerRows === [] && $this->rowLooksLikeHeader($section)) {
                    $headerRows[] = $section;
                } else {
                    $bodyRows[] = $section;
                }
            }
        }

        return [$headerRows, $bodyRows];
    }

    /**
     * @return list<DOMElement>
     */
    private function directElementChildren(DOMElement $parent): array
    {
        $elements = [];
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $elements[] = $child;
            }
        }

        return $elements;
    }

    /**
     * @return list<DOMElement>
     */
    private function directRows(DOMElement $parent): array
    {
        $rows = [];
        foreach ($this->directElementChildren($parent) as $child) {
            if (strtolower($child->tagName) === 'tr') {
                $rows[] = $child;
            }
        }

        return $rows;
    }

    /**
     * @return list<DOMElement>
     */
    private function directCells(DOMElement $row): array
    {
        $cells = [];
        foreach ($this->directElementChildren($row) as $child) {
            if (in_array(strtolower($child->tagName), ['td', 'th'], true)) {
                $cells[] = $child;
            }
        }

        return $cells;
    }

    private function rowLooksLikeHeader(DOMElement $row): bool
    {
        foreach ($this->directCells($row) as $cell) {
            if (strtolower($cell->tagName) === 'th') {
                return true;
            }
        }

        return false;
    }

    private function tableColumnCount(DOMElement $table): int
    {
        [$headerRows, $bodyRows] = $this->tableRows($table);
        $row = $headerRows[0] ?? $bodyRows[0] ?? null;
        if (! $row instanceof DOMElement) {
            return 1;
        }

        $count = 0;
        foreach ($this->directCells($row) as $cell) {
            $count += max(1, (int) ($cell->getAttribute('colspan') ?: 1));
        }

        return max(1, $count);
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
            if ($this->isForcedPageBreak($node) || $this->isVisuallyEmpty($node)) {
                continue;
            }

            $total += $this->safeEstimate($this->estimateNodeHeightPt($node));

            if ($total > $this->budgetPt) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pack to the full content zone (page height minus user margins).
     * Extra footer slack is never added: if letterhead artwork has a footer,
     * the category's bottom margin is the only reserved gap.
     */
    private function packBudgetPt(): float
    {
        return $this->budgetPt;
    }

    private function normalizePdfEngine(?string $pdfEngine): string
    {
        $engine = strtolower(trim((string) $pdfEngine));
        if (in_array($engine, ['mpdf', 'chrome', 'html'], true)) {
            return $engine;
        }

        // Prefer mPDF calibration: live forced-mPDF path and Chrome-unavailable fallback.
        return 'mpdf';
    }

    private function usesMpdfMetrics(): bool
    {
        return $this->pdfEngine === 'mpdf';
    }

    /**
     * Scale raw PHP estimates toward the active PDF engine's paint height.
     * mPDF paints tighter than Dompdf/Chrome for the same HTML.
     */
    private function estimateScaleFactor(): float
    {
        return $this->usesMpdfMetrics() ? self::ESTIMATE_TO_MPDF : self::ESTIMATE_TO_CHROME;
    }

    private function defaultLineHeightRatio(): float
    {
        return $this->usesMpdfMetrics()
            ? self::LINE_HEIGHT_RATIO_MPDF
            : self::LINE_HEIGHT_RATIO_CHROME;
    }

    private function paragraphMarginEm(): float
    {
        return $this->usesMpdfMetrics()
            ? self::PARAGRAPH_MARGIN_EM_MPDF
            : self::PARAGRAPH_MARGIN_EM_CHROME;
    }

    /** Extra padding on text/list blocks beyond line metrics. */
    private function textBlockPaddingPt(): float
    {
        return $this->usesMpdfMetrics() ? 2.0 : 4.0;
    }

    private function listItemPaddingPt(): float
    {
        return $this->usesMpdfMetrics() ? 3.0 : 6.0;
    }

    private function safeEstimate(float $estimate): float
    {
        return max(0.0, $estimate * $this->estimateScaleFactor());
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
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => $this->hasBlockChildren($node)
                ? $this->estimateBlockHeightPt($node)
                : 8 + $this->estimateTextHeightPt($node->textContent ?? '', $this->headingFontPt($tag)),
            'hr' => $this->isForcedPageBreak($node) ? 0.0 : 10,
            'img' => $this->estimateImageHeightPt($node),
            'li' => $this->estimateTextHeightPt(
                $node->textContent ?? '',
                $this->styleFontSizePt($node),
                $this->styleLineHeightRatio($node)
            ) + $this->textBlockPaddingPt(),
            default => $this->estimateBlockHeightPt($node),
        };
    }

    private function headingFontPt(string $tag): float
    {
        return match (strtolower($tag)) {
            'h1' => 16.0,
            'h2' => self::HEADING_FONT_PT,
            'h3' => 12.0,
            default => self::CONTENT_FONT_PT,
        };
    }

    private function estimateTableHeightPt(DOMElement $table): float
    {
        [$headerRows, $bodyRows] = $this->tableRows($table);

        return $this->estimateRowsHeightPt($headerRows, $table) + $this->estimateRowsHeightPt($bodyRows, $table);
    }

    /**
     * @param  list<DOMElement>  $rows
     */
    private function estimateRowsHeightPt(array $rows, DOMElement $table): float
    {
        if ($rows === []) {
            return 0.0;
        }

        return array_sum(array_map(
            fn (DOMElement $row): float => $this->estimateRowHeightPt($row, $table),
            $rows
        ));
    }

    private function estimateRowHeightPt(DOMElement $row, DOMElement $table): float
    {
        $columnCount = $this->tableColumnCount($table);
        $tableFontPt = $this->styleFontSizePt($table);
        $tableLineHeightRatio = $this->styleLineHeightRatio($table);
        $cssHeight = $this->cssLengthToPt($row->getAttribute('style'), 'height')
            ?? $this->cssLengthToPt($row->getAttribute('style'), 'min-height');
        $contentDerived = $tableFontPt * $tableLineHeightRatio;

        foreach ($this->directCells($row) as $cell) {
            $nestedHeight = 0.0;
            foreach ($this->directElementChildren($cell) as $child) {
                if (strtolower($child->tagName) === 'table') {
                    $nestedHeight += $this->estimateTableHeightPt($child);
                }
            }

            if ($nestedHeight > 0) {
                $contentDerived = max($contentDerived, $nestedHeight + 4);

                continue;
            }

            $cellFontPt = $this->inheritedFontSizePt($cell, $table);
            $cellLineHeight = $cellFontPt * $this->inheritedLineHeightRatio($cell, $table);
            $cellCss = $this->cssLengthToPt($cell->getAttribute('style'), 'height')
                ?? $this->cssLengthToPt($cell->getAttribute('style'), 'min-height');
            $lines = $this->estimateCellLines($cell, $this->cellCharsForWidth($cell, $columnCount));
            $cellPad = max(1.5, $cellFontPt * ($this->usesMpdfMetrics() ? 0.22 : 0.55));
            $contentPt = ($lines * $cellLineHeight) + $cellPad;
            // Soften TinyMCE cell height floors that exceed content-derived height.
            $contentDerived = max($contentDerived, $this->softenCssHeightFloor($cellCss, $contentPt));
        }

        return $this->softenCssHeightFloor($cssHeight, $contentDerived);
    }

    /**
     * TinyMCE often stores editor row/cell heights larger than PDF paint.
     * Prefer content-derived height when CSS is only a modestly larger floor;
     * otherwise keep a soft fraction of the surplus so packing is not starved.
     */
    private function softenCssHeightFloor(?float $cssHeight, float $contentPt): float
    {
        $contentPt = max(0.0, $contentPt);
        if ($cssHeight === null || $cssHeight <= 0) {
            return $contentPt;
        }

        if ($contentPt <= 0) {
            return $cssHeight;
        }

        if ($cssHeight <= $contentPt * 1.12) {
            return max($cssHeight, $contentPt);
        }

        $surplus = $cssHeight - $contentPt;
        if ($this->usesMpdfMetrics()) {
            // mPDF ignores most TinyMCE row height floors; keep almost none.
            return $contentPt + min($surplus * 0.08, $contentPt * 0.06);
        }

        return $contentPt + min($surplus * 0.28, $contentPt * 0.18);
    }

    private function inheritedFontSizePt(DOMElement $node, DOMElement $ancestor): float
    {
        $own = $this->cssLengthToPt($node->getAttribute('style'), 'font-size');
        if ($own !== null && $own > 0) {
            return $own;
        }

        return $this->styleFontSizePt($ancestor);
    }

    private function inheritedLineHeightRatio(DOMElement $node, DOMElement $ancestor): float
    {
        $style = $node->getAttribute('style');
        if ($style !== '' && preg_match('/(?:^|;)\s*line-height\s*:\s*([\d.]+)\s*;?/i', $style)) {
            return $this->styleLineHeightRatio($node);
        }

        return $this->styleLineHeightRatio($ancestor);
    }

    /**
     * Full-width (colspan) cells wrap against the full line, not one grid
     * column. Using column count alone made a 6-col title/"Whereas" row look
     * like an entire page and orphaned the following rows.
     */
    private function cellCharsForWidth(DOMElement $cell, int $columnCount): int
    {
        $span = max(1, (int) ($cell->getAttribute('colspan') ?: 1));
        $fraction = min(1.0, $span / max(1, $columnCount));

        return max(24, (int) floor(self::CHARS_PER_LINE * $fraction));
    }

    private function cssLengthToPt(string $style, string $property): ?float
    {
        if ($style === '') {
            return null;
        }

        $pattern = '/(?:^|;)\s*' . preg_quote($property, '/') . '\s*:\s*([\d.]+)\s*(pt|px|mm|cm|in|em|rem)?/i';
        if (! preg_match($pattern, $style, $match)) {
            return null;
        }

        $value = (float) $match[1];

        return match (strtolower($match[2] ?? 'pt')) {
            'px' => $value * 72 / 96,
            'mm' => $value * 72 / 25.4,
            'cm' => $value * 72 / 2.54,
            'in' => $value * 72,
            'em', 'rem' => $value * self::CONTENT_FONT_PT,
            default => $value,
        };
    }

    private function estimateImageHeightPt(DOMElement $img): float
    {
        if ($this->isForcedPageBreak($img)) {
            return 0.0;
        }

        $styleHeight = $this->cssLengthToPt($img->getAttribute('style'), 'height');
        if ($styleHeight !== null && $styleHeight > 0) {
            return min($this->budgetPt, $styleHeight);
        }

        $attr = trim($img->getAttribute('height'));
        if ($attr !== '' && is_numeric($attr)) {
            return min($this->budgetPt, ((float) $attr) * 72 / 96);
        }

        return 48.0;
    }

    private function estimateCellLines(DOMElement $cell, int $charsPerLine = 48): int
    {
        $html = strtolower($cell->ownerDocument?->saveHTML($cell) ?? '');
        $brLines = max(0, substr_count($html, '<br'));
        $text = trim(preg_replace('/\s+/u', ' ', $cell->textContent ?? '') ?? '');
        $wrap = $text === '' ? 1 : (int) ceil(mb_strlen($text) / max(24, $charsPerLine));

        return max(1, $brLines + 1, $wrap);
    }

    private function estimateListHeightPt(DOMElement $list): float
    {
        $total = 4.0;
        $counted = false;

        foreach ($list->childNodes as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $counted = true;
            // Do not safeEstimate here — callers wrap the list estimate once.
            $total += $this->estimateNodeHeightPt($child);
        }

        if (! $counted) {
            return max(4.0, $this->estimateBlockHeightPt($list));
        }

        return $total;
    }

    private function estimateBlockHeightPt(DOMElement $node): float
    {
        $html = strtolower($node->ownerDocument?->saveHTML($node) ?? '');
        $brLines = max(0, substr_count($html, '<br'));
        $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
        $fontPt = $this->styleFontSizePt($node);
        $lineHeightRatio = $this->styleLineHeightRatio($node);
        $textHeight = $this->estimateTextHeightPt($text, $fontPt, $lineHeightRatio);

        $imageHeight = 0.0;
        foreach ($node->getElementsByTagName('img') as $img) {
            if ($img instanceof DOMElement) {
                $imageHeight += $this->estimateImageHeightPt($img);
            }
        }

        if ($text === '' && $brLines === 0) {
            if ($imageHeight > 0) {
                return $imageHeight + 8.0;
            }

            $margin = strtolower($node->tagName) === 'p' ? $fontPt * $this->paragraphMarginEm() : 0.0;

            return ($fontPt * $lineHeightRatio) + $margin;
        }

        if ($brLines > 0) {
            $lineHeight = $fontPt * $lineHeightRatio;
            $wrapLines = max(1, (int) ceil(mb_strlen($text) / max(1, self::CHARS_PER_LINE)));
            $brLines = min($brLines, $wrapLines + 2);
            $textHeight = max($textHeight, ($brLines + 1) * $lineHeight);
        }

        $nestedTables = [];
        foreach ($this->directElementChildren($node) as $child) {
            if (strtolower($child->tagName) === 'table') {
                $nestedTables[] = $child;
            }
        }

        if ($nestedTables !== []) {
            $total = 0.0;
            foreach ($nestedTables as $table) {
                $total += $this->estimateTableHeightPt($table);
            }

            return $total + ($text === '' ? 0.0 : 4.0);
        }

        // Match engine CSS: mPDF .content p { margin: 0 0 0.32em }; Chrome 0.5em.
        return $textHeight + $imageHeight + ($fontPt * $this->paragraphMarginEm());
    }

    private function estimateTextHeightPt(string $text, float $fontSizePt, ?float $lineHeightRatio = null): float
    {
        if ($text === '') {
            return 0.0;
        }

        $lineHeight = $fontSizePt * ($lineHeightRatio ?? $this->defaultLineHeightRatio());
        $charsPerLine = $this->charsPerLineForFont($fontSizePt);
        $lines = max(1, (int) ceil(mb_strlen($text) / $charsPerLine));
        $fudge = $this->usesMpdfMetrics() ? 1.0 : 2.0;

        return ($lines * $lineHeight) + $fudge;
    }

    private function charsPerLineForFont(float $fontSizePt): int
    {
        $base = self::CHARS_PER_LINE;
        // mPDF wraps a touch earlier than the Dompdf/Chrome char budget.
        if ($this->usesMpdfMetrics()) {
            $base = (int) floor($base * 0.94);
        }
        $scale = self::CONTENT_FONT_PT / max(6.0, $fontSizePt);

        return max(40, (int) floor($base * $scale));
    }

    private function styleFontSizePt(DOMElement $node): float
    {
        $pt = $this->cssLengthToPt($node->getAttribute('style'), 'font-size');
        if ($pt !== null && $pt > 0) {
            return $pt;
        }

        // TinyMCE often puts font-size on nested spans, not the block itself.
        $fromDescendant = $this->dominantDescendantFontSizePt($node);
        if ($fromDescendant !== null) {
            return $fromDescendant;
        }

        return self::CONTENT_FONT_PT;
    }

    private function dominantDescendantFontSizePt(DOMElement $node): ?float
    {
        $weights = [];

        foreach ($node->getElementsByTagName('*') as $el) {
            if (! $el instanceof DOMElement) {
                continue;
            }

            $pt = $this->cssLengthToPt($el->getAttribute('style'), 'font-size');
            if ($pt === null || $pt <= 0) {
                continue;
            }

            $key = (string) round($pt, 2);
            $weights[$key] = ($weights[$key] ?? 0) + max(1, mb_strlen(trim($el->textContent ?? '')));
        }

        if ($weights === []) {
            return null;
        }

        arsort($weights);

        return (float) array_key_first($weights);
    }

    private function styleLineHeightRatio(DOMElement $node): float
    {
        $style = $node->getAttribute('style');
        if ($style !== '' && preg_match('/(?:^|;)\s*line-height\s*:\s*([\d.]+)\s*;?/i', $style, $match)) {
            $value = (float) $match[1];
            if ($value > 0 && $value <= 4) {
                return $value;
            }
        }

        return $this->defaultLineHeightRatio();
    }
}
