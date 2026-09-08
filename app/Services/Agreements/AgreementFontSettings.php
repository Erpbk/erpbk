<?php

namespace App\Services\Agreements;

use DOMDocument;
use DOMElement;

class AgreementFontSettings
{
    /**
     * @return list<string>
     */
    public function families(): array
    {
        return array_values(config('agreement_fonts.families', ['Calibri']));
    }

    public function defaultFamily(): string
    {
        return (string) config('agreement_fonts.family', 'Calibri');
    }

    public function familyStackCss(): string
    {
        $names = array_merge([$this->defaultFamily()], config('agreement_fonts.fallbacks', []));
        $parts = [];
        foreach (array_unique($names) as $name) {
            $parts[] = preg_match('/\s/', (string) $name) ? "'" . $name . "'" : $name;
        }

        return implode(', ', $parts);
    }

    /**
     * Prefer a Naskh face that includes Arabic Presentation Forms for Dompdf.
     */
    public function rtlDefaultFamily(): string
    {
        // Amiri ships filled Arabic Presentation Forms outlines that Dompdf embeds
        // reliably; some Naskh builds map those codepoints but still paint .notdef.
        return 'Amiri';
    }

    /**
     * Prefer a Naskh face that includes Arabic Presentation Forms for Dompdf.
     */
    public function rtlFamilyStackCss(): string
    {
        $preferred = $this->rtlDefaultFamily();
        $names = array_values(array_unique(array_filter([
            $preferred,
            'Scheherazade New',
            'Noto Naskh Arabic',
        ])));

        $parts = [];
        foreach ($names as $name) {
            $parts[] = preg_match('/\s/', (string) $name) ? "'" . $name . "'" : $name;
        }

        return implode(', ', $parts);
    }

    /**
     * TinyMCE often stamps Calibri/Arial onto spans; Dompdf then paints shaped
     * presentation-form codepoints with a Latin face → missing-glyph boxes.
     *
     * Only force the Arabic face onto marked Arabic segments/blocks — never the
     * whole bilingual document.
     */
    public function forceRtlFontFamiliesInHtml(string $html): string
    {
        if ($html === '' || (! str_contains($html, 'agreement-ar') && ! str_contains($html, 'agreement-ar-block'))) {
            return $html;
        }

        $family = $this->rtlDefaultFamily();
        $quoted = "'".$family."'";

        $html = preg_replace_callback(
            '/<(span|p|div|h[1-6]|li|td|th|blockquote)\b([^>]*\bclass=(["\'])[^"\']*\bagreement-ar(?:-block)?\b[^"\']*)([^>]*)>/i',
            static function (array $m) use ($quoted): string {
                $tag = $m[1];
                $attrs = $m[2].$m[4];
                if (preg_match('/\bstyle\s*=\s*(["\'])(.*?)/i', $attrs, $styleMatch)) {
                    $style = $styleMatch[2];
                    if (preg_match('/font-family\s*:\s*[^;]+/i', $style)) {
                        $style = preg_replace('/font-family\s*:\s*[^;]+/i', 'font-family: '.$quoted, $style) ?? $style;
                    } else {
                        $style = rtrim($style, '; ').'; font-family: '.$quoted;
                    }
                    $attrs = preg_replace(
                        '/\bstyle\s*=\s*(["\'])(.*?)/i',
                        'style='.$style.'',
                        $attrs,
                        1
                    ) ?? $attrs;
                } else {
                    $attrs .= ' style="font-family: '.$quoted.'"';
                }

                return '<'.$tag.$attrs.'>';
            },
            $html
        ) ?? $html;

        return $html;
    }

    public function sizePt(): float
    {
        return (float) config('agreement_fonts.size_pt', 11);
    }

    public function lineHeight(): float
    {
        return (float) config('agreement_fonts.line_height', 1.5);
    }

    public function color(): string
    {
        return (string) config('agreement_fonts.color', '#1e293b');
    }

    /**
     * @return array<string, float>
     */
    public function headingSizesPt(): array
    {
        return config('agreement_fonts.headings_pt', [
            'h1' => 16.0,
            'h2' => 14.0,
            'h3' => 12.0,
            'h4' => 11.0,
        ]);
    }

