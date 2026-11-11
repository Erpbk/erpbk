<?php

$path = __DIR__ . '/../public/assets/js/company-details-settings.js';
$js = file_get_contents($path);

// Class name renames in generated HTML / querySelector strings.
$replacements = [
    // Modal shell
    "className='modal'" => "className='cd-modal'",
    'className="modal"' => 'className="cd-modal"',
    "querySelector('.modal')" => "querySelector('.cd-modal')",
    // Toast
    "className='toast'" => "className='cd-toast'",
    'className="toast"' => 'className="cd-toast"',
    "querySelector('.toast')" => "querySelector('.cd-toast')",
    // Card inside modal
    'class="card wide"' => 'class="cd-card wide"',
    'class="card"' => 'class="cd-card"',
    "class=\"card wide\"" => "class=\"cd-card wide\"",
    // Buttons in generated markup
    'class="btn ghost"' => 'class="cd-btn ghost"',
    'class="btn"' => 'class="cd-btn"',
    // Lang preview uses btn style
    'class="btn" style="pointer-events:none' => 'class="cd-btn" style="pointer-events:none',
];

foreach ($replacements as $from => $to) {
    $js = str_replace($from, $to, $js);
}

// Also catch template-literal btn classes that use class="btn
$js = preg_replace('/class="btn(\s|")/', 'class="cd-btn$1', $js);
$js = preg_replace("/class='btn(\\s|')/", "class='cd-btn$1", $js);

// Harden init: wait for DOM, bind via root, stop overlay issues.
$bootGuard = <<<'JS'
(function(){
"use strict";
function bootCompanyDetails(){
if(!document.getElementById('companyDetailsRoot')) return;
if(window.__companyDetailsBooted) return;
window.__companyDetailsBooted = true;

JS;

if (! str_contains($js, '__companyDetailsBooted')) {
    $js = preg_replace('/^\(function\(\)\{\s*"use strict";/', $bootGuard, $js, 1);

    // Close bootCompanyDetails + DOM ready at end
    $js = preg_replace(
        '/\}\)\(\);\s*$/',
        "}\nif(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',bootCompanyDetails);}else{bootCompanyDetails();}\n})();\n",
        $js,
        1
    );
}

// Make section nav clicks more reliable + stopPropagation so layout handlers don't interfere.
$js = str_replace(
    "document.getElementById('nav').onclick=ev=>{const b=ev.target.closest('[data-sec]');if(!b)return;curSec=b.dataset.sec;renderNav();renderPanel();ibanCheckAll();};",
    "document.getElementById('nav').addEventListener('click',ev=>{const b=ev.target.closest('[data-sec]');if(!b)return;ev.preventDefault();ev.stopPropagation();curSec=b.dataset.sec;renderNav();renderPanel();ibanCheckAll();});",
    $js
);

// Switcher open/close more reliable
$js = str_replace(
    "swb.onclick=ev=>{ev.stopPropagation();menu.hidden?openMenu():closeMenu();};",
    "swb.addEventListener('click',ev=>{ev.preventDefault();ev.stopPropagation();menu.hidden?openMenu():closeMenu();});",
    $js
);
$js = str_replace(
    "menu.onclick=ev=>{ev.stopPropagation();const b=ev.target.closest('.mi');if(!b)return;if(b.dataset.ent)switchTo(ents[+b.dataset.ent]);else if(b.hasAttribute('data-all')){closeMenu();openAll();}else if(b.hasAttribute('data-create')){closeMenu();openCreate();}};",
    "menu.addEventListener('click',ev=>{ev.stopPropagation();const b=ev.target.closest('.mi');if(!b)return;if(b.dataset.ent)switchTo(ents[+b.dataset.ent]);else if(b.hasAttribute('data-all')){closeMenu();openAll();}else if(b.hasAttribute('data-create')){closeMenu();openCreate();}});",
    $js
);

file_put_contents($path, $js);
echo "JS updated\n";
foreach (['cd-modal', 'cd-btn', 'cd-toast', 'cd-card', '__companyDetailsBooted', "closest('[data-sec]')"] as $n) {
    echo $n . ': ' . (str_contains($js, $n) ? 'yes' : 'NO') . "\n";
}

// Syntax check hint
passthru('node --check ' . escapeshellarg($path) . ' 2>&1');
