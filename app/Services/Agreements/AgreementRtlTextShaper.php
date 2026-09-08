<?php

namespace App\Services\Agreements;

use ArPHP\I18N\Arabic;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Throwable;

/**
 * Dompdf cannot shape Arabic OpenType glyphs. Pre-process HTML with Ar-PHP
 * so connected letters render. Mixed docs keep English LTR and mark Arabic
 * segments/blocks only — never force whole-document RTL.
 *
 * Strategy: protect strong LTR tokens (Latin, Western digits, ID/phone) with
 * ASCII placeholders, run utf8Glyphs once on the protected Arabic stream so
 * phrase/word order stays correct, then restore LTR payloads unchanged.
 * Soft-wrap by measured width on logical whitespace tokens; never Ar-PHP
 * char-count wrap; never direction:rtl on shaped glyphs.
 * PDF wrap uses <bdo dir="ltr" class="agreement-ar"> plus Unicode LRO/PDF
 * (U+202D…U+202C) per shaped line; letterhead CSS also sets direction:ltr +
 * unicode-bidi:bidi-override on .agreement-ar / bdo.agreement-ar so Dompdf
 * cannot re-reverse visual-order Presentation Forms on paint.
 */
class AgreementRtlTextShaper
{
    /**
     * Arabic script including Arabic, Supplement, Extended-A/B, Presentation Forms.
     */
    private const ARABIC_SCRIPT = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';

    /**
     * Strong LTR tokens: Latin, Western digits, and ID/phone/email punctuation
     * bound to those tokens (hyphenated IDs, slashes, chassis codes, etc.).
     */
    private const LTR_TOKEN = '/\+?[A-Za-z0-9][A-Za-z0-9\-\/:._+%@]*/u';

    /**
     * Tashkeel / combining marks that trip Ar-PHP when adjacent to spaces.
     */
    private const ARABIC_MARKS = '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u';

    /**
     * Pass a huge max to utf8Glyphs so Ar-PHP does not soft-wrap by character
     * count; we break lines ourselves by measured width.
     */
    private const PDF_GLYPHS_NO_WRAP = 999999;

    /** U+202D LEFT-TO-RIGHT OVERRIDE — force visual-order glyph paint LTR. */
    private const LRO = "\u{202D}";

    /** U+202C POP DIRECTIONAL FORMATTING — close LRO. */
    private const PDF = "\u{202C}";

    /**
     * Default usable content width (pt) when caller has not configured layout —
     * roughly A4 minus 12mm side margins at 11pt body size.
     */
    private const DEFAULT_CONTENT_WIDTH_PT = 527.0;

    private const DEFAULT_FONT_SIZE_PT = 11.0;