    /**
     * @return list<float>
     */
    public function allowedSizesPt(): array
    {
        return array_map('floatval', config('agreement_fonts.sizes_pt', [11]));
    }

    public function tinymceFamilyFormats(): string
    {
        $map = [
            'Calibri' => 'Calibri,sans-serif',
            'Cambria' => 'Cambria,serif',
            'Arial' => 'arial,helvetica,sans-serif',
            'Times New Roman' => 'times new roman,times,serif',
            'Georgia' => 'georgia,serif',
            'Verdana' => 'verdana,sans-serif',
            'Courier New' => 'courier new,courier,monospace',
            'Noto Naskh Arabic' => "'Noto Naskh Arabic',serif",
            'Amiri' => 'Amiri,serif',
            'Scheherazade New' => "'Scheherazade New',serif",
        ];

        $parts = [];
        foreach ($this->families() as $family) {
            if ($family === 'Segoe UI') {
                continue;
            }
            $css = $map[$family] ?? ("'" . $family . "',sans-serif");
            $parts[] = $family . '=' . $css;
        }

        return implode(';', $parts);
    }

    public function tinymceSizeFormats(): string
    {
        return implode(' ', array_map(
            fn (float $size): string => rtrim(rtrim(number_format($size, 1, '.', ''), '0'), '.') . 'pt',
            $this->allowedSizesPt()
        ));
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function ribbonFamilyOptions(): array
    {
        $map = [
            'Calibri' => 'Calibri,sans-serif',
            'Cambria' => 'Cambria,serif',
            'Arial' => 'Arial,Helvetica,sans-serif',
            'Times New Roman' => 'Times New Roman,Times,serif',
            'Georgia' => 'Georgia,serif',
            'Verdana' => 'Verdana,sans-serif',
            'Courier New' => 'Courier New,Courier,monospace',
            'Noto Naskh Arabic' => "'Noto Naskh Arabic',serif",
            'Amiri' => 'Amiri,serif',
            'Scheherazade New' => "'Scheherazade New',serif",
        ];

        $options = [];
        foreach ($this->families() as $family) {
            if ($family === 'Segoe UI' || ! isset($map[$family])) {
                continue;
            }
            $options[] = ['label' => $family, 'value' => $map[$family]];
        }

        return $options;
    }

    /**
     * Rewrite stored Word/TinyMCE font CSS onto the allowed agreement families and sizes.
     * Also strips Google/Docs paste wrappers that break Dompdf Arabic shaping.
     */
    public function normalizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        // Paste from Google Search/Docs injects host UI chrome into the body.
        $html = preg_replace('/<!--TgQPHd.*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/\sdata-(?:sfc-[a-z]+|hveid|complete|copy-service-[a-z-]+)="[^"]*"/i', '', $html) ?? $html;
        $html = preg_replace("/\sdata-(?:sfc-[a-z]+|hveid|complete|copy-service-[a-z-]+)='[^']*'/i", '', $html) ?? $html;

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-font-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_PARSEHUGE
        );

        $root = $dom->getElementById('agreement-font-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $this->normalizeElement($root);
        $this->unwrapPasteChrome($root);
        $this->ensureBlockWrappers($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
    }

    /**
     * Dompdf ignores text-align on anonymous text nodes; keep body text in blocks.
     */
    private function ensureBlockWrappers(DOMElement $root): void
    {
        $doc = $root->ownerDocument;
        if (! $doc) {
            return;
        }

        // Drop empty leftover wrappers (common after Google paste unwrap).
        foreach (iterator_to_array($root->childNodes) as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            if (in_array($tag, ['div', 'span'], true) && trim($child->textContent ?? '') === '') {
                $root->removeChild($child);
            }
        }

        $hasElement = false;
        foreach ($root->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $hasElement = true;
                break;
            }
        }

        if ($hasElement) {
            return;
        }

        $text = trim($root->textContent ?? '');
        if ($text === '') {
            return;
        }

        while ($root->firstChild) {
            $root->removeChild($root->firstChild);
        }

        $p = $doc->createElement('p');
        $p->appendChild($doc->createTextNode($text));
        $root->appendChild($p);
    }

