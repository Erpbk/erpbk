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
 */
class AgreementRtlTextShaper
{
    /**
     * Arabic script including Arabic, Supplement, Extended-A/B, Presentation Forms.
     */
    private const ARABIC_SCRIPT = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u';

    /**
     * Tashkeel / combining marks that trip Ar-PHP when adjacent to spaces.
     */
    private const ARABIC_MARKS = '/[\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u';

    private const BLOCK_TAGS = ['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'td', 'th', 'div', 'blockquote'];

    public function containsArabicScript(string $text): bool
    {
        return (bool) preg_match(self::ARABIC_SCRIPT, $text);
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

            $shaped = $this->shapeSegment($arabic, $segment);
            $wrapped = '<span class="agreement-ar">'.$shaped.'</span>';
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
                    $span = $doc->createElement('span');
                    $span->setAttribute('class', 'agreement-ar');
                    $span->appendChild($doc->createTextNode($this->shapeSegment($arabic, $part)));
                    $frag->appendChild($span);
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
     */
    private function shapeSegment(Arabic $arabic, string $segment): string
    {
        $prepared = preg_replace(self::ARABIC_MARKS, '', $segment) ?? $segment;
        $shaped = $this->utf8GlyphsSafe($arabic, $prepared);
        if ($shaped !== null) {
            return $shaped;
        }

        $shaped = $this->utf8GlyphsSafe($arabic, $segment);

        return $shaped ?? $segment;
    }

    private function utf8GlyphsSafe(Arabic $arabic, string $segment): ?string
    {
        set_error_handler(static function (int $severity, string $message): bool {
            return str_contains($message, 'Undefined array key')
                || str_contains($message, 'Trying to access array offset on value of type null');
        });

        try {
            // High max_chars avoids injecting \n wraps into long paragraphs.
            // hindo=false keeps Western digits (0-9) unchanged in mixed agreements.
            return $arabic->utf8Glyphs($segment, 10000, false);
        } catch (Throwable) {
            return null;
        } finally {
            restore_error_handler();
        }
    }
}
