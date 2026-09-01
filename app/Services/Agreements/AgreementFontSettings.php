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
        ];

        $parts = [];
        foreach ($this->families() as $family) {
            if ($family === 'Segoe UI') {
                continue;
            }
            $css = $map[$family] ?? ($family . ',sans-serif');
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
     */
    public function normalizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = true;
        @$dom->loadHTML(
            '<?xml encoding="UTF-8"><html><body><div id="agreement-font-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        $root = $dom->getElementById('agreement-font-root');
        if (! $root instanceof DOMElement) {
            return $html;
        }

        $this->normalizeElement($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return $out;
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
            foreach ($candidate['paths'] as $path) {
                if (! is_readable($path) || ! str_ends_with(strtolower($path), '.ttf')) {
                    continue;
                }

                $file = $this->cachedFileName($candidate['family'], $candidate['weight'], $candidate['style']);
                $dest = $dir . DIRECTORY_SEPARATOR . $file;
                if (! is_file($dest) || filesize($dest) !== filesize($path)) {
                    copy($path, $dest);
                }

                $faces[] = [
                    'family' => $candidate['family'],
                    'weight' => $candidate['weight'],
                    'style' => $candidate['style'],
                    'path' => $dest,
                    'file' => $file,
                    'url' => url('/agreement-fonts/' . $file),
                ];

                break;
            }
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

        return is_file($path) ? $path : null;
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
        $faces = [];

        foreach ([
            'Calibri' => [
                'normal' => [$win . 'calibri.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-Regular.ttf'],
                'bold' => [$win . 'calibrib.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-Bold.ttf'],
                'italic' => [$win . 'calibrii.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-Italic.ttf'],
                'bold_italic' => [$win . 'calibriz.ttf', '/usr/share/fonts/truetype/crosextra/Carlito-BoldItalic.ttf'],
            ],
            'Segoe UI' => [
                'normal' => [$win . 'segoeui.ttf'],
                'bold' => [$win . 'segoeuib.ttf'],
                'italic' => [$win . 'segoeuii.ttf'],
                'bold_italic' => [$win . 'segoeuiz.ttf'],
            ],
            'Arial' => [
                'normal' => [$win . 'arial.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf'],
                'bold' => [$win . 'arialbd.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf'],
                'italic' => [$win . 'ariali.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-Italic.ttf'],
                'bold_italic' => [$win . 'arialbi.ttf', '/usr/share/fonts/truetype/liberation/LiberationSans-BoldItalic.ttf'],
            ],
            'Times New Roman' => [
                'normal' => [$win . 'times.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-Regular.ttf'],
                'bold' => [$win . 'timesbd.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf'],
                'italic' => [$win . 'timesi.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-Italic.ttf'],
                'bold_italic' => [$win . 'timesbi.ttf', '/usr/share/fonts/truetype/liberation/LiberationSerif-BoldItalic.ttf'],
            ],
            'Georgia' => [
                'normal' => [$win . 'georgia.ttf'],
                'bold' => [$win . 'georgiab.ttf'],
                'italic' => [$win . 'georgiai.ttf'],
                'bold_italic' => [$win . 'georgiaz.ttf'],
            ],
            'Verdana' => [
                'normal' => [$win . 'verdana.ttf'],
                'bold' => [$win . 'verdanab.ttf'],
                'italic' => [$win . 'verdanai.ttf'],
                'bold_italic' => [$win . 'verdanaz.ttf'],
            ],
            'Courier New' => [
                'normal' => [$win . 'cour.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-Regular.ttf'],
                'bold' => [$win . 'courbd.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-Bold.ttf'],
                'italic' => [$win . 'couri.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-Italic.ttf'],
                'bold_italic' => [$win . 'courbi.ttf', '/usr/share/fonts/truetype/liberation/LiberationMono-BoldItalic.ttf'],
            ],
            'Cambria' => [
                'normal' => [$win . 'cambria.ttf', $win . 'cambria.ttc'],
                'bold' => [$win . 'cambriab.ttf'],
                'italic' => [$win . 'cambriai.ttf'],
                'bold_italic' => [$win . 'cambriaz.ttf'],
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

    private function formatPt(float $pt): string
    {
        return rtrim(rtrim(number_format($pt, 1, '.', ''), '0'), '.') . 'pt';
    }
}