    /**
     * Unwrap Google paste host divs and drop empty placeholder spans.
     */
    private function unwrapPasteChrome(DOMElement $root): void
    {
        $xpath = new \DOMXPath($root->ownerDocument);
        /** @var list<DOMElement> $nodes */
        $nodes = [];
        foreach ($xpath->query('.//*', $root) ?: [] as $node) {
            if ($node instanceof DOMElement) {
                $nodes[] = $node;
            }
        }

        // Deepest-first so nested wrappers unwrap cleanly.
        $nodes = array_reverse($nodes);

        foreach ($nodes as $element) {
            $tag = strtolower($element->tagName);
            $class = $element->getAttribute('class');

            if ($tag === 'span' && trim($element->textContent ?? '') === '' && ! $element->hasChildNodes()) {
                $element->parentNode?->removeChild($element);
                continue;
            }

            $isGoogleHost = $tag === 'div' && (
                str_contains($class, 'n6owBd')
                || str_contains($class, 'awi2gc')
                || $element->hasAttribute('data-copy-service-computed-style')
                || $element->hasAttribute('data-sfc-root')
            );

            if (! $isGoogleHost) {
                continue;
            }

            $parent = $element->parentNode;
            if (! $parent) {
                continue;
            }

            while ($element->firstChild) {
                $parent->insertBefore($element->firstChild, $element);
            }
            $parent->removeChild($element);
        }
    }

    private function normalizeElement(DOMElement $element): void
    {
        if ($element->hasAttribute('style')) {
            $element->setAttribute('style', $this->normalizeStyle($element->getAttribute('style')));
        }

        if ($element->hasAttribute('face')) {
            $element->setAttribute('face', $this->canonicalFamily($element->getAttribute('face')));
        }

        foreach (iterator_to_array($element->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $this->normalizeElement($child);
            }
        }
    }

    private function normalizeStyle(string $style): string
    {
        $style = preg_replace('/mso-[a-z0-9-]+\s*:\s*[^;]+;?/i', '', $style) ?? $style;

        $rules = [];
        foreach (explode(';', $style) as $rule) {
            if (! str_contains($rule, ':')) {
                continue;
            }
            [$property, $value] = array_map('trim', explode(':', $rule, 2));
            $propertyLower = strtolower($property);
            if ($propertyLower === '' || $value === '') {
                continue;
            }

            if ($propertyLower === 'font-family') {
                $rules[$propertyLower] = $this->canonicalFamily($value);
                continue;
            }

            if ($propertyLower === 'font-size') {
                $pt = $this->canonicalSizePt($value);
                if ($pt !== null) {
                    $rules[$propertyLower] = $this->formatPt($pt);
                }
                continue;
            }

            if (in_array($propertyLower, ['font', 'font-variant'], true)) {
                continue;
            }

            $rules[$propertyLower] = $value;
        }

        $parts = [];
        foreach ($rules as $property => $value) {
            $parts[] = $property . ': ' . $value;
        }

        return implode('; ', $parts);
    }

    public function canonicalFamily(string $stack): string
    {
        $aliases = [];
        foreach (config('agreement_fonts.aliases', []) as $alias => $canonical) {
            $aliases[strtolower((string) $alias)] = (string) $canonical;
        }
        foreach ($this->families() as $family) {
            $aliases[strtolower($family)] = $family;
        }

        foreach (preg_split('/\s*,\s*/', $stack) ?: [] as $part) {
            $name = strtolower(trim($part, " \t\"'"));
            $name = preg_replace('/\s+(light|lt|pro)$/i', '', $name) ?? $name;
            if ($name === '') {
                continue;
            }
            if (isset($aliases[$name])) {
                return $aliases[$name];
            }
            foreach ($this->families() as $family) {
                if (str_contains($name, strtolower($family))) {
                    return $family;
                }
            }
        }

        return $this->defaultFamily();
    }

    public function canonicalSizePt(string $value): ?float
    {
        $value = strtolower(trim($value));
        if (! preg_match('/^([\d.]+)\s*(pt|px|em|rem)?$/', $value, $match)) {
            return null;
        }

        $amount = (float) $match[1];
        $unit = $match[2] !== '' ? $match[2] : 'pt';
        $pt = match ($unit) {
            'px' => $amount * 72 / 96,
            'em', 'rem' => $amount * $this->sizePt(),
            default => $amount,
        };

        $allowed = $this->allowedSizesPt();
        if ($allowed === []) {
            return round($pt, 1);
        }

        $closest = $allowed[0];
        $best = abs($pt - $closest);
        foreach ($allowed as $size) {
            $delta = abs($pt - $size);
            if ($delta < $best) {
                $best = $delta;
                $closest = $size;
            }
        }

        return $closest;
    }

