<?php

$path = __DIR__ . '/../public/assets/js/company-details-settings.js';
$js = file_get_contents($path);

// Replace mojibake / fragile glyphs with JS unicode escapes (encoding-safe).
$map = [
    // Drag handle (vertical ellipsis) — the broken icon the user reported
    'â‹®â‹®' => '\\u22EE\\u22EE',
    // Common punctuation mojibake
    'Â·' => '\\u00B7',
    'â€“' => '\\u2013',
    'â€”' => '\\u2014',
    'â€¦' => '\\u2026',
    'â€º' => '\\u203A',
    'âœ“' => '\\u2713',
    'âœŽ' => '\\u270E',
    'Ã—' => '\\u00D7',
    // Paperclip emoji (often corrupted)
    'ðŸ“Ž' => '\\uD83D\\uDCCE',
];

foreach ($map as $bad => $good) {
    $js = str_replace($bad, $good, $js);
}

// Also catch grip if stored as literal weird bytes via regex on the grip span
$js = preg_replace(
    '/(<span class="grip"[^>]*>)(.*?)(<\/span>)/',
    '$1\\u22EE\\u22EE$3',
    $js,
    1
);

// Tip text handle reference
$js = preg_replace(
    '/Drag a field by its .*? handle/',
    'Drag a field by its \\u22EE\\u22EE handle',
    $js,
    1
);

// Language option labels — keep readable English fallbacks (avoid broken Arabic/Hindi bytes)
$js = str_replace(
    "['ar','Ø§Ù„Ø¹Ø±Ø¨ÙŠØ© — Arabic'],['ur','Ø§Ø±Ø¯Ùˆ — Urdu'],['hi','à¤¹à¤¿à¤¨à¥à¤¦à¥€ — Hindi']",
    "['ar','Arabic'],['ur','Urdu'],['hi','Hindi']",
    $js
);
$js = str_replace(
    "ph:'Ø§Ø³Ù… Ø§Ù„Ø´Ø±ÙƒØ© Ø¨Ø§Ù„Ø¹Ø±Ø¨ÙŠØ©'",
    "ph:'Arabic company name'",
    $js
);
$js = str_replace(
    "const L={en:'Save Settings',ar:'Ø­ÙØ¸ Ø§Ù„Ø¥Ø¹Ø¯Ø§Ø¯Ø§Øª',ur:'Ø³ÛŒÙ¹Ù†Ú¯Ø² Ù…Ø­ÙÙˆØ¸ Ú©Ø±ÛŒÚº',hi:'à¤¸à¥‡à¤Ÿà¤¿à¤‚à¤—à¥à¤¸ à¤¸à¤¹à¥‡à¤œà¥‡à¤‚'};",
    "const L={en:'Save Settings',ar:'Save Settings',ur:'Save Settings',hi:'Save Settings'};",
    $js
);
$js = str_replace(
    "vv.doc==='ar'?'ÙØ§ØªÙˆØ±Ø© Ø¶Ø±ÙŠØ¨ÙŠØ©':'TAX INVOICE / ÙØ§ØªÙˆØ±Ø© Ø¶Ø±ÙŠØ¨ÙŠØ©'",
    "vv.doc==='ar'?'TAX INVOICE':'TAX INVOICE / فاتورة ضريبية'",
    $js
);
$js = str_replace(
    ":'Ø¯.Ø¥'",
    ":'AED'",
    $js
);
$js = str_replace(
    'at least 64Ã—64',
    'at least 64x64',
    $js
);

// Delete button X if corrupted
$js = str_replace('>âœ•</button>', '>\\u2715</button>', $js);

file_put_contents($path, $js);

echo "Fixed encoding issues\n";
echo "Grip unicode escapes: " . (str_contains($js, '\\u22EE\\u22EE') ? 'yes' : 'NO') . "\n";
echo "Remaining mojibake grip: " . (str_contains($js, 'â‹®') ? 'YES' : 'no') . "\n";
passthru('node --check ' . escapeshellarg($path) . ' 2>&1', $code);
echo "Syntax exit: {$code}\n";
