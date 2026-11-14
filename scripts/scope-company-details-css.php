<?php

/**
 * Scope company-details CSS under .company-details-root so it cannot
 * break Settings panel .menu / .nav / .btn / body styles.
 */

$src = __DIR__ . '/../public/assets/css/company-details-settings.css';
$css = file_get_contents($src);

// Drop global html/body resets that fight the ERP shell.
$css = preg_replace('/html,body\{[^}]*\}/', '', $css);
$css = preg_replace('/body\{[^}]*\}/', '', $css);
$css = preg_replace('/\*\{box-sizing:border-box\}/', '', $css);
$css = preg_replace('/button,input,select\{[^}]*\}/', '', $css);

// Move :root tokens onto the component root.
$css = preg_replace('/:root\{/', '.company-details-root{', $css, 1);
$css = str_replace(
    '@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){',
    '@media (prefers-color-scheme:dark){.company-details-root:not([data-theme="light"]){',
    $css
);
$css = str_replace(':root[data-theme="dark"]{', '.company-details-root[data-theme="dark"]{', $css);

// Rename conflicting class names used by ERP layout.
$renames = [
    '.menu' => '.cd-switcher-menu',
    '.nav' => '.cd-sec-nav',
    '.btn' => '.cd-btn',
    '.card' => '.cd-card',
    '.wrap' => '.cd-wrap',
    '.top' => '.cd-top',
    '.modal' => '.cd-modal',
    '.toast' => '.cd-toast',
];

foreach ($renames as $from => $to) {
    // Replace selector occurrences carefully (class boundaries).
    $css = preg_replace('/(?<![a-zA-Z0-9_-])' . preg_quote($from, '/') . '(?![a-zA-Z0-9_-])/', $to, $css);
}

// Prefix remaining top-level selectors with .company-details-root,
// except ones already scoped or at-rules / keyframes.
$lines = preg_split("/\r\n|\n|\r/", $css);
$out = [];
$inAt = 0;
foreach ($lines as $line) {
    $trim = ltrim($line);
    if ($trim === '' || str_starts_with($trim, '/*') || str_starts_with($trim, '*') || str_starts_with($trim, '*/')) {
        $out[] = $line;
        continue;
    }
    if (preg_match('/^@(media|supports|keyframes|font-face|layer)/', $trim)) {
        $out[] = $line;
        if (! str_contains($trim, '{')) {
            // rare
        }
        if (substr_count($trim, '{') > substr_count($trim, '}')) {
            $inAt++;
        }
        continue;
    }
    if ($inAt > 0) {
        $open = substr_count($line, '{');
        $close = substr_count($line, '}');
        $inAt += $open - $close;
        // Inside @media: still scope selectors.
        if (preg_match('/^([^{}@]+)\{/', $trim, $m)) {
            $sel = trim($m[1]);
            if ($sel !== '' && ! str_starts_with($sel, '.company-details-root') && $sel !== 'from' && $sel !== 'to' && ! preg_match('/^\d+%/', $sel)) {
                $scoped = implode(', ', array_map(function ($s) {
                    $s = trim($s);
                    if ($s === '' || str_starts_with($s, '.company-details-root')) {
                        return $s;
                    }
                    if (str_starts_with($s, ':')) {
                        return '.company-details-root' . $s;
                    }
                    return '.company-details-root ' . $s;
                }, explode(',', $sel)));
                $line = preg_replace('/^[^{]+\{/', $scoped . '{', $line, 1);
            }
        }
        $out[] = $line;
        continue;
    }

    if (preg_match('/^([^{}@]+)\{/', $trim, $m)) {
        $sel = trim($m[1]);
        if ($sel !== '' && ! str_starts_with($sel, '.company-details-root')) {
            $scoped = implode(', ', array_map(function ($s) {
                $s = trim($s);
                if ($s === '' || str_starts_with($s, '.company-details-root')) {
                    return $s;
                }
                if (str_starts_with($s, ':')) {
                    return '.company-details-root' . $s;
                }
                return '.company-details-root ' . $s;
            }, explode(',', $sel)));
            $line = preg_replace('/^[^{]+\{/', $scoped . '{', $line, 1);
        }
    }
    $out[] = $line;
}

$css = implode("\n", $out);

// Extra professional polish + click safety.
$css .= <<<'CSS'

/* Professional Settings integration + click safety */
.company-details-root {
  font-family: var(--font);
  color: var(--fg);
  background: transparent;
}
.company-details-root .cd-wrap {
  max-width: 100%;
  margin: 0;
  padding: 0 0 28px;
}
.company-details-root .cd-top {
  box-shadow: var(--shadow);
}
.company-details-root .cd-sec-nav {
  box-shadow: 0 1px 2px rgba(18,26,51,.04);
}
.company-details-root .cd-btn {
  border: 0;
}
.company-details-root .cd-btn.ghost {
  border: 1px solid var(--line);
}
.company-details-root .cd-switcher-menu[hidden] {
  display: none !important;
}
.company-details-root button.nb,
.company-details-root button.sw,
.company-details-root button.cd-btn,
.company-details-root button.mi,
.company-details-root button.bc,
.company-details-root button.addbtn,
.company-details-root button.inh-t,
.company-details-root label.filebtn {
  pointer-events: auto !important;
  cursor: pointer;
}
.company-details-root .cd-modal {
  z-index: 1090;
}
.company-details-root .cd-toast {
  z-index: 1100;
}
/* Prevent ERP content backdrop from swallowing clicks on this page */
body.settings-panel-company-details .content-backdrop {
  display: none !important;
  pointer-events: none !important;
}
CSS;

file_put_contents($src, $css);
echo "Scoped CSS written (" . strlen($css) . " bytes)\n";
echo "Has .menu unscoped: " . (preg_match('/(?<!company-details-root )\.[ ]*menu\{/', $css) ? 'YES' : 'no') . "\n";
echo "Has .cd-switcher-menu: " . (str_contains($css, '.cd-switcher-menu') ? 'yes' : 'no') . "\n";
echo "Has .cd-sec-nav: " . (str_contains($css, '.cd-sec-nav') ? 'yes' : 'no') . "\n";
echo "Has .cd-btn: " . (str_contains($css, '.cd-btn') ? 'yes' : 'no') . "\n";