    /**
     * Copy system TTFs into storage/fonts and return faces for PDF + browser.
     *
     * @return list<array{family: string, weight: string, style: string, path: string, file: string, url: string}>
     */
    public function cachedFaces(): array
    {
        $dir = storage_path('fonts');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $faces = [];
        foreach ($this->systemFontCandidates() as $candidate) {
            $file = $this->cachedFileName($candidate['family'], $candidate['weight'], $candidate['style']);
            $dest = $dir . DIRECTORY_SEPARATOR . $file;
            $source = $this->firstReadableTtf($candidate['paths']);
            if ($source === null && ! is_readable($dest)) {
                continue;
            }

            if ($source !== null && (! is_file($dest) || filesize($dest) !== filesize($source))) {
                @copy($source, $dest);
            }

            $path = is_readable($dest) ? $dest : $source;
            if ($path === null || ! is_readable($path)) {
                continue;
            }

            $faces[] = [
                'family' => $candidate['family'],
                'weight' => $candidate['weight'],
                'style' => $candidate['style'],
                'path' => $path,
                'file' => $file,
                'url' => url('/agreement-fonts/' . $file),
            ];
        }

        return $faces;
    }

    public function cachedFileName(string $family, string $weight, string $style): string
    {
        $safeFamily = strtolower(preg_replace('/[^a-z0-9]+/i', '', $family) ?: 'font');

        return $safeFamily . '-' . $weight . '-' . $style . '.ttf';
    }

    public function isCachedFontFile(string $file): bool
    {
        $file = basename($file);

        return (bool) preg_match('/^[a-z0-9]+-(normal|bold)-(normal|italic)\.ttf$/', $file);
    }

    public function cachedFontPath(string $file): ?string
    {
        $file = basename($file);
        if (! $this->isCachedFontFile($file)) {
            return null;
        }

        $path = storage_path('fonts/' . $file);
        if (is_file($path)) {
            return $path;
        }

        foreach ($this->cachedFaces() as $face) {
            if ($face['file'] === $file && is_file($face['path'])) {
                return $face['path'];
            }
        }

        return null;
    }

    /**
     * @font-face CSS that loads the same TTF files the PDF uses.
     */
    public function browserFontFaceCss(): string
    {
        $css = '';
        foreach ($this->cachedFaces() as $face) {
            $css .= sprintf(
                "@font-face{font-family:'%s';font-weight:%s;font-style:%s;font-display:swap;src:url('%s') format('truetype');}",
                str_replace("'", '', $face['family']),
                $face['weight'],
                $face['style'],
                str_replace("'", '%27', $face['url'])
            );
        }

        return $css;
    }

