<?php

$rawPath = __DIR__ . '/../public/assets/js/company-details-settings.raw.js';
$outPath = __DIR__ . '/../public/assets/js/company-details-settings.js';
$js = file_get_contents($rawPath);
if ($js === false) {
    fwrite(STDERR, "Cannot read raw JS\n");
    exit(1);
}

$js = str_replace(["â€”", "â€™", "â€œ", "â€\x9d"], ['—', "'", '"', '"'], $js);

$bootstrap = <<<'JS'
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

$js = preg_replace('/^\(function\(\)\{\s*"use strict";/', $bootstrap, $js, 1);

$js = preg_replace(
    '/const ents=\[\{id:\'DXB\'.*?MODULE\.forEach\(.*?\);/s',
    "let ents = (CFG.entities || []).map(mapEntity);\n" .
    "if(!ents.length){ ents = [{id:0,type:'ho',code:'HO',label:(CFG.company&&CFG.company.name)||'Head Office',place:'',vals:{...HO_VALS},inh:{},banks:[blankBank()],useHoBank:false,docs:mkDocs(DOCS_HO)}]; }\n",
    $js,
    1
);

$js = str_replace(
    "const order={};SECS.forEach(s=>{if(s.rows)order[s.k]=s.rows.map(r=>r.id);});",
    "const order={};SECS.forEach(s=>{if(s.rows)order[s.k]=s.rows.map(r=>r.id);});\n" .
    "if(CFG.field_orders && typeof CFG.field_orders==='object'){Object.keys(CFG.field_orders).forEach(k=>{if(Array.isArray(CFG.field_orders[k])&&CFG.field_orders[k].length)order[k]=CFG.field_orders[k].filter(id=>ROWS[id]);});}",
    $js
);

$js = preg_replace(
    '/const e=newEnt\(t,nm\.value\.trim\(\),em\.value,\(cd\.value\|\|\'NEW\'\)\.toUpperCase\(\),\{city:em\.value,country:\'United Arab Emirates\',tlauth:AUTH_BY\[em\.value\],name:t===\'co\'\?nm\.value\.trim\(\):\'\'\}\);ents\.push\(e\);switchTo\(e\);toast\(`\$\{e\.label\} created`\);/',
    "api(ROUTES.storeEntity,{method:'POST',body:JSON.stringify({type:t,name:nm.value.trim(),emirate:em.value,code:(cd.value||'NEW').toUpperCase(),country:'United Arab Emirates'})}).then(function(res){if(res.bootstrap&&res.bootstrap.entities){ents=res.bootstrap.entities.map(mapEntity);}var e=ents.find(function(x){return String(x.id)===String(res.entity_id);})||ents[ents.length-1];switchTo(e);toast(((e&&e.label)?e.label:'Record')+' created');}).catch(function(err){toast(err.message||'Create failed');});",
    $js,
    1
);

$saveReplacement = <<<'JS'
document.getElementById('save').onclick=async function(){
  if(!CAN_EDIT){toast('You do not have permission to save.');return;}
  const locked=[...dirty].filter(k=>SECS.find(s=>s.k===k)?.warn);
  const adminLocked=[...dirty].filter(k=>ADMIN_SECTIONS.includes(k));
  if(adminLocked.length && !IS_ADMIN){toast('Administrator access required for: '+adminLocked.map(k=>(SECS.find(s=>s.k===k)||{}).t).filter(Boolean).join(', '));return;}
  const payload={
    vals:cur.vals, inh:cur.inh, banks:cur.banks, use_ho_bank:!!cur.useHoBank,
    docs_meta:(cur.docs||[]).filter(d=>!d.key).map(d=>({n:d.n,no:d.no,exp:d.exp,file:d.file,key:d.key||''})),
    field_orders:order, dirty_sections:[...dirty], label:cur.label, place:cur.place, code:cur.code,
    theme:(window.__thmGet?window.__thmGet():(CFG.theme||null)),
    confirm_admin:false
  };
  const doSave=async function(confirm){
    try{
      payload.confirm_admin=!!confirm;
      const res=await api((ROUTES.saveEntity||'').replace('__ID__',cur.id),{method:'POST',body:JSON.stringify(payload)});
      if(res.entity&&res.entity.entities){const id=cur.id;ents=res.entity.entities.map(mapEntity);cur=ents.find(e=>String(e.id)===String(id))||ents[0];}
      dirty.clear();render();document.getElementById('saveNote').textContent='One button saves every section.';toast((cur.type==='ho'?'Settings':cur.label)+' saved');
    }catch(err){
      if(err.data&&err.data.needs_confirmation){
        const secs=(err.data.sections||locked).map(k=>(SECS.find(s=>s.k===k)||{}).t).filter(Boolean).join(', ');
        const m=modal(`<div class="card" role="dialog" aria-label="Confirm changes"><div class="hd"><div><h3>Confirm changes</h3><p>You changed ${secs}.</p></div></div><div class="bd"><p style="margin:0;font-size:13px;color:var(--muted)">These settings can affect existing vouchers, VAT returns and reports. The change will be recorded in Activity Logs.</p></div><div class="ft"><button type="button" class="btn ghost" data-close>Cancel</button><button type="button" class="btn" id="cfm">Save changes</button></div></div>`);
        m.querySelector('#cfm').onclick=function(){m.remove();doSave(true);};
        return;
      }
      toast(err.message||'Save failed');
    }
  };
  if(locked.length){
    const m=modal(`<div class="card" role="dialog" aria-label="Confirm changes"><div class="hd"><div><h3>Confirm changes</h3><p>You changed ${locked.map(k=>SECS.find(s=>s.k===k).t).join(', ')}.</p></div></div><div class="bd"><p style="margin:0;font-size:13px;color:var(--muted)">These settings can affect existing vouchers, VAT returns and reports. The change will be recorded in Activity Logs.</p></div><div class="ft"><button type="button" class="btn ghost" data-close>Cancel</button><button type="button" class="btn" id="cfm">Save changes</button></div></div>`);
    m.querySelector('#cfm').onclick=function(){m.remove();doSave(true);};
  } else doSave(false);
};
JS;

