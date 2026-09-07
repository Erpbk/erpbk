<?php

namespace App\Services\Agreements;

use ArPHP\I18N\Arabic;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Throwable;

/**
 * Dompdf cannot shape Arabic/Urdu OpenType glyphs. Pre-process HTML with Ar-PHP
 * so connected letters render, and mark the document for RTL-friendly alignment.
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

    public function containsArabicScript(string $text): bool
    {
        return (bool) preg_match(self::ARABIC_SCRIPT, $text);
    }

    /**
     * Shape Arabic/Urdu runs for Dompdf and flag whether the HTML is RTL-heavy.
     *
     * @return array{html: string, rtl: bool}
     */
    public function shapeHtmlForPdf(string $html): array
    {
        if ($html === '' || ! $this->containsArabicScript($html)) {
            return ['html' => $html, 'rtl' => false];
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

        return [
            'html' => $shaped,
            'rtl' => true,
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

            $shaped = $this->shapeSegment($arabic, $segment);
            $html = substr_replace($html, $shaped, $start, $length);
        }

        return $html;
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
            if ($text !== '' && $this->containsArabicScript($text)) {
                $node->nodeValue = $this->shapeSegment($arabic, $text);
            }

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

        // Last resort: original text (may still crash Ar-PHP — guarded above).
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