    /**
     * System font files for every agreement family so print and PDF share the same faces.
     *
     * @return list<array{family: string, weight: string, style: string, paths: list<string>}>
     */
    public function systemFontCandidates(): array
    {
        $win = 'C:\\Windows\\Fonts\\';
        $bundle = $this->bundledFontDirectory();
        $dejavu = $this->dompdfFontDirectory();
        $faces = [];

        foreach ([
            'Calibri' => [
                'normal' => [$bundle . 'Carlito-Regular.ttf', $win . 'calibri.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-Regular.ttf', $dejavu . 'DejaVuSans.ttf'],
                'bold' => [$bundle . 'Carlito-Bold.ttf', $win . 'calibrib.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-Bold.ttf', $dejavu . 'DejaVuSans-Bold.ttf'],
                'italic' => [$bundle . 'Carlito-Italic.ttf', $win . 'calibrii.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-Italic.ttf', $dejavu . 'DejaVuSans-Oblique.ttf'],
                'bold_italic' => [$bundle . 'Carlito-BoldItalic.ttf', $win . 'calibriz.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-BoldItalic.ttf', $dejavu . 'DejaVuSans-BoldOblique.ttf'],
            ],
            'Segoe UI' => [
                'normal' => [$bundle . 'Carlito-Regular.ttf', $bundle . 'DejaVuSans.ttf', $win . 'segoeui.ttf', $dejavu . 'DejaVuSans.ttf'],
                'bold' => [$bundle . 'Carlito-Bold.ttf', $bundle . 'DejaVuSans-Bold.ttf', $win . 'segoeuib.ttf', $dejavu . 'DejaVuSans-Bold.ttf'],
                'italic' => [$bundle . 'Carlito-Italic.ttf', $bundle . 'DejaVuSans-Oblique.ttf', $win . 'segoeuii.ttf', $dejavu . 'DejaVuSans-Oblique.ttf'],
                'bold_italic' => [$bundle . 'Carlito-BoldItalic.ttf', $bundle . 'DejaVuSans-BoldOblique.ttf', $win . 'segoeuiz.ttf', $dejavu . 'DejaVuSans-BoldOblique.ttf'],
            ],
            'Arial' => [
                'normal' => [$bundle . 'DejaVuSans.ttf', $win . 'arial.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf', $dejavu . 'DejaVuSans.ttf'],
                'bold' => [$bundle . 'DejaVuSans-Bold.ttf', $win . 'arialbd.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf', $dejavu . 'DejaVuSans-Bold.ttf'],
                'italic' => [$bundle . 'DejaVuSans-Oblique.ttf', $win . 'ariali.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Italic.ttf', $dejavu . 'DejaVuSans-Oblique.ttf'],
                'bold_italic' => [$bundle . 'DejaVuSans-BoldOblique.ttf', $win . 'arialbi.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf', $dejavu . 'DejaVuSans-BoldOblique.ttf'],
            ],
            'Times New Roman' => [
                'normal' => [$bundle . 'Tinos-Regular.ttf', $win . 'times.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf', $dejavu . 'DejaVuSerif.ttf'],
                'bold' => [$bundle . 'Tinos-Bold.ttf', $win . 'timesbd.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf', $dejavu . 'DejaVuSerif-Bold.ttf'],
                'italic' => [$bundle . 'Tinos-Italic.ttf', $win . 'timesi.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-Italic.ttf', $dejavu . 'DejaVuSerif-Italic.ttf'],
                'bold_italic' => [$bundle . 'Tinos-BoldItalic.ttf', $win . 'timesbi.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-BoldItalic.ttf', $dejavu . 'DejaVuSerif-BoldItalic.ttf'],
            ],
            'Georgia' => [
                'normal' => [$bundle . 'Tinos-Regular.ttf', $bundle . 'DejaVuSerif.ttf', $win . 'georgia.ttf', $dejavu . 'DejaVuSerif.ttf'],
                'bold' => [$bundle . 'Tinos-Bold.ttf', $bundle . 'DejaVuSerif-Bold.ttf', $win . 'georgiab.ttf', $dejavu . 'DejaVuSerif-Bold.ttf'],
                'italic' => [$bundle . 'Tinos-Italic.ttf', $bundle . 'DejaVuSerif-Italic.ttf', $win . 'georgiai.ttf', $dejavu . 'DejaVuSerif-Italic.ttf'],
                'bold_italic' => [$bundle . 'Tinos-BoldItalic.ttf', $bundle . 'DejaVuSerif-BoldItalic.ttf', $win . 'georgiaz.ttf', $dejavu . 'DejaVuSerif-BoldItalic.ttf'],
            ],
            'Verdana' => [
                'normal' => [$bundle . 'DejaVuSans.ttf', $win . 'verdana.ttf', $dejavu . 'DejaVuSans.ttf'],
                'bold' => [$bundle . 'DejaVuSans-Bold.ttf', $win . 'verdanab.ttf', $dejavu . 'DejaVuSans-Bold.ttf'],
                'italic' => [$bundle . 'DejaVuSans-Oblique.ttf', $win . 'verdanai.ttf', $dejavu . 'DejaVuSans-Oblique.ttf'],
                'bold_italic' => [$bundle . 'DejaVuSans-BoldOblique.ttf', $win . 'verdanaz.ttf', $dejavu . 'DejaVuSans-BoldOblique.ttf'],
            ],
            'Courier New' => [
                'normal' => [$bundle . 'Cousine-Regular.ttf', $win . 'cour.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-Regular.ttf', $dejavu . 'DejaVuSansMono.ttf'],
                'bold' => [$bundle . 'Cousine-Bold.ttf', $win . 'courbd.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-Bold.ttf', $dejavu . 'DejaVuSansMono-Bold.ttf'],
                'italic' => [$bundle . 'Cousine-Italic.ttf', $win . 'couri.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-Italic.ttf', $dejavu . 'DejaVuSansMono-Oblique.ttf'],
                'bold_italic' => [$bundle . 'Cousine-BoldItalic.ttf', $win . 'courbi.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-BoldItalic.ttf', $dejavu . 'DejaVuSansMono-BoldOblique.ttf'],
            ],
            'Cambria' => [
                'normal' => [$bundle . 'Caladea-Regular.ttf', $win . 'cambria.ttf', $dejavu . 'DejaVuSerif.ttf'],
                'bold' => [$bundle . 'Caladea-Bold.ttf', $win . 'cambriab.ttf', $dejavu . 'DejaVuSerif-Bold.ttf'],
                'italic' => [$bundle . 'Caladea-Italic.ttf', $win . 'cambriai.ttf', $dejavu . 'DejaVuSerif-Italic.ttf'],
                'bold_italic' => [$bundle . 'Caladea-BoldItalic.ttf', $win . 'cambriaz.ttf', $dejavu . 'DejaVuSerif-BoldItalic.ttf'],
            ],
            'Noto Naskh Arabic' => [
                'normal' => [$bundle . 'NotoNaskhArabic-Regular.ttf'],
                'bold' => [$bundle . 'NotoNaskhArabic-Bold.ttf', $bundle . 'NotoNaskhArabic-Regular.ttf'],
                'italic' => [$bundle . 'NotoNaskhArabic-Regular.ttf'],
                'bold_italic' => [$bundle . 'NotoNaskhArabic-Bold.ttf', $bundle . 'NotoNaskhArabic-Regular.ttf'],
            ],
            'Amiri' => [
                'normal' => [$bundle . 'Amiri-Regular.ttf'],
                'bold' => [$bundle . 'Amiri-Bold.ttf', $bundle . 'Amiri-Regular.ttf'],
                'italic' => [$bundle . 'Amiri-Italic.ttf', $bundle . 'Amiri-Regular.ttf'],
                'bold_italic' => [$bundle . 'Amiri-BoldItalic.ttf', $bundle . 'Amiri-Bold.ttf', $bundle . 'Amiri-Regular.ttf'],
            ],
            'Scheherazade New' => [
                'normal' => [$bundle . 'ScheherazadeNew-Regular.ttf'],
                'bold' => [$bundle . 'ScheherazadeNew-Bold.ttf', $bundle . 'ScheherazadeNew-Regular.ttf'],
                'italic' => [$bundle . 'ScheherazadeNew-Regular.ttf'],
                'bold_italic' => [$bundle . 'ScheherazadeNew-Bold.ttf', $bundle . 'ScheherazadeNew-Regular.ttf'],
            ],
        ] as $family => $styles) {
            $variants = [
                'normal' => ['weight' => 'normal', 'style' => 'normal'],
                'bold' => ['weight' => 'bold', 'style' => 'normal'],
                'italic' => ['weight' => 'normal', 'style' => 'italic'],
                'bold_italic' => ['weight' => 'bold', 'style' => 'italic'],
            ];
            foreach ($variants as $key => $variant) {
                $faces[] = [
                    'family' => $family,
                    'weight' => $variant['weight'],
                    'style' => $variant['style'],
                    'paths' => $styles[$key] ?? [],
                ];
            }
        }

        return $faces;
    }