$js = preg_replace(
    '/document\.getElementById\(\'save\'\)\.onclick=\(\)=>\{.*?\n\};/s',
    $saveReplacement,
    $js,
    1
);

$js = str_replace(
    "if(t.dataset.up&&t.files[0]){const fid=t.dataset.up;const r=new FileReader();r.onload=()=>{setVal(fid,r.result);renderHead();renderPanel();};r.readAsDataURL(t.files[0]);}",
    "if(t.dataset.up&&t.files[0]){const fid=t.dataset.up;const fd=new FormData();fd.append('field',fid);fd.append('file',t.files[0]);api((ROUTES.uploadAsset||'').replace('__ID__',cur.id),{method:'POST',body:fd,headers:{}}).then(function(res){setVal(fid,res.url||res.path);renderHead();renderPanel();toast('Uploaded');}).catch(function(err){toast(err.message||'Upload failed');});}",
    $js
);

$js = preg_replace(
    '/if\(t\.dataset\.dfile&&t\.files\[0\]\)\{const d=cur\.docs\[\+t\.dataset\.dfile\];.*?toast\(`\$\{d\.n\|\|\'Document\'\} saved to the Documents module`\);\}/s',
    "if(t.dataset.dfile&&t.files[0]){const d=cur.docs[+t.dataset.dfile];const fd=new FormData();fd.append('file',t.files[0]);fd.append('name',d.n||'Document');if(d.key)fd.append('key',d.key);if(d.no)fd.append('number',d.no);if(d.exp)fd.append('expiry_date',d.exp);if(d.document_type_id)fd.append('document_type_id',d.document_type_id);api((ROUTES.uploadDocument||'').replace('__ID__',cur.id),{method:'POST',body:fd,headers:{}}).then(function(res){if(res.bootstrap&&res.bootstrap.entities){const id=cur.id;ents=res.bootstrap.entities.map(mapEntity);cur=ents.find(function(e){return String(e.id)===String(id);})||cur;}mark();renderPanel();toast((d.n||'Document')+' saved');}).catch(function(err){toast(err.message||'Upload failed');});}",
    $js,
    1
);

$js = str_replace(
    "if(s.warn)h+=`<div class=\"warn\">Changing this setting can affect existing vouchers, VAT returns and reports. You will be asked to confirm before saving.</div>`;",
    "if(s.warn)h+=`<div class=\"warn\">Changing this setting can affect existing vouchers, VAT returns and reports. You will be asked to confirm before saving.</div>`;\n  if(s.lock && !IS_ADMIN)h+=`<div class=\"warn\">Admin only — you can view these settings but cannot change them.</div>`;",
    $js
);

$js = str_replace(
    "order[curSec]=[...panel.querySelectorAll('.row')].map(r=>r.dataset.row);",
    "order[curSec]=[...panel.querySelectorAll('.row')].map(r=>r.dataset.row);mark();\n    if(ROUTES.fieldOrders){api(ROUTES.fieldOrders,{method:'POST',body:JSON.stringify({field_orders:order})}).catch(function(){});}",
    $js
);

$js = str_replace(
    "if(s.custom==='theme'){if(!window.__thmEl){window.__thmEl=document.createElement('div');window.mountTheme(window.__thmEl,()=>mark());}panel.appendChild(window.__thmEl);}",
    "if(s.custom==='theme'){if(!window.__thmEl){window.__thmEl=document.createElement('div');const th=window.mountTheme(window.__thmEl,()=>mark());window.__thmGet=function(){return th&&th.get?th.get():null;};}panel.appendChild(window.__thmEl);}",
    $js
);

$js = preg_replace(
    '/render\(\);\s*\}\)\(\);\s*$/',
    "if(!CAN_EDIT){const b=document.getElementById('save'); if(b){b.disabled=true;b.style.opacity=.55;b.style.cursor='not-allowed';}}\nrender();\n})();\n",
    $js,
    1
);

file_put_contents($outPath, $js);
echo "Wrote {$outPath} (" . strlen($js) . " bytes)\n";
foreach (['CompanyDetailsConfig', 'doSave', 'uploadDocument', 'mapEntity', 'let ents'] as $needle) {
    echo $needle . ': ' . (str_contains($js, $needle) ? 'yes' : 'NO') . "\n";
}
