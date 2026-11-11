<?php

$path = __DIR__ . '/../public/assets/js/company-details-settings.js';
$js = file_get_contents($path);

if (str_contains($js, 'CompanyDetailsConfig')) {
    echo "Already bootstrapped\n";
    exit(0);
}

$boot = <<<'JS'
(function(){
"use strict";
const CFG = window.CompanyDetailsConfig || {};
const ROUTES = CFG.routes || {};
const CSRF = CFG.csrf || '';
const CAN_EDIT = !!CFG.can_edit;
const IS_ADMIN = !!CFG.is_admin;
const ADMIN_SECTIONS = CFG.admin_sections || ['cur','time','fy','legal','bank','stamp'];
async function api(url, opts){
  opts = opts || {};
  const headers = Object.assign({'X-CSRF-TOKEN': CSRF, 'Accept':'application/json'}, opts.headers || {});
  if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
  const res = await fetch(url, Object.assign({}, opts, {headers: headers, credentials:'same-origin'}));
  const data = await res.json().catch(function(){ return {}; });
  if (!res.ok) {
    const err = new Error(data.message || 'Request failed');
    err.status = res.status; err.data = data; throw err;
  }
  return data;
}
function mapEntity(e){
  return {
    id:e.id, type:e.type, code:e.code||'', label:e.label||'', place:e.place||'',
    vals:Object.assign({}, e.vals||{}), inh:Object.assign({}, e.inh||{}),
    banks:(e.banks && e.banks.length) ? e.banks : [], useHoBank:!!e.useHoBank,
    docs:Array.isArray(e.docs)?e.docs:[], branch_id:e.branch_id, parent_id:e.parent_id
  };
}

JS;

$replaced = preg_replace('/^\(function\(\)\{\R?"use strict";\R?/', $boot, $js, 1, $count);
if ($count < 1) {
    // Fallback: hard prefix after stripping first two lines
    $lines = preg_split("/\R/", $js);
    if (isset($lines[0], $lines[1]) && str_contains($lines[0], '(function') && str_contains($lines[1], 'use strict')) {
        array_shift($lines);
        array_shift($lines);
        $replaced = $boot . implode("\n", $lines);
        $count = 1;
    }
}

if ($count < 1) {
    fwrite(STDERR, "Failed to inject bootstrap\n");
    exit(1);
}

file_put_contents($path, $replaced);
echo "Bootstrapped OK (" . strlen($replaced) . " bytes)\n";
echo "Has CompanyDetailsConfig: " . (str_contains($replaced, 'CompanyDetailsConfig') ? 'yes' : 'no') . "\n";
echo "Preview:\n" . substr($replaced, 0, 280) . "\n";