    /**
     * Metric-compatible TTFs shipped with the app (Carlito, Tinos, Cousine, Caladea, DejaVu).
     */
    public function bundledFontDirectory(): string
    {
        return resource_path('fonts/agreements/');
    }

    /**
     * DejaVu TTFs shipped with Dompdf — last-resort fallback if bundled files are missing.
     */
    public function dompdfFontDirectory(): string
    {
        return base_path('vendor/dompdf/dompdf/lib/fonts/');
    }

    /**
     * @param  list<string>  $paths
     */
    private function firstReadableTtf(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (is_readable($path) && str_ends_with(strtolower($path), '.ttf')) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Dompdf caches font metrics keyed by md5($ttfPath). When our cached TTF moves
     * or is replaced (e.g. Windows Calibri → bundled Carlito), stale .ufm files make
     * PDF text render as gibberish while HTML/print still look fine.
     *
     * @param  list<array{family: string, weight: string, style: string, path: string}>  $faces
     */
    public function refreshDompdfFontRegistry(array $faces): void
    {
        $dir = storage_path('fonts');
        $installedFile = $dir . DIRECTORY_SEPARATOR . 'installed-fonts.json';
        $installed = is_readable($installedFile)
            ? json_decode((string) file_get_contents($installedFile), true)
            : [];
        if (! is_array($installed)) {
            $installed = [];
        }

        $changed = false;

        foreach ($faces as $face) {
            $sourcePath = $face['path'];
            if (! is_readable($sourcePath)) {
                continue;
            }

            $familyKey = mb_strtolower($face['family']);
            $styleKey = $this->dompdfStyleKey($face['weight'], $face['style']);
            $expectedToken = $this->dompdfFontToken(
                $face['family'],
                $face['weight'],
                $face['style'],
                $sourcePath
            );

            $entry = $installed[$familyKey][$styleKey] ?? null;
            if ($entry === null) {
                continue;
            }

            $entryBase = $this->dompdfEntryBasename((string) $entry);
            if ($entryBase === $expectedToken && $this->dompdfFontMatchesSource($entryBase, $sourcePath)) {
                continue;
            }

            $this->deleteDompdfFontArtifacts($dir, $entryBase);
            unset($installed[$familyKey][$styleKey]);
            if (isset($installed[$familyKey]) && $installed[$familyKey] === []) {
                unset($installed[$familyKey]);
            }
            $changed = true;
        }

        if ($changed) {
            file_put_contents(
                $installedFile,
                json_encode($installed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
            );
        }
    }

    private function dompdfStyleKey(string $weight, string $style): string
    {
        $type = trim($weight . ' ' . $style);

        if (preg_match('/bold/i', $type)) {
            $weightKey = 'bold';
        } else {
            $weightKey = 'normal';
        }

        $italic = preg_match('/italic|oblique/i', $type) === 1;

        if ($weightKey === 'normal' && $italic) {
            return 'italic';
        }

        return $italic ? $weightKey . '_italic' : $weightKey;
    }

    private function dompdfFontToken(string $family, string $weight, string $style, string $sourcePath): string
    {
        $fontname = mb_strtolower($family);
        $styleString = $this->dompdfStyleKey($weight, $style);
        $prefix = $fontname . '_' . $styleString;
        $prefix = trim($prefix, '-');
        if (function_exists('iconv')) {
            $prefix = @iconv('utf-8', 'us-ascii//TRANSLIT', $prefix) ?: $prefix;
        }
        $prefixEncoding = mb_detect_encoding($prefix, mb_detect_order(), true);
        $substchar = mb_substitute_character();
        mb_substitute_character(0x005F);
        $prefix = mb_convert_encoding($prefix, 'ISO-8859-1', $prefixEncoding ?: 'UTF-8');
        mb_substitute_character($substchar);
        $prefix = preg_replace('[\W]', '_', $prefix) ?? $prefix;
        $prefix = preg_replace('/[^-_\w]+/', '', $prefix) ?? $prefix;

        return $prefix . '_' . md5($sourcePath);
    }

    private function dompdfEntryBasename(string $entry): string
    {
        return basename(str_replace('\\', '/', $entry));
    }

    private function dompdfFontMatchesSource(string $entryBase, string $sourcePath): bool
    {
        $cachedTtf = storage_path('fonts/' . $entryBase . '.ttf');

        return is_file($cachedTtf)
            && is_file($sourcePath)
            && md5_file($cachedTtf) === md5_file($sourcePath);
    }

    private function deleteDompdfFontArtifacts(string $dir, string $base): void
    {
        foreach (['.ttf', '.ufm', '.ufm.json'] as $ext) {
            $path = $dir . DIRECTORY_SEPARATOR . $base . $ext;
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function formatPt(float $pt): string
    {
        return rtrim(rtrim(number_format($pt, 1, '.', ''), '0'), '.') . 'pt';
    }
}