    private const BLOCK_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'div', 'blockquote'];

    private float $contentWidthPt = self::DEFAULT_CONTENT_WIDTH_PT;

    private float $fontSizePt = self::DEFAULT_FONT_SIZE_PT;

    private ?string $fontFile = null;

    /** @var (callable(string, float, ?string): float)|null */
    private $widthMeasurer = null;

    public function containsArabicScript(string $text): bool
    {
        return (bool) preg_match(self::ARABIC_SCRIPT, $text);
    }

    /**
     * Configure measure-based wrapping for the PDF content column.
     *
     * @param  float  $contentWidthPt  Usable content/column width in PDF points
     * @param  float  $fontSizePt  Arabic body font size in points
     * @param  string|null  $fontFile  Absolute path to the Arabic TTF used in the PDF
     */
    public function configureForPdf(float $contentWidthPt, float $fontSizePt, ?string $fontFile = null): self
    {
        $this->contentWidthPt = max(1.0, $contentWidthPt);
        $this->fontSizePt = max(1.0, $fontSizePt);
        $this->fontFile = $fontFile;

        return $this;
    }

    /**
     * Inject a width measurer for unit tests: fn(string $text, float $sizePt, ?string $fontFile): float
     *
     * @param  (callable(string, float, ?string): float)|null  $measurer
     */
    public function setWidthMeasurer(?callable $measurer): self
    {
        $this->widthMeasurer = $measurer;

        return $this;
    }

    public function contentWidthPt(): float
    {
        return $this->contentWidthPt;
    }

    public function fontSizePt(): float
    {
        return $this->fontSizePt;
    }

    /**
     * Shape Arabic runs for Dompdf and mark per-segment/block direction helpers.
     *
     * `rtl` is always false for document-wide flags (mixed LTR/RTL).
     * `has_arabic` is true when Arabic was present and shaped/marked.
     *
     * @return array{html: string, rtl: bool, has_arabic: bool}
     */
    public function shapeHtmlForPdf(string $html): array
    {
        if ($html === '' || ! $this->containsArabicScript($html)) {
            return ['html' => $html, 'rtl' => false, 'has_arabic' => false];
        }

        try {
            $shaped = $this->shapeWithArPhp($html);
        } catch (Throwable) {
            try {
                $shaped = $this->shapeTextNodes($html);
            } catch (Throwable) {
                $shaped = $html;
            }
        }

        try {
            $shaped = $this->prepareMixedDirectionBlocks($shaped);
        } catch (Throwable) {
            // Keep shaped HTML even if block marking fails.
        }

        return [
            'html' => $shaped,
            'rtl' => false,
            'has_arabic' => true,
        ];
    }

    private function shapeWithArPhp(string $html): string
    {
        $arabic = new Arabic();
        $positions = $arabic->arIdentify($html);

        for ($i = count($positions) - 1; $i >= 0; $i -= 2) {
            $start = (int) $positions[$i - 1];
            $end = (int) $positions[$i];
            $length = $end - $start;
            if ($length <= 0) {
                continue;
            }

            $segment = substr($html, $start, $length);
            if ($segment === '' || ! $this->containsArabicScript($segment)) {
                continue;
            }

            // Skip if this offset already sits inside a tag name/attribute.
            if ($this->offsetInsideHtmlTag($html, $start)) {
                continue;
            }

            $shaped = $this->newlinesToBr($this->shapeSegment($arabic, $segment));
            $wrapped = '<bdo dir="ltr" class="agreement-ar">'.$shaped.'</bdo>';
            $html = substr_replace($html, $wrapped, $start, $length);
        }

        return $html;
    }

    private function offsetInsideHtmlTag(string $html, int $offset): bool
    {
        $before = substr($html, 0, $offset);
        $lastOpen = strrpos($before, '<');
        $lastClose = strrpos($before, '>');

        return $lastOpen !== false && ($lastClose === false || $lastOpen > $lastClose);
    }

    /**
     * Fallback: walk text nodes when arIdentify cannot run on malformed HTML.
     */
    private function shapeTextNodes(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="rtl-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('rtl-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        try {
            $arabic = new Arabic();
        } catch (Throwable) {
            return $html;
        }

        $this->shapeDomNode($root, $arabic);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }

        return $out !== '' ? $out : $html;
    }

    private function shapeDomNode(DOMNode $node, Arabic $arabic): void
    {
        if ($node instanceof DOMText) {
            $text = $node->nodeValue ?? '';
            if ($text === '' || ! $this->containsArabicScript($text)) {
                return;
            }

            $parent = $node->parentNode;
            $doc = $node->ownerDocument;
            if (! $parent || ! $doc) {
                $node->nodeValue = $this->shapeSegment($arabic, $text);

                return;
            }

            // Split mixed text into Latin + wrapped Arabic runs.
            $parts = preg_split(
                '/('.substr(self::ARABIC_SCRIPT, 1, -2).'+)/u',
                $text,
                -1,
                PREG_SPLIT_DELIM_CAPTURE
            ) ?: [$text];

            $frag = $doc->createDocumentFragment();
            foreach ($parts as $part) {
                if ($part === '') {
                    continue;
                }
                if ($this->containsArabicScript($part)) {
                    $bdo = $doc->createElement('bdo');
                    $bdo->setAttribute('dir', 'ltr');
                    $bdo->setAttribute('class', 'agreement-ar');
                    $this->appendShapedTextWithBreaks($bdo, $this->shapeSegment($arabic, $part));
                    $frag->appendChild($bdo);
                } else {
                    $frag->appendChild($doc->createTextNode($part));
                }
            }
            $parent->replaceChild($frag, $node);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'code', 'pre'], true)) {
            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->shapeDomNode($child, $arabic);
        }
    }

    /**
     * Honour editor dir=rtl/ltr per block; auto-mark Arabic-heavy blocks.
     * For PDF we never keep direction:rtl on shaped text (would reverse twice).
     */
    private function prepareMixedDirectionBlocks(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="rtl-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('rtl-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('.//*', $root) ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            // Strip CSS direction/unicode-bidi from every element (TinyMCE inline
            // styles) so author styles do not fight letterhead .agreement-ar
            // direction:ltr + unicode-bidi:bidi-override. Visual-order glyphs would
            // reverse twice if TinyMCE direction:rtl survived.
            $this->stripDirectionFromInlineStyle($node);

            $tag = strtolower($node->tagName);
            if (! in_array($tag, self::BLOCK_TAGS, true)) {
                continue;
            }

            $dir = strtolower(trim($node->getAttribute('dir')));
            $text = $node->textContent ?? '';

            if ($dir === 'rtl' || ($dir === '' && $this->isMostlyArabic($text))) {
                $this->addClass($node, 'agreement-ar-block');
            }

            // Drop dir on PDF HTML so Dompdf does not re-reverse visual-order glyphs.
            if ($dir === 'rtl' || $dir === 'ltr') {
                $node->removeAttribute('dir');
                if ($dir === 'ltr') {
                    $this->addClass($node, 'agreement-ltr-block');
                }
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }

        return $out !== '' ? $out : $html;
    }

    /**
     * Remove direction / unicode-bidi from inline style; keep text-align, fonts, etc.
     */
    private function stripDirectionFromInlineStyle(DOMElement $element): void
    {
        if (! $element->hasAttribute('style')) {
            return;
        }

        $style = $element->getAttribute('style');
        if ($style === '') {
            return;
        }

        $parts = preg_split('/\s*;\s*/', $style, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $kept = [];
        foreach ($parts as $part) {
            $decl = trim($part);
            if ($decl === '') {
                continue;
            }
            if (preg_match('/^(direction|unicode-bidi)\s*:/i', $decl) === 1) {
                continue;
            }
            $kept[] = $decl;
        }

        if ($kept === []) {
            $element->removeAttribute('style');
        } else {
            $element->setAttribute('style', implode('; ', $kept).';');
        }
    }

    private function isMostlyArabic(string $text): bool
    {
        if (! $this->containsArabicScript($text)) {
            return false;
        }

        $arabic = preg_match_all(self::ARABIC_SCRIPT, $text) ?: 0;
        $latin = preg_match_all('/[A-Za-z]/', $text) ?: 0;

        return $arabic > 0 && $arabic >= max(1, $latin);
    }

    private function addClass(DOMElement $element, string $class): void
    {
        $existing = trim($element->getAttribute('class'));
        $parts = $existing === '' ? [] : (preg_split('/\s+/', $existing) ?: []);
        if (! in_array($class, $parts, true)) {
            $parts[] = $class;
        }
        $element->setAttribute('class', implode(' ', $parts));
    }

    /**
     * Ar-PHP utf8Glyphs emits notices on some harakat+space sequences; Laravel
     * promotes those to ErrorException. Dompdf also places leftover tashkeel
     * poorly on presentation forms, so strip marks before shaping.
     *
     * Packs the logical segment into width-fitting lines, then shapes each line
     * with mixed BiDi (Arabic glyphs only; LTR islands preserved).
     */
    private function shapeSegment(Arabic $arabic, string $segment): string
    {
        $prepared = preg_replace(self::ARABIC_MARKS, '', $segment) ?? $segment;
        $lines = $this->wrapAndShapeLines($arabic, $prepared);
        if ($lines !== []) {
            return implode("\n", array_map(
                fn (string $line): string => $this->applyLtrOverrideMarkers($line),
                $lines
            ));
        }

        $fallback = $this->shapeMixedVisual($arabic, $segment);
        if ($fallback === null) {
            return $segment;
        }

        return $this->applyLtrOverrideMarkers($fallback);
    }

    /**
     * @return list<string> Visual-order shaped lines (no trailing newlines)
     */
    private function wrapAndShapeLines(Arabic $arabic, string $text): array
    {
        $paragraphs = preg_split("/\r\n|\n|\r/", $text);
        if ($paragraphs === false || $paragraphs === []) {
            $paragraphs = [$text];
        }

        $out = [];
        foreach ($paragraphs as $paragraph) {
            if ($paragraph === '') {
                $out[] = '';
                continue;
            }

            foreach ($this->packParagraphByWidth($arabic, $paragraph) as $line) {
                $out[] = $line;
            }
        }

        return $out;
    }

    /**
     * Pack a single paragraph (no hard newlines) by measured glyph width.
     * Tokenizes on whitespace so hyphenated LTR IDs / chassis codes stay intact.
     *
     * @return list<string>
     */
    private function packParagraphByWidth(Arabic $arabic, string $paragraph): array
    {
        $shapedWhole = $this->shapeMixedVisual($arabic, $paragraph);
        if ($shapedWhole !== null && $this->measureWidthPt($shapedWhole) <= $this->contentWidthPt) {
            return [$shapedWhole];
        }

        $words = preg_split('/\s+/u', trim($paragraph), -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return [$shapedWhole ?? $paragraph];
        }

        $lines = [];
        $current = '';

        foreach ($words as $word) {
            $candidate = $current === '' ? $word : $current.' '.$word;
            $shapedCandidate = $this->shapeMixedVisual($arabic, $candidate);
            $width = $this->measureWidthPt($shapedCandidate ?? $candidate);

            if ($current !== '' && $width > $this->contentWidthPt) {
                $shapedLine = $this->shapeMixedVisual($arabic, $current);
                $lines[] = $shapedLine ?? $current;
                $current = $word;
                continue;
            }

            $current = $candidate;
        }

        if ($current !== '') {
            $shapedLine = $this->shapeMixedVisual($arabic, $current);
            $lines[] = $shapedLine ?? $current;
        }

        return $lines !== [] ? $lines : [$shapedWhole ?? $paragraph];
    }

    /**
     * Shape mixed Arabic + Western text for Dompdf LTR embedding.
     *
     * Protect strong LTR tokens with ASCII placeholders utf8Glyphs will not
     * alter, shape the whole protected string once (Arabic words + spaces +
     * punctuation stay one stream so phrase order stays correct), then restore
     * original LTR payloads without reversing them. Do not reverse runs —
     * utf8Glyphs already emits visual-order presentation forms.
     */
    private function shapeMixedVisual(Arabic $arabic, string $text): ?string
    {
        if ($text === '') {
            return '';
        }

        if (! $this->containsArabicScript($text)) {
            return $text;
        }

        $map = [];
        $protected = preg_replace_callback(
            self::LTR_TOKEN,
            static function (array $matches) use (&$map): string {
                $key = '[[~L'.count($map).'~]]';
                $map[$key] = $matches[0];

                return $key;
            },
            $text
        );

        if ($protected === null) {
            return null;
        }

        $glyphs = $this->utf8GlyphsSafe($arabic, $protected);
        if ($glyphs === null) {
            return null;
        }

        if ($map !== []) {
            // strtr tries longest keys first, so [[~L10~]] is safe vs [[~L1~]].
            $glyphs = strtr($glyphs, $map);
        }

        return $glyphs;
    }

    /**
     * Measure text width in PDF points against the configured Arabic face.
     * Prefer an injected measurer (tests), then GD imagettfbbox on the TTF
     * (same face Dompdf embeds), then a conservative em estimate.
     */
    private function measureWidthPt(string $text): float
    {
        if ($text === '') {
            return 0.0;
        }

        if ($this->widthMeasurer !== null) {
            return (float) ($this->widthMeasurer)($text, $this->fontSizePt, $this->fontFile);
        }

        $fontFile = $this->fontFile ?? $this->defaultArabicFontPath();
        if ($fontFile !== null && function_exists('imagettfbbox') && is_readable($fontFile)) {
            $box = @imagettfbbox($this->fontSizePt, 0, $fontFile, $text);
            if (is_array($box)) {
                $widthPx = (float) abs($box[2] - $box[0]);
                // PHP GD FreeType typically rasterizes at 96 DPI while size is in pt.
                return $widthPx * 72.0 / 96.0;
            }
        }

        // Fallback when TTF/GD unavailable: ~0.55em average for Arabic presentation forms.
        return mb_strlen($text, 'UTF-8') * $this->fontSizePt * 0.55;
    }

    private function defaultArabicFontPath(): ?string
    {
        $candidates = [
            resource_path('fonts/agreements/Amiri-Regular.ttf'),
            storage_path('fonts/amiri-normal-normal.ttf'),
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Wrap a shaped glyph line with Unicode LRO…PDF so plain-text / Dompdf
     * BiDi cannot re-apply Arabic RTL to Presentation Forms.
     */
    private function applyLtrOverrideMarkers(string $glyphs): string
    {
        if ($glyphs === '') {
            return '';
        }

        return self::LRO.$glyphs.self::PDF;
    }

    /**
     * Convert soft-wrap newlines into HTML breaks for string insertion.
     */
    private function newlinesToBr(string $shaped): string
    {
        return str_replace(["\r\n", "\n", "\r"], '<br />', $shaped);
    }

    /**
     * Append shaped glyph text to a DOM element, turning soft-wrap newlines
     * into real <br> nodes (never put raw "<br>" into createTextNode).
     */
    private function appendShapedTextWithBreaks(DOMElement $parent, string $shaped): void
    {
        $doc = $parent->ownerDocument;
        if (! $doc) {
            return;
        }

        $lines = preg_split("/\r\n|\n|\r/", $shaped) ?: [$shaped];
        $last = count($lines) - 1;
        foreach ($lines as $i => $line) {
            if ($line !== '') {
                $parent->appendChild($doc->createTextNode($line));
            }
            if ($i < $last) {
                $parent->appendChild($doc->createElement('br'));
            }
        }
    }

    private function utf8GlyphsSafe(Arabic $arabic, string $segment): ?string
    {
        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'Undefined array key')
                || str_contains($message, 'Trying to access array offset on value of type null');
        });

        try {
            // No Ar-PHP char-count wrap; width packing owns line breaks.
            // hindo=false keeps Western digits (0-9) unchanged in mixed agreements.
            return $arabic->utf8Glyphs($segment, self::PDF_GLYPHS_NO_WRAP, false);
        } catch (Throwable) {
            return null;
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Prepare logical Unicode HTML for mPDF (no Ar-PHP glyphs, no bdo/LRO).
     * Marks Arabic-heavy / dir=rtl blocks so letterhead CSS can apply
     * direction:rtl; text-align:right and the Arabic font stack.
     *
     * @return array{html: string, rtl: bool, has_arabic: bool}
     */
    public function prepareLogicalHtmlForMpdf(string $html): array
    {
        $hasArabic = $html !== '' && $this->containsArabicScript($html);
        if ($html === '') {
            return ['html' => $html, 'rtl' => false, 'has_arabic' => false];
        }

        $wrapped = $html;

        // Same logical prep as Chrome: Arabic block marks, field-label reorder, LTR isolates.
        // mPDF shapes OpenType under direction:rtl; <bdi dir="ltr"> keeps phones/IDs ordered.
        if ($hasArabic) {
            try {
                $wrapped = $this->markLogicalArabicBlocks($wrapped);
            } catch (Throwable) {
                $wrapped = $html;
            }

            try {
                $wrapped = $this->normalizeChromeRtlFieldLabelOrder($wrapped);
            } catch (Throwable) {
                // Keep marked HTML even if field-order normalization fails.
            }
        }

        try {
            $wrapped = $this->wrapLtrTokensForChrome($wrapped);
        } catch (Throwable) {
            // Keep marked HTML even if LTR wrapping fails.
        }

        return [
            'html' => $wrapped,
            'rtl' => false,
            'has_arabic' => $hasArabic,
        ];
    }

    /**
     * Keep logical Unicode; add agreement-ar / agreement-ar-block helpers for CSS.
     * Preserve editor dir=rtl and direction:rtl (mPDF shapes OpenType correctly).
     */
    private function markLogicalArabicBlocks(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="rtl-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('rtl-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('.//*', $root) ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($node->tagName);
            if (! in_array($tag, self::BLOCK_TAGS, true)) {
                continue;
            }

            $dir = strtolower(trim($node->getAttribute('dir')));
            $text = $node->textContent ?? '';

            if ($dir === 'rtl' || ($dir === '' && $this->isMostlyArabic($text))) {
                $this->addClass($node, 'agreement-ar-block');
                $this->addClass($node, 'agreement-ar');
                if ($dir === '') {
                    $node->setAttribute('dir', 'rtl');
                }
            } elseif ($dir === 'ltr') {
                $this->addClass($node, 'agreement-ltr-block');
            }
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }

        return $out !== '' ? $out : $html;
    }

    /**
     * Prepare logical Unicode HTML for Chrome/Edge headless PDF.
     * No Ar-PHP glyph shaping — Chrome shapes Arabic natively under direction:rtl.
     * Wrap strong LTR tokens (phones, IDs, codes, plates) in <bdi dir="ltr"> so
     * hyphen/slash neutrals keep editor order instead of BiDi-reversing to
     * e.g. 4-6268395-1988-784.
     *
     * @return array{html: string, rtl: bool, has_arabic: bool}
     */
    public function prepareLogicalHtmlForChrome(string $html): array
    {
        $hasArabic = $html !== '' && $this->containsArabicScript($html);
        if ($html === '') {
            return ['html' => $html, 'rtl' => false, 'has_arabic' => false];
        }

        $wrapped = $html;

        // Mark Arabic-heavy / dir=rtl blocks for letterhead Chrome CSS helpers first,
        // then normalize TinyMCE-reversed field lines (LTR value + colon + Arabic label),
        // then wrap LTR tokens so a second DOM parse cannot drop <bdi> isolates.
        if ($hasArabic) {
            try {
                $wrapped = $this->markLogicalArabicBlocks($wrapped);
            } catch (Throwable) {
                $wrapped = $html;
            }

            try {
                $wrapped = $this->normalizeChromeRtlFieldLabelOrder($wrapped);
            } catch (Throwable) {
                // Keep marked HTML even if field-order normalization fails.
            }
        }

        try {
            $wrapped = $this->wrapLtrTokensForChrome($wrapped);
        } catch (Throwable) {
            // Keep marked HTML even if LTR wrapping fails.
        }

        return [
            'html' => $wrapped,
            'rtl' => false,
            'has_arabic' => $hasArabic,
        ];
    }


    /**
     * TinyMCE RTL sometimes stores field lines as LTR_VALUE + colon + Arabic_label
     * (value-before-label). With direction:rtl that puts the heading on the wrong side.
     * Normalize to label + colon + value (same logical order as correct plate lines).
     * Only rewrites lines that are essentially that reversed field pattern — leaves
     * already label-first plate lines and body prose (phones mid-sentence) alone.
     */
    private function normalizeChromeRtlFieldLabelOrder(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="rtl-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('rtl-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('.//*', $root) ?: [] as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($node->tagName);
            if (! in_array($tag, self::BLOCK_TAGS, true)) {
                continue;
            }
            $this->normalizeChromeRtlFieldLinesInBlock($node);
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }

        return $out !== '' ? $out : $html;
    }

    /**
     * Walk a block's children line-by-line (split on <br>) and fix reversed field order.
     */
    private function normalizeChromeRtlFieldLinesInBlock(DOMElement $block): void
    {
        $doc = $block->ownerDocument;
        if (! $doc) {
            return;
        }

        // Only leaf blocks — avoid flattening a parent that wraps nested <p>/<div>.
        foreach ($block->childNodes as $child) {
            if ($child instanceof DOMElement && in_array(strtolower($child->tagName), self::BLOCK_TAGS, true)) {
                return;
            }
        }

        $children = iterator_to_array($block->childNodes);
        if ($children === []) {
            return;
        }

        $lines = [];
        $current = [];
        foreach ($children as $child) {
            if ($child instanceof DOMElement && strtolower($child->tagName) === 'br') {
                $lines[] = ['nodes' => $current, 'br' => $child];
                $current = [];
                continue;
            }
            $current[] = $child;
        }
        $lines[] = ['nodes' => $current, 'br' => null];

        $changed = false;
        $rebuilt = [];
        foreach ($lines as $line) {
            $normalized = $this->normalizeChromeRtlFieldLineNodes($doc, $line['nodes']);
            if ($normalized !== null) {
                $changed = true;
                foreach ($normalized as $n) {
                    $rebuilt[] = $n;
                }
            } else {
                foreach ($line['nodes'] as $n) {
                    $rebuilt[] = $n;
                }
            }
            if ($line['br'] instanceof DOMElement) {
                $rebuilt[] = $line['br'];
            }
        }

        if (! $changed) {
            return;
        }

        while ($block->firstChild) {
            $block->removeChild($block->firstChild);
        }
        foreach ($rebuilt as $n) {
            $block->appendChild($n);
        }
    }

    /**
     * @param  list<DOMNode>  $nodes
     * @return list<DOMNode>|null  Replacement nodes, or null if no change
     */
    private function normalizeChromeRtlFieldLineNodes(DOMDocument $doc, array $nodes): ?array
    {
        if ($nodes === []) {
            return null;
        }

        $text = '';
        foreach ($nodes as $node) {
            $text .= $node->textContent ?? '';
        }
        $trimmed = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($trimmed === '') {
            return null;
        }

        // Reversed TinyMCE field: LTR value, colon, Arabic label (entire line).
        if (preg_match(
            '/^([A-Za-z0-9][A-Za-z0-9\-\/:._+%@]*)\s*(:|：)\s*(.+)$/u',
            $trimmed,
            $m
        ) !== 1) {
            return null;
        }

        $value = $m[1];
        $colon = $m[2];
        $label = trim($m[3]);

        if ($label === '' || ! $this->containsArabicScript($label) || ! $this->isMostlyArabic($label)) {
            return null;
        }

        // Reject if the "label" still embeds another LTR field token (not a plain heading).
        if (preg_match(self::LTR_TOKEN, $label) === 1 && preg_match('/[0-9]/', $label) === 1) {
            return null;
        }

        // Preserve TinyMCE inline typography so rebuilt lines match sibling field lines.
        $lineStyle = $this->bestChromeRtlFieldLineStyle($nodes);
        $lineClass = $this->bestChromeRtlFieldLineClass($nodes);
        $valueStyle = $this->bestChromeRtlFieldValueStyle($nodes, $value);

        // Rebuild like plate: <strong>LABEL <span>:</span></strong><span>VALUE</span>
        // optionally wrapped in a span carrying the Arabic body font-size/family.
        $strong = $doc->createElement('strong');
        $strong->appendChild($doc->createTextNode($label.' '));
        $colonSpan = $doc->createElement('span');
        $colonSpan->appendChild($doc->createTextNode($colon));
        $strong->appendChild($colonSpan);

        $valueSpan = $doc->createElement('span');
        if ($valueStyle !== '') {
            $valueSpan->setAttribute('style', $valueStyle);
        }
        $valueSpan->appendChild($doc->createTextNode($value));

        if ($lineStyle !== '' || $lineClass !== '') {
            $wrapper = $doc->createElement('span');
            if ($lineStyle !== '') {
                $wrapper->setAttribute('style', $lineStyle);
            }
            if ($lineClass !== '') {
                $wrapper->setAttribute('class', $lineClass);
            }
            $wrapper->appendChild($strong);
            $wrapper->appendChild($valueSpan);

            return [$wrapper];
        }

        return [$strong, $valueSpan];
    }

    /**
     * Prefer a wrapper/element style with font-size and/or Arabic (Noto) font-family.
     *
     * @param  list<DOMNode>  $nodes
     */
    private function bestChromeRtlFieldLineStyle(array $nodes): string
    {
        $candidates = $this->collectInlineStylesFromNodes($nodes);
        $best = '';
        $bestScore = -1;
        foreach ($candidates as $style) {
            $score = 0;
            if (preg_match('/font-size\s*:/i', $style) === 1) {
                $score += 4;
            }
            if (preg_match('/font-family\s*:/i', $style) === 1) {
                $score += 2;
                if (preg_match('/Noto|Naskh|Arabic|Amiri|Lateef|Scheherazade/i', $style) === 1) {
                    $score += 3;
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $style;
            }
        }

        return $best;
    }

    /**
     * Optional class from the best-styled element (same preference as line style).
     *
     * @param  list<DOMNode>  $nodes
     */
    private function bestChromeRtlFieldLineClass(array $nodes): string
    {
        $bestClass = '';
        $bestScore = -1;
        $this->walkDomNodes($nodes, function (DOMNode $node) use (&$bestClass, &$bestScore): void {
            if (! $node instanceof DOMElement) {
                return;
            }
            $style = trim($node->getAttribute('style'));
            $class = trim($node->getAttribute('class'));
            if ($style === '' && $class === '') {
                return;
            }
            $score = 0;
            if (preg_match('/font-size\s*:/i', $style) === 1) {
                $score += 4;
            }
            if (preg_match('/font-family\s*:/i', $style) === 1) {
                $score += 2;
                if (preg_match('/Noto|Naskh|Arabic|Amiri|Lateef|Scheherazade/i', $style) === 1) {
                    $score += 3;
                }
            }
            if ($class !== '') {
                $score += 1;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestClass = $class;
            }
        });

        return $bestClass;
    }

    /**
     * Prefer Calibri / font-family from the original LTR value node when present.
     *
     * @param  list<DOMNode>  $nodes
     */
    private function bestChromeRtlFieldValueStyle(array $nodes, string $value): string
    {
        $best = '';
        $bestScore = -1;
        $this->walkDomNodes($nodes, function (DOMNode $node) use (&$best, &$bestScore, $value): void {
            if (! $node instanceof DOMElement) {
                return;
            }
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent ?? '') ?? '');
            $style = trim($node->getAttribute('style'));
            if ($style === '') {
                return;
            }
            $score = 0;
            // Exact / near-exact value carrier wins.
            if ($text === $value || str_starts_with($text, $value)) {
                $score += 6;
            }
            if (preg_match('/font-family\s*:/i', $style) === 1) {
                $score += 2;
                if (preg_match('/Calibri|Arial|Helvetica|Roboto|sans-serif/i', $style) === 1) {
                    $score += 3;
                }
            }
            if (preg_match('/font-size\s*:/i', $style) === 1) {
                $score += 1;
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $style;
            }
        });

        return $best;
    }

    /**
     * @param  list<DOMNode>  $nodes
     * @return list<string>
     */
    private function collectInlineStylesFromNodes(array $nodes): array
    {
        $styles = [];
        $this->walkDomNodes($nodes, function (DOMNode $node) use (&$styles): void {
            if ($node instanceof DOMElement) {
                $style = trim($node->getAttribute('style'));
                if ($style !== '') {
                    $styles[] = $style;
                }
            }
        });

        return $styles;
    }

    /**
     * @param  list<DOMNode>  $nodes
     * @param  callable(DOMNode): void  $visitor
     */
    private function walkDomNodes(array $nodes, callable $visitor): void
    {
        foreach ($nodes as $node) {
            $visitor($node);
            if ($node->hasChildNodes()) {
                $this->walkDomNodes(iterator_to_array($node->childNodes), $visitor);
            }
        }
    }
    private function wrapLtrTokensForChrome(string $html): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="rtl-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('rtl-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $this->wrapLtrTokensInDomNode($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child) ?: '';
        }

        return $out !== '' ? $out : $html;
    }

    private function wrapLtrTokensInDomNode(DOMNode $node): void
    {
        if ($node instanceof DOMText) {
            $this->wrapLtrTokensInTextNode($node);

            return;
        }

        if (! $node instanceof DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'code', 'pre'], true)) {
            return;
        }

        // Already inside an LTR isolate — do not nest another wrap.
        if ($this->elementOrAncestorIsLtrIsolate($node)) {
            return;
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $this->wrapLtrTokensInDomNode($child);
        }
    }

    private function elementOrAncestorIsLtrIsolate(DOMElement $element): bool
    {
        $n = $element;
        while ($n instanceof DOMElement) {
            $dir = strtolower(trim($n->getAttribute('dir')));
            if ($dir === 'ltr') {
                return true;
            }
            $n = $n->parentNode instanceof DOMElement ? $n->parentNode : null;
        }

        return false;
    }

    private function wrapLtrTokensInTextNode(DOMText $node): void
    {
        $text = $node->nodeValue ?? '';
        if ($text === '' || preg_match(self::LTR_TOKEN, $text) !== 1) {
            return;
        }

        $parent = $node->parentNode;
        $doc = $node->ownerDocument;
        if (! $parent instanceof DOMElement || ! $doc) {
            return;
        }

        if ($this->elementOrAncestorIsLtrIsolate($parent)) {
            return;
        }

        $parts = preg_split(
            '/(\+?[A-Za-z0-9][A-Za-z0-9\-\/:._+%@]*)/u',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if ($parts === false || count($parts) <= 1) {
            return;
        }

        $needsWrap = false;
        foreach ($parts as $i => $part) {
            if ($i % 2 === 1 && $this->shouldIsolateLtrTokenForChrome($part)) {
                $needsWrap = true;
                break;
            }
        }
        if (! $needsWrap) {
            return;
        }

        $frag = $doc->createDocumentFragment();
        foreach ($parts as $i => $part) {
            if ($part === '') {
                continue;
            }
            if ($i % 2 === 1 && $this->shouldIsolateLtrTokenForChrome($part)) {
                $bdi = $doc->createElement('bdi');
                $bdi->setAttribute('dir', 'ltr');
                $bdi->appendChild($doc->createTextNode($part));
                $frag->appendChild($bdi);
            } else {
                $frag->appendChild($doc->createTextNode($part));
            }
        }
        $parent->replaceChild($frag, $node);
    }

    /**
     * Isolate Western digit / Latin runs that BiDi would scramble under RTL:
     * hyphenated phones/IDs, slash codes, alphanumeric Latin+digit tokens.
     */
    private function shouldIsolateLtrTokenForChrome(string $token): bool
    {
        if ($token === '' || preg_match('/[0-9]/', $token) !== 1) {
            return false;
        }

        // Digits + phone/ID punctuation, or letters+digits, or digit runs.
        return preg_match('/[\-\/:._+%@]/', $token) === 1
            || preg_match('/[A-Za-z]/', $token) === 1
            || preg_match('/\d{2,}/', $token) === 1;
    }
}
