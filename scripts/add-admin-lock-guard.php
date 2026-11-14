<?php

$path = __DIR__ . '/../public/assets/js/company-details-settings.js';
$js = file_get_contents($path);

if (str_contains($js, '#panel .row,#panel .bank')) {
    echo "Already present\n";
    exit(0);
}

$insert = "  if(s.lock && !IS_ADMIN){const st=document.createElement('style');st.textContent='#panel .row,#panel .bank,#panel .addbtn,#panel .tblw{opacity:.7;pointer-events:none}';panel.prepend(st);}\n";
$js = str_replace("if(s.custom==='theme')", $insert . "if(s.custom==='theme')", $js);
file_put_contents($path, $js);
echo "Added\n";
