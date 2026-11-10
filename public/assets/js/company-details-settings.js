(function(){
"use strict";
function bootCompanyDetails(){
if(!document.getElementById('companyDetailsRoot')) return;
if(window.__companyDetailsBooted) return;
window.__companyDetailsBooted = true;

const CFG = window.CompanyDetailsConfig || {};
const ROUTES = CFG.routes || {};
const CSRF = CFG.csrf || '';
const CAN_EDIT = !!CFG.can_edit;
const IS_ADMIN = !!CFG.is_admin;
const ADMIN_SECTIONS = CFG.admin_sections || ['cur','time','fy','legal','stamp'];
function assetUrl(pathOrUrl){
  if(!pathOrUrl) return '';
  const s=String(pathOrUrl);
  if(/^(blob:|data:)/i.test(s)) return s;
  try{
    if(/^(https?:)?\/\//i.test(s) || s.charAt(0)==='/'){
      const u=new URL(s, window.location.origin);
      const idx=u.pathname.indexOf('/storage/');
      if(idx>=0){
        const prefix=(CFG.storage_base||'').replace(/\/?$/,'/');
        if(prefix && prefix.charAt(0)==='/'){
          return prefix + u.pathname.slice(idx+'/storage/'.length) + u.search;
        }
        return u.pathname.slice(idx) + u.search;
      }
      return u.pathname + u.search;
    }
  }catch(e){}
  const base=(CFG.storage_base||'/storage/').replace(/\/?$/,'/');
  return base + s.replace(/^\/+/,'');
}
function profileUrl(template){
  const id=cur&&cur.id;
  if(id==null||id===''||!/^\d+$/.test(String(id))){
    throw new Error('Profile not loaded. Refresh the page and try again.');
  }
  const url=String(template||'');
  if(url.indexOf('__ID__')!==-1) return url.split('__ID__').join(String(id));
  return url.replace(/(\/entities\/)[^/?#]+/,'$1'+String(id));
}
function swapUploadPreview(fid, localUrl, serverUrl){
  const next=assetUrl(serverUrl);
  if(!next || next===localUrl){
    setVal(fid, localUrl||next);renderHead();renderPanel();
    return;
  }
  const probe=new Image();
  let done=false;
  const finish=function(url){
    if(done)return; done=true;
    if(url!==localUrl && localUrl){try{URL.revokeObjectURL(localUrl);}catch(e){}}
    setVal(fid, url);renderHead();renderPanel();
  };
  probe.onload=function(){finish(next);};
  probe.onerror=function(){finish(localUrl||next);};
  probe.src=next;
  setTimeout(function(){if(!done)finish(localUrl||next);},2500);
}
async function api(url, opts){
  opts = opts || {};
  const headers = Object.assign({'X-CSRF-TOKEN': CSRF, 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}, opts.headers || {});
  if (!(opts.body instanceof FormData)) headers['Content-Type'] = 'application/json';
  const res = await fetch(url, Object.assign({}, opts, {headers: headers, credentials:'same-origin'}));
  const data = await res.json().catch(function(){ return {}; });
  if (!res.ok) {
    let msg = data.message || 'Request failed';
    if (data.errors) {
      const flat = Object.values(data.errors).flat().filter(Boolean);
      if (flat.length) msg = flat.join(' ');
    }
    const err = new Error(msg);
    err.status = res.status; err.data = data; throw err;
  }
  return data;
}
function mapEntity(e){
  return {
    id:e.id, type:e.type, code:e.code||'', label:e.label||'', place:e.place||'',
    vals:Object.assign({}, e.vals||{}), inh:Object.assign({}, e.inh||{}),
    banks:(e.banks && e.banks.length) ? e.banks.map(normalizeBank) : [], useHoBank:!!e.useHoBank,
    docs:Array.isArray(e.docs)?e.docs:[], branch_id:e.branch_id, parent_id:e.parent_id
  };
}
/* ---------- icons ---------- */
const P={
 receipt:'<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
 id:'<rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6.5 16c.6-1.4 1.5-2 2.5-2s1.9.6 2.5 2M14 10h4M14 13h3"/>',
 pin:'<path d="M12 21s-6-5.5-6-11a6 6 0 1 1 12 0c0 5.5-6 11-6 11z"/><circle cx="12" cy="10" r="2.2"/>',
 image:'<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.8"/><path d="m21 16-5-5-8 9"/>',
 coins:'<ellipse cx="9" cy="7" rx="6" ry="3"/><path d="M3 7v5c0 1.7 2.7 3 6 3s6-1.3 6-3V7"/><path d="M15 12.5c3 .3 6-1 6-2.5M15 16c3 .3 6-1 6-2.5V10"/>',
 clock:'<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
 cal:'<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
 globe:'<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3z"/>',
 scale:'<path d="M12 4v16M7 20h10M5 8h14"/><path d="m5 8-3 6a3 3 0 0 0 6 0zM19 8l-3 6a3 3 0 0 0 6 0z"/>',
 bank:'<path d="M3 10 12 4l9 6M5 10v8M9 10v8M15 10v8M19 10v8M3 20h18"/>',
 pen:'<path d="m4 20 4-1 11-11-3-3L5 16z"/><path d="M14 6l3 3"/>',
 folder:'<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
 building:'<rect x="5" y="3" width="14" height="18" rx="1"/><path d="M9 7h2M13 7h2M9 11h2M13 11h2M9 15h2M13 15h2"/>',
 grid:'<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
 plus:'<path d="M12 5v14M5 12h14"/>',
 palette:'<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.6-.9 1.2-1.8-.5-1-.1-2.2 1.1-2.2H17a4 4 0 0 0 4-4c0-5.5-4-10-9-10z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10" cy="7" r="1.2"/><circle cx="15" cy="7.5" r="1.2"/>',
 cash:'<rect x="3" y="7" width="18" height="10" rx="1"/><path d="M7 12h4M15 12h2"/>',
 inv:'<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5M10 13h6M10 17h4"/>',
 user:'<circle cx="12" cy="8" r="3.5"/><path d="M5 20c1.3-3.5 4-5 7-5s5.7 1.5 7 5"/>',
 agr:'<path d="M7 3h7l5 5v13H7z"/><path d="M14 3v5h5"/><path d="M9 17c1-1.5 2-1.5 3 0s2 1.5 3 0"/>',
 mail:'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>'
};
const ico=n=>`<svg class="ico" viewBox="0 0 24 24" aria-hidden="true">${P[n]}</svg>`;
const esc=s=>String(s??'').replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

/* ---------- options ---------- */
const EMIRATES=['Dubai','Abu Dhabi','Sharjah','Ajman','Umm Al Quwain','Ras Al Khaimah','Fujairah'];
const CODES={'Dubai':'DXB','Abu Dhabi':'AUH','Sharjah':'SHJ','Ajman':'AJM','Umm Al Quwain':'UAQ','Ras Al Khaimah':'RAK','Fujairah':'FUJ'};
const AUTH=['Dubai Economy & Tourism (DET)','Abu Dhabi (ADDED)','Sharjah (SEDD)','Ajman (DED)','Umm Al Quwain (DED)','Ras Al Khaimah (RAKDED)','Fujairah (DED)','Free Zone'];
const AUTH_BY={'Dubai':AUTH[0],'Abu Dhabi':AUTH[1],'Sharjah':AUTH[2],'Ajman':AUTH[3],'Umm Al Quwain':AUTH[4],'Ras Al Khaimah':AUTH[5],'Fujairah':AUTH[6]};
const MONTHS=['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS=['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
const CUR=['AED — UAE Dirham','USD — US Dollar','EUR — Euro','GBP — British Pound','SAR — Saudi Riyal','QAR — Qatari Riyal','OMR — Omani Rial','KWD — Kuwaiti Dinar','BHD — Bahraini Dinar','PKR — Pakistani Rupee','INR — Indian Rupee','RSD — Serbian Dinar'];
const TZ=[['Asia/Dubai','(UTC+04:00) Dubai, Abu Dhabi'],['Asia/Muscat','(UTC+04:00) Muscat'],['Asia/Riyadh','(UTC+03:00) Riyadh'],['Asia/Qatar','(UTC+03:00) Doha'],['Asia/Karachi','(UTC+05:00) Karachi'],['Asia/Kolkata','(UTC+05:30) India'],['Europe/Belgrade','(UTC+01:00) Belgrade'],['UTC','UTC']];
const DATEF=['DD-MM-YYYY','DD/MM/YYYY','DD.MM.YYYY','MM-DD-YYYY','MM/DD/YYYY','YYYY-MM-DD','DD MMM YYYY','DD-MMM-YY','dddd, DD MMMM YYYY'];
const BANKS=['Emirates NBD','ADCB','First Abu Dhabi Bank (FAB)','Dubai Islamic Bank','Mashreq','RAKBANK','Commercial Bank of Dubai','Abu Dhabi Islamic Bank','Wio Bank','Other'];
const o=a=>a.map(x=>Array.isArray(x)?x:[x,x]);

/* ---------- schema ---------- */
/* row: id, label, help, inh (shared with Head Office by default), fields[], pv(previewFn), noInh */
const SECTIONS=[
 {grp:'Organization'},
 {k:'branches',icon:'building',t:'Branches',s:'Locations linked to this company',custom:'branches'},
 {k:'depts',icon:'grid',t:'Departments',s:'Teams and departments',custom:'depts'},
 {grp:'Company'},
 {k:'tax',icon:'receipt',t:'Tax & VAT',s:'FTA registration',rows:[
  {id:'trn',label:'TRN / VAT number',help:'15-digit Tax Registration Number',inh:1,f:[{id:'trn',type:'text',mono:1,max:15,ph:'100XXXXXXXXXXXX'}]},
  {id:'vatp',label:'VAT percentage',help:'Standard rate in the UAE',inh:1,f:[{id:'vatp',type:'text',suf:'%'}]}]},
 {k:'gen',icon:'id',t:'General',s:'Name, email, phone',rows:[
  {id:'name',label:'Company name',help:'Shown on invoices, vouchers and PDFs',inh:1,f:[{id:'name',type:'text'}]},
  {id:'phone',label:'Phone',help:'Landline or mobile, with country code',f:[{id:'phone',type:'text',ph:'+971 4 XXX XXXX'}]},
  {id:'email',label:'Email',help:'Official email for notifications',f:[{id:'email',type:'text',ph:'name@company.com'}]},
  {id:'wa',label:'WhatsApp',help:'Number customers can message',f:[{id:'wa',type:'text',pre:'WhatsApp',ph:'+971 5X XXX XXXX'}]},
  {id:'phone2',label:'Secondary phone',help:'Alternate landline or mobile',f:[{id:'phone2',type:'text',ph:'+971 X XXX XXXX'}]}]},
 {k:'adr',icon:'pin',t:'Address',s:'Location and website',rows:[
  {id:'address',label:'Address',help:'As per trade license',f:[{id:'address',type:'text'}]},
  {id:'city',label:'City / Emirate',help:'',f:[{id:'city',type:'select',opts:o(['',...EMIRATES,'Belgrade','Lahore','Karachi'])}]},
  {id:'country',label:'Country',help:'',inh:1,f:[{id:'country',type:'select',opts:o(['United Arab Emirates','Pakistan','Serbia','Oman','Saudi Arabia'])}]},
  {id:'web',label:'Website',help:'Shown on invoices and emails',inh:1,f:[{id:'web',type:'text',pre:'https://'}]}]},
 {k:'brd',icon:'image',t:'Branding',s:'Logo',rows:[
  {id:'logo',label:'Company logo',help:'PNG or SVG, transparent background. Used in PDFs and emails.',inh:1,f:[{id:'logo',type:'upload',box:'logo'}]}]},
 {grp:'Regional & Accounting'},
 {k:'cur',icon:'coins',t:'Currency',s:'Base currency',lock:1,warn:1,rows:[
  {id:'cur',label:'Base currency',help:'Accounts and reports use this currency',inh:1,f:[{id:'cur',type:'select',opts:o(CUR)}],pv:v=>['Preview',amount(v)]},
  {id:'curic',label:'Currency icon',help:'Default symbol or your own icon, such as the new Dirham sign',inh:1,f:[{id:'curic',type:'curicon'}]}]},
 {k:'time',icon:'clock',t:'Date & Time',s:'Date format, time zone, week',lock:1,warn:1,rows:[
  {id:'datef',label:'Date format',help:'Used in forms, tables, invoices and reports',inh:1,f:[{id:'datef',type:'select',opts:DATEF.map(f=>[f,`${f}  (${fmtDate(new Date(2026,9,27),f)})`])}],pv:v=>['Today',fmtDate(now(v),v.datef)]},
  {id:'timef',label:'Time format',help:'12-hour or 24-hour clock',inh:1,f:[{id:'timef',type:'select',opts:[['12','12-hour  (06:30 PM)'],['24','24-hour  (18:30)']]},{id:'secs',type:'switch',text:'Show seconds'}],pv:v=>['Now',fmtTime(now(v),v)]},
  {id:'hijri',label:'Hijri date',help:'Also show the Islamic (Hijri) date',inh:1,f:[{id:'hijri',type:'select',opts:[['off','Do not show'],['docs','On invoices and PDFs only'],['all','Everywhere']]}],pv:v=>v.hijri==='off'?null:['Hijri',hijri(now(v))]},
  {id:'tz',label:'Time zone',help:'Used for attendance, activity logs and notifications',inh:1,f:[{id:'tz',type:'select',opts:TZ}]},
  {id:'usertz',label:'User time zone',help:'Let each user choose their own time zone',inh:1,f:[{id:'usertz',type:'switch',text:'Allow'}]},
  {id:'week',label:'Work week',help:'Rider attendance and payroll follow this',inh:1,grid:1,f:[{id:'wstart',type:'select',mini:'Week starts',opts:[['1','Monday'],['0','Sunday'],['6','Saturday']]},{id:'woff',type:'select',mini:'Off day',opts:[['5','Friday'],['0','Sunday'],['56','Friday + Saturday'],['60','Saturday + Sunday'],['-','No off day']]}],pv:v=>['This week',weekRange(now(v),+v.wstart)]}]},
 {k:'fy',icon:'cal',t:'Financial Year',s:'FY and VAT period',lock:1,warn:1,rows:[
  {id:'fy',label:'Financial year',help:'Month in which the financial year starts',inh:1,f:[{id:'fy',type:'select',opts:MONTHS.map((m,i)=>[String(i),m])}],pv:v=>['Current FY',fyRange(now(v),+v.fy)]},
  {id:'vatper',label:'VAT return period',help:'As per FTA',inh:1,f:[{id:'vatper',type:'select',opts:[['q','Quarterly (every 3 months)'],['m','Monthly']]}]},
  {id:'vatq',label:'First VAT quarter',help:'As per the FTA-assigned stagger',inh:1,f:[{id:'vatq',type:'select',opts:MONTHS.map((m,i)=>[String(i),m])}]}]},
 {k:'lang',icon:'globe',t:'Language',s:'Interface and documents',rows:[
  {id:'ui',label:'Interface language',help:'Language of menus, buttons and labels',inh:1,f:[{id:'ui',type:'select',opts:[['en','English'],['ar','Arabic'],['ur','Urdu'],['hi','Hindi']]}]},
  {id:'dir',label:'Layout direction',help:'Right-to-left is used for Arabic and Urdu',inh:1,f:[{id:'dir',type:'select',opts:[['auto','Auto (based on language)'],['ltr','Left-to-right'],['rtl','Right-to-left']]}]},
  {id:'doc',label:'Document language',help:'Language of invoices, vouchers, receipts and PDFs',inh:1,f:[{id:'doc',type:'select',opts:[['en','English'],['ar','Arabic'],['bi','English + Arabic (bilingual)']]}]},
  {id:'arname',label:'Company name in Arabic',help:'Use the Arabic name from your trade license',inh:1,f:[{id:'arname',type:'text',dir:'rtl',ph:'Arabic company name'}]},
  {id:'mail',label:'Email language',help:'Language of system emails and notifications',inh:1,f:[{id:'mail',type:'select',opts:[['same','Same as interface'],['en','English'],['ar','Arabic']]}]},
  {id:'userlang',label:'User language',help:'Allow each user to choose their own language',inh:1,f:[{id:'userlang',type:'switch',text:'Allow'}]},
  {id:'lpv',label:'Preview',help:'How a button and a document title will look',noInh:1,f:[{id:'lpv',type:'langpv'}]}]},
 {grp:'Legal & Documents'},
 {k:'legal',icon:'scale',t:'Legal & Licenses',s:'Trade license, MOHRE, immigration, tax',lock:1,rows:[
  {id:'tl',label:'Trade license',help:'License number and issuing authority',grid:1,f:[{id:'tlno',type:'text',mini:'License number',mono:1},{id:'tlauth',type:'select',mini:'Issued by',opts:o(AUTH)},{id:'tlissue',type:'date',mini:'Issue date'},{id:'tlexp',type:'date',mini:'Expiry date',exp:1}]},
  {id:'form',label:'Legal form',help:'Type of company as per license',inh:1,grid:1,f:[{id:'ltype',type:'select',mini:'Company type',opts:o(['Limited Liability Company (LLC)','Sole Establishment','Civil Company','Free Zone Company (FZE / FZCO)','Branch of foreign company'])},{id:'lest',type:'date',mini:'Establishment date'}]},
  {id:'mohre',label:'MOHRE establishment',help:'Ministry of Human Resources. Needed for labour cards and work permits.',grid:1,f:[{id:'mono',type:'text',mini:'Establishment number',mono:1},{id:'moexp',type:'date',mini:'Expiry date',exp:1}]},
  {id:'imm',label:'Immigration establishment card',help:'GDRFA / ICP. Needed for employee and rider visas.',grid:1,f:[{id:'imno',type:'text',mini:'Card number',mono:1},{id:'imexp',type:'date',mini:'Expiry date',exp:1}]},
  {id:'cttrn',label:'Corporate Tax TRN',help:'Separate from the VAT TRN',inh:1,f:[{id:'cttrn',type:'text',mono:1,max:15,ph:'15-digit Corporate Tax TRN'}]},
  {id:'pobox',label:'PO Box',help:'Shown on official letters',f:[{id:'pobox',type:'text',pre:'P.O. Box'}]},
  {id:'rem',label:'Expiry reminder',help:'Alert before any license or card expires',inh:1,grid:1,f:[{id:'remd',type:'select',mini:'When',opts:o(['30 days before','15 days before','60 days before','90 days before'])},{id:'remto',type:'select',mini:'Notify',opts:o(['Admins','Admins + Accounts','All managers'])}]}]},
 {k:'stamp',icon:'pen',t:'Stamp & Signature',s:'Per-user stamps, signatures and document permissions',lock:1,rows:[
  {id:'stampimg',label:'Stamp',help:'This user\u2019s personal stamp / official seal',noInh:1,f:[{id:'stampimg',type:'upload'}]},
  {id:'signimg',label:'Signature',help:'This user\u2019s personal signature',noInh:1,f:[{id:'signimg',type:'upload'}]},
  {id:'signer',label:'Authorized signatory',help:'Printed below the signature for this user',noInh:1,grid:1,f:[{id:'signame',type:'text',mini:'Name'},{id:'sigdes',type:'text',mini:'Designation',ph:'Manager / Partner'}]},
  {id:'apply',label:'Apply automatically',help:'Admin assigns which modules this user may stamp and sign (vouchers, invoices, agreements)',noInh:1,wide:1,f:[{id:'apm',type:'docmatrix'}]},
  {id:'apother',label:'Other documents',help:'Documents not tied to one module that this user may stamp and sign',noInh:1,f:[{id:'ap4',type:'tile',icon:'user',text:'Salary slips'},{id:'ap5',type:'tile',icon:'mail',text:'Official letters'},{id:'ap7',type:'tile',icon:'receipt',text:'Quotations'},{id:'ap8',type:'tile',icon:'inv',text:'Statements of account'}]}]},
 {k:'theme',icon:'palette',t:'Theme & Colors',s:'Mode, colors, ready-made themes',custom:'theme'},
 {k:'docs',icon:'folder',t:'Company Documents',s:'Copies with expiry tracking',custom:'docs'}
];
const SECS=SECTIONS.filter(s=>s.k);
const ROWS={};SECS.forEach(s=>(s.rows||[]).forEach(r=>{ROWS[r.id]=r;r.sec=s.k;}));

/* ---------- data (branches as listed in the Branches module) ---------- */
const MODGROUPS=[
 ['Finance',['Cash & Banks','Accounts','Vouchers','Expenses','VAT']],
 ['People',['Employees','Riders','Recruiters','Customers']],
 ['Fleet',['Bikes','Vehicle on rent','Fuel Cards','Sims','RTA Fines','RTA Saliks','Garages','Leasing Companies']],
 ['Legal & Admin',['Agreements','Legal Case','Visa Expense','License Expense','Passport Handover','Documents']],
 ['Purchasing',['Supplier','Items','Assets']]];
const MCOLS=[['v','Vouchers'],['i','Invoices'],['a','Agreements']];
function defaultMatrix(){const on={v:['Cash & Banks','Accounts','Vouchers','Expenses','Supplier','Garages','Leasing Companies'],i:['Customers','Accounts','VAT','Vehicle on rent'],a:['Agreements','Riders','Employees','Leasing Companies','Vehicle on rent','Customers','Supplier']};const m={};MODGROUPS.forEach(([,mods])=>mods.forEach(x=>m[x]={v:on.v.includes(x),i:on.i.includes(x),a:on.a.includes(x)}));return m;}
const HO_VALS={trn:'100539270700003',vatp:'5',name:'Express Fast Delivery Service L.L.C',phone:'+97142984377',email:'efdservice.com@gmail.com',wa:'',phone2:'',
 address:'Warehouse No. 2, Umm Ramool',city:'Dubai',country:'United Arab Emirates',web:'www.efdservice.com',logo:'',cur:CUR[0],curmode:'d',curimg:'',
 datef:'DD-MM-YYYY',timef:'12',secs:false,hijri:'off',tz:'Asia/Dubai',usertz:false,wstart:'1',woff:'5',fy:'0',vatper:'q',vatq:'0',
 ui:'en',dir:'auto',doc:'en',arname:'',mail:'same',userlang:true,
 tlno:'1016724',tlauth:AUTH[0],tlissue:'',tlexp:'',ltype:'Limited Liability Company (LLC)',lest:'',mono:'',moexp:'',imno:'',imexp:'',cttrn:'',pobox:'',remd:'30 days before',remto:'Admins',
 stampimg:'',signimg:'',signame:'',sigdes:'',ap4:false,ap5:false,ap7:false,ap8:true,apm:defaultMatrix()};
const MODULE=[
 ['AUH','Express Fast','','Abu Dhabi','Abu Dhabi'],
 ['ES','Express Fast (Serbia)','','','Serbia'],
 ['PK','Pak Branch','xxxx','','Pakistan'],
 ['RAK','Express Fast','','Ras Al Khaimah','Ras Al Khaimah'],
 ['SHJ','Express Fast','0552043126','Sharjah','Sharjah']];
const DOC_KEYS={tl:['tlno','tlexp'],mohre:['mono','moexp'],imm:['imno','imexp']};
const DOCS_HO=[['Trade License','tl'],['MOHRE Establishment','mohre'],['Immigration Establishment Card','imm'],['VAT Certificate'],['Corporate Tax Certificate'],['Tenancy Contract (Ejari)'],['Chamber of Commerce'],['Company Insurance']];
const DOCS_BR=[['Trade License','tl'],['MOHRE Establishment','mohre'],['Immigration Establishment Card','imm'],['Tenancy Contract (Ejari)']];
const mkDocs=a=>a.map(([n,key])=>({n,key:key||'',no:'',exp:'',file:'',up:''}));
/* Bank matrix helpers must be defined BEFORE ents/mapEntity runs (BCOLS is const — TDZ). */
const BCOLS=[['invoice','Invoices'],['payment','Payment'],['customer_note','Customer note']];
function blankBankCell(){return{invoice:false,payment:false,customer_note:false};}
function defaultBankMatrix(seed){
  const on={
    invoice:['Customers','Accounts','VAT','Vehicle on rent'],
    payment:['Cash & Banks','Accounts','Vouchers','Expenses','Supplier','Garages','Leasing Companies','Customers','Riders','Employees'],
    customer_note:['Customers'],
  };
  if(seed&&typeof seed==='object'&&!Array.isArray(seed)){
    // seed = {invoice:true,payment:false,...} legacy flat modules → apply defaults only for enabled kinds
    const use={
      invoice:seed.invoice!==false,
      payment:!!seed.payment,
      customer_note:!!seed.customer_note,
    };
    const m={};
    MODGROUPS.forEach(([,mods])=>mods.forEach(x=>{
      m[x]={
        invoice:use.invoice&&on.invoice.includes(x),
        payment:use.payment&&on.payment.includes(x),
        customer_note:use.customer_note&&on.customer_note.includes(x),
      };
    }));
    return m;
  }
  const m={};
  MODGROUPS.forEach(([,mods])=>mods.forEach(x=>{
    m[x]={invoice:on.invoice.includes(x),payment:on.payment.includes(x),customer_note:on.customer_note.includes(x)};
  }));
  return m;
}
function bankModsFromMatrix(matrix){
  const mods={invoice:false,payment:false,customer_note:false};
  Object.values(matrix||{}).forEach(row=>{
    BCOLS.forEach(([c])=>{if(row&&row[c])mods[c]=true;});
  });
  return mods;
}
function normalizeBankMatrix(src){
  if(src&&typeof src==='object'&&!Array.isArray(src)&&Object.keys(src).some(k=>src[k]&&typeof src[k]==='object'&&('invoice' in src[k]||'payment' in src[k]||'customer_note' in src[k]))){
    const base=defaultBankMatrix({invoice:false,payment:false,customer_note:false});
    Object.keys(base).forEach(mod=>{
      const row=src[mod]&&typeof src[mod]==='object'?src[mod]:{};
      base[mod]={
        invoice:!!row.invoice,
        payment:!!row.payment,
        customer_note:!!row.customer_note,
      };
    });
    return base;
  }
  if(src&&typeof src==='object'&&(src.invoice!==undefined||src.payment!==undefined||src.customer_note!==undefined||src.show!==undefined)){
    return defaultBankMatrix({
      invoice:src.modules?!!src.modules.invoice:(src.show!==undefined?!!src.show:true),
      payment:src.modules?!!src.modules.payment:false,
      customer_note:src.modules?!!src.modules.customer_note:false,
    });
  }
  return defaultBankMatrix();
}
function normalizeBank(b){
  const src=b&&typeof b==='object'?b:{};
  let matrix;
  if(src.matrix&&typeof src.matrix==='object'){
    matrix=normalizeBankMatrix(src.matrix);
  }else if(src.modules&&typeof src.modules==='object'&&(src.modules.invoice!==undefined||src.modules.payment!==undefined||src.modules.customer_note!==undefined)&&!Object.values(src.modules).some(v=>v&&typeof v==='object')){
    // Flat legacy modules toggles
    matrix=defaultBankMatrix(src.modules);
  }else if(src.show!==undefined && !src.matrix){
    matrix=defaultBankMatrix({invoice:!!src.show,payment:false,customer_note:false});
  }else{
    matrix=normalizeBankMatrix(src.matrix||src.modules);
  }
  const mods=bankModsFromMatrix(matrix);
  return{
    bank:src.bank||BANKS[0], title:src.title||'', iban:src.iban||'', swift:src.swift||'',
    acc:src.acc||'', cur:src.cur||'AED', matrix, modules:mods, show:!!mods.invoice,
  };
}
function blankBank(){return normalizeBank({bank:BANKS[0],matrix:defaultBankMatrix()});}
let ents = (CFG.entities || []).map(mapEntity);
if(!ents.length){ ents = [{id:0,type:'ho',code:'HO',label:(CFG.company&&CFG.company.name)||'Head Office',place:'',vals:{...HO_VALS},inh:{},banks:[blankBank()],useHoBank:false,docs:mkDocs(DOCS_HO)}]; }

function newEnt(type,label,place,code,extra){
  const vals={};Object.keys(HO_VALS).forEach(k=>vals[k]=typeof HO_VALS[k]==='boolean'?false:typeof HO_VALS[k]==='object'?defaultMatrix():'');
  Object.assign(vals,{vatp:'5',cur:CUR[0],curmode:'d',datef:'DD-MM-YYYY',timef:'12',hijri:'off',tz:'Asia/Dubai',wstart:'1',woff:'5',fy:'0',vatper:'q',vatq:'0',ui:'en',dir:'auto',doc:'en',mail:'same',remd:'30 days before',remto:'Admins',ltype:'Limited Liability Company (LLC)'},extra||{});
  return{id:code+'-'+Math.random().toString(36).slice(2,6),type,code,label,place,vals,inh:{},banks:[blankBank()],useHoBank:type==='br',docs:mkDocs(type==='br'?DOCS_BR:DOCS_HO)};
}
let cur=ents[0],curSec='gen';
const order={};SECS.forEach(s=>{if(s.rows)order[s.k]=s.rows.map(r=>r.id);});
if(CFG.field_orders && typeof CFG.field_orders==='object'){Object.keys(CFG.field_orders).forEach(k=>{if(Array.isArray(CFG.field_orders[k])&&CFG.field_orders[k].length)order[k]=CFG.field_orders[k].filter(id=>ROWS[id]);});}
const dirty=new Set();
const STAMP_FIELDS=['stampimg','signimg','signame','sigdes','apm','ap4','ap5','ap7','ap8'];
const stampUsers=Array.isArray(CFG.users)?CFG.users.slice():[];
const userStamps=Object.assign({}, CFG.user_stamps||{});
let stampUserId=String(CFG.current_user_id||(stampUsers[0]&&stampUsers[0].id)||'');
function blankStamp(uid,name){return{user_id:+uid||0,stampimg:'',signimg:'',signame:name||'',sigdes:'',apm:defaultMatrix(),ap4:false,ap5:false,ap7:false,ap8:true,is_active:true,has_record:false};}
function ensureStamp(uid){const id=String(uid||'');if(!id)return blankStamp(0,'');if(!userStamps[id]){const u=stampUsers.find(x=>String(x.id)===id);userStamps[id]=blankStamp(id,u&&u.name);}return userStamps[id];}
function stampUserUrl(template){const id=String(stampUserId||'');if(!id||!/^\d+$/.test(id))throw new Error('Select a user first');const url=String(template||'');if(url.indexOf('__UID__')!==-1)return url.split('__UID__').join(id);return url.replace(/(\/user-stamps\/)[^/?#]+/,'$1'+id);}
function isStampField(fid){return STAMP_FIELDS.indexOf(fid)>=0;}

/* ---------- value helpers ---------- */
const HO=()=>ents[0];
const inherited=rid=>cur.type==='br'&&!ROWS[rid].noInh&&(cur.inh[rid]??!!ROWS[rid].inh);
const fieldRow={};SECS.forEach(s=>(s.rows||[]).forEach(r=>r.f.forEach(f=>fieldRow[f.id]=r.id)));
fieldRow.curmode=fieldRow.curimg='curic';
function val(fid,e=cur){
  if(isStampField(fid)){const s=ensureStamp(stampUserId);return s[fid];}
  const rid=fieldRow[fid];if(e===cur&&rid&&inherited(rid))return HO().vals[fid];if(e!==cur&&e.type==='br'&&rid&&!ROWS[rid].noInh&&(e.inh[rid]??!!ROWS[rid].inh))return HO().vals[fid];return e.vals[fid];
}
function V(){const v={};Object.keys(HO_VALS).forEach(k=>v[k]=val(k));return v;}
function now(v){try{return new Date(new Date().toLocaleString('en-US',{timeZone:v.tz||'Asia/Dubai'}));}catch(e){return new Date();}}
function fmtDate(d,f){const p=n=>String(n).padStart(2,'0');return f.replace('dddd','\u0001').replace('MMMM','\u0002').replace('MMM','\u0003').replace('YYYY',d.getFullYear()).replace('YY',String(d.getFullYear()).slice(2)).replace('MM',p(d.getMonth()+1)).replace('DD',p(d.getDate())).replace('\u0001',DAYS[d.getDay()]).replace('\u0002',MONTHS[d.getMonth()]).replace('\u0003',MONTHS[d.getMonth()].slice(0,3));}
function fmtTime(d,v){return d.toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit',second:v.secs?'2-digit':undefined,hour12:v.timef==='12'}).toUpperCase();}
function hijri(d){try{return new Intl.DateTimeFormat('en-u-ca-islamic-umalqura',{day:'numeric',month:'long',year:'numeric'}).format(d);}catch(e){return'—';}}
function weekRange(d,ws){const s=new Date(d);s.setDate(d.getDate()-((d.getDay()-ws+7)%7));const e=new Date(s);e.setDate(s.getDate()+6);const f=x=>fmtDate(x,'DD MMM');return f(s)+' \u2013 '+f(e);}
function fyRange(d,m){const y=d.getMonth()>=m?d.getFullYear():d.getFullYear()-1;return m===0?`Jan \u2013 Dec ${y}`:`${MONTHS[m].slice(0,3)} ${y} \u2013 ${MONTHS[(m+11)%12].slice(0,3)} ${y+1}`;}
function amount(v){const code=(v.cur||'AED').slice(0,3);const icon=assetUrl(v.curimg);if(v.curmode==='c'&&icon)return`<img src="${esc(icon)}" alt="" style="height:13px;vertical-align:-2px;margin-right:3px">1,234.50`;return`${code} 1,234.50`;}
function status(d){if(!d)return'<span class="st none">No date</span>';const t=new Date();t.setHours(0,0,0,0);const n=Math.round((new Date(d)-t)/864e5);if(n<0)return`<span class="st exp">Expired ${-n} days ago</span>`;if(n<=30)return`<span class="st soon">Expires in ${n} days</span>`;return`<span class="st ok">Valid \u00B7 ${n} days left</span>`;}

/* ---------- render: field ---------- */
function field(f){
  const v=val(f.id);const mini=f.mini?`<span class="mini">${f.mini}</span>`:'';
  let h='';
  if(f.type==='text'){const inp=`<input class="in${f.mono?' mono':''}" id="f-${f.id}" data-f="${f.id}" value="${esc(v)}" ${f.ph?`placeholder="${esc(f.ph)}"`:''} ${f.max?`maxlength="${f.max}"`:''} ${f.dir?`dir="${f.dir}"`:''}>`;
    h=f.pre||f.suf?`<div class="ig">${f.pre?`<span class="pre">${f.pre}</span>`:''}${inp}${f.suf?`<span class="suf">${f.suf}</span>`:''}</div>`:inp;}
  else if(f.type==='select')h=`<select class="in" id="f-${f.id}" data-f="${f.id}">${f.opts.map(([a,b])=>`<option value="${esc(a)}"${String(v)===String(a)?' selected':''}>${esc(b||'Select\u2026')}</option>`).join('')}</select>`;
  else if(f.type==='date')h=`<input type="date" class="in" id="f-${f.id}" data-f="${f.id}" value="${esc(v)}">${f.exp?`<div>${status(v)}</div>`:''}`;
  else if(f.type==='docmatrix'){const m=v||defaultMatrix();const q=(window.__mq||'').toLowerCase();const all=Object.values(m);
    const colAll=c=>all.every(x=>x[c]);const n=c=>all.filter(x=>x[c]).length;
    h=`<div class="mx"><div class="mxbar"><input class="in mxq" id="mxq" placeholder="Search module" value="${esc(window.__mq||'')}"><span class="mxsum">${MCOLS.map(([c,t])=>`<b>${n(c)}</b> ${t.toLowerCase()}`).join(' \u00B7 ')} of ${all.length} modules</span></div>
    <div class="mxw"><table class="mxt"><thead><tr><th>Module</th>${MCOLS.map(([c,t])=>`<th><label class="mxh"><input type="checkbox" data-mall="${c}"${colAll(c)?' checked':''}><span>${t}</span></label></th>`).join('')}</tr></thead><tbody>${MODGROUPS.map(([g,mods])=>{const vis=mods.filter(x=>!q||x.toLowerCase().includes(q));if(!vis.length)return'';return`<tr class="mxg"><td colspan="4">${g}</td></tr>`+vis.map(x=>`<tr><td>${x}</td>${MCOLS.map(([c])=>`<td><label class="mxc${m[x][c]?' on':''}"><input type="checkbox" data-m="${esc(x)}" data-c="${c}"${m[x][c]?' checked':''} aria-label="${esc(x)} ${c}"><span>\u2713</span></label></td>`).join('')}</tr>`).join('');}).join('')}</tbody></table></div></div>`;}
  else if(f.type==='tile')return`<label class="tile${v?' on':''}"><input type="checkbox" id="f-${f.id}" data-f="${f.id}"${v?' checked':''}><span class="ti">${ico(f.icon)}</span><span>${f.text}</span><span class="tck">\u2713</span></label>`;
  else if(f.type==='switch')h=`<label class="sw2"><input type="checkbox" id="f-${f.id}" data-f="${f.id}"${v?' checked':''}>${f.text}</label>`;
  else if(f.type==='upload'){const img=assetUrl(v);h=`<div class="up"><div class="box">${img?`<img src="${esc(img)}" alt="">`:'No file'}</div><label class="filebtn">Upload<input type="file" accept="image/*" data-up="${f.id}"></label>${img?`<button class="filebtn" type="button" data-clear="${f.id}">Remove</button>`:''}</div>`;}
  else if(f.type==='curicon'){const m=val('curmode'),img=assetUrl(val('curimg'));h=`<div class="stack"><label class="radio"><input type="radio" name="curmode" value="d" data-f="curmode"${m!=='c'?' checked':''}>Use default currency symbol</label><label class="radio"><input type="radio" name="curmode" value="c" data-f="curmode"${m==='c'?' checked':''}>Upload custom currency icon</label></div>${m==='c'?`<div class="upbox"><div class="up"><div class="cur-ic">${img?`<img src="${esc(img)}" alt="">`:'AED'}</div><label class="filebtn">Choose file<input type="file" accept="image/png,image/svg+xml" data-up="curimg"></label>${img?`<button class="filebtn" type="button" data-clear="curimg">Remove custom icon</button>`:''}</div><span class="help">PNG or SVG, transparent background, at least 64\u00D764. Applies to this company's amounts only (UI, PDFs, emails).</span></div>`:''}`;}
  else if(f.type==='langpv'){const vv=V();const L={en:'Save Settings',ar:'Save Settings',ur:'Save Settings',hi:'Save Settings'};const dir=vv.dir==='auto'?(['ar','ur'].includes(vv.ui)?'rtl':'ltr'):vv.dir;const t=vv.doc==='en'?'TAX INVOICE':vv.doc==='ar'?'TAX INVOICE':'TAX INVOICE / فاتورة ضريبية';h=`<div dir="${dir}" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap"><span class="cd-btn" style="pointer-events:none;padding:7px 16px;font-size:12.5px">${L[vv.ui]}</span><b style="letter-spacing:.04em">${t}</b></div>`;}
  return`<div>${mini}${h}</div>`;
}
function rowHtml(r){
  const vv=V();const inh=inherited(r.id);
  const pv=r.pv?r.pv(vv):null;
  const tiles=r.f.every(f=>f.type==='tile');const body=tiles?(()=>{const n=r.f.filter(f=>val(f.id)).length;return`<div class="tilehd"><span>${n} of ${r.f.length} selected</span><span><button type="button" data-tiles="${r.id}" data-on="1">Select all</button> \u00B7 <button type="button" data-tiles="${r.id}" data-on="0">Clear</button></span></div><div class="tiles">${r.f.map(field).join('')}</div>`;})():r.grid?`<div class="g2">${r.f.map(field).join('')}</div>`:r.f.length>1&&r.f.every(f=>f.type==='switch')?`<div class="stack">${r.f.map(field).join('')}</div>`:r.f.map(field).join('<div style="height:8px"></div>');
  const t=cur.type==='br'&&!r.noInh?`<button type="button" class="inh-t" data-inh="${r.id}" aria-pressed="${inh}"><i></i>Same as Head Office</button>`:'';
  const dk=DOC_KEYS[r.id]?cur.docs.findIndex(d=>d.key===r.id):-1;const dline=dk>=0?`<div class="docline">${cur.docs[dk].file?`<span class="st ok" style="margin:0">Synced</span><button type="button" class="flink" data-view="${dk}">\uD83D\uDCCE ${esc(cur.docs[dk].file)}</button><span>\u00B7 in Documents module</span>`:'<span>Copy of the document:</span>'}${cur.docs[dk].file?`<label class="filebtn">Replace<input type="file" data-dfile="${dk}"></label>`:`<label class="filebtn">Choose file<input type="file" data-dfile="${dk}"></label>`}</div>`:'';
  return`<div class="cd-field${inh?' inh':''}${r.wide?' wide':''}" data-row="${r.id}"><button type="button" class="grip" title="Drag to reorder" aria-label="Drag to reorder"></button><div class="l"><b>${r.label}</b>${r.help?`<small>${r.help}</small>`:''}${t}</div><div class="r">${body}${pv?`<div class="pv"><span>${pv[0]}</span><b>${pv[1]}</b></div>`:''}${dline}</div></div>`;
}

/* ---------- render: sections ---------- */
function stampUserBar(){
  if(!stampUsers.length)return`<div class="warn">No users found for this company. Add users first, then assign stamps and signatures.</div>`;
  const canPick=IS_ADMIN;
  const opts=stampUsers.map(u=>`<option value="${u.id}"${String(u.id)===String(stampUserId)?' selected':''}>${esc(u.name)}${u.email?` — ${esc(u.email)}`:''}</option>`).join('');
  return`<div class="syncbar" style="margin-top:14px"><span class="sdot"></span><div><b>User stamp &amp; signature</b><br>Each login user has their own stamp, signature and document permissions. ${canPick?'Administrators can select any user to manage.':'Showing your own stamp &amp; signature.'}</div>
  <div style="min-width:220px"><span class="mini">User</span><select class="in" id="stampUserPick" ${canPick?'':'disabled'}>${opts}</select></div></div>`;
}
function renderPanel(){
  const s=SECS.find(x=>x.k===curSec);const panel=document.getElementById('panel');
  let h=`<div class="ph"><div><h2>${s.t}</h2><p>${s.s}</p></div>${s.lock?'<span class="lock">Admin only</span>':''}</div>`;
  if(s.k==='stamp')h+=stampUserBar();
  if(s.rows){h+=`<div class="tip">Drag a field by its <span class="grip" aria-hidden="true"></span> handle to change the order.<button type="button" id="resetOrder">Reset order</button></div>`;h+=order[s.k].map(id=>rowHtml(ROWS[id])).join('');}
  if(s.custom==='docs')h+=docsHtml();
  if(s.custom==='branches')h+=branchesHtml();
  if(s.custom==='depts')h+=deptsHtml();
  if(s.warn)h+=`<div class="warn">Changing this setting can affect existing vouchers, VAT returns and reports. You will be asked to confirm before saving.</div>`;
  if(s.lock && !IS_ADMIN){h+=`<div class="warn">Admin only — you can view these settings but cannot change them.</div>`;}
  panel.innerHTML=h;
    if(s.lock && !IS_ADMIN){const st=document.createElement('style');st.textContent='#panel .cd-field,#panel .bank,#panel .addbtn,#panel .tblw{opacity:.7;pointer-events:none}';panel.prepend(st);}
if(s.custom==='theme'){if(!window.__thmEl){window.__thmEl=document.createElement('div');const th=window.mountTheme(window.__thmEl,()=>mark());window.__thmGet=function(){return th&&th.get?th.get():null;};}panel.appendChild(window.__thmEl);}
  if(typeof window.initSettingsSelect2==='function'){window.initSettingsSelect2(panel,true);}
}
function branchesHtml(){
  const card=e=>{const v=k=>val(k,e);return`<button type="button" class="bc" data-ent="${ents.indexOf(e)}"><span class="tag ${e.type==='co'?'br':e.type}" style="align-self:flex-start">${e.type==='ho'?'Head Office':(e.code||'Branch')}</span><h4>${esc(e.label)}</h4><div class="m">City: <b>${esc(v('city')||e.place||'\u2014')}</b><br>Phone: <b>${esc(v('phone')||'\u2014')}</b><br>Email: <b>${esc(v('email')||'\u2014')}</b><br>License: <b>${esc(v('tlno')||'\u2014')}</b></div>${status(v('tlexp'))}</button>`;};
  return`<div class="syncbar"><span class="sdot"></span><div><b>${ents.length} locations</b><br>Open a branch to edit its profile, or create a new branch. Need a new legal company? Contact your super admin.</div><button type="button" class="cd-btn" id="cdCreateBranch" style="padding:8px 14px;font-size:12.5px;white-space:nowrap">+ Create branch</button></div>
  <div class="grid">${ents.map(card).join('')}<button type="button" class="bc add" data-create-branch>+ Create branch</button></div>`;
}
let departments=Array.isArray(CFG.departments)?CFG.departments.slice():[];
function deptsHtml(){
  const rows=departments.map((d,i)=>`<tr data-dept="${i}"><td><b>${esc(d.name||'')}</b>${d.detail?`<small class="dm">${esc(d.detail)}</small>`:''}</td><td>${d.status?`<span class="st ok">Active</span>`:`<span class="st none">Inactive</span>`}</td><td style="text-align:right;white-space:nowrap"><button type="button" class="filebtn" data-edit-dept="${i}">Edit</button> <button type="button" class="del" data-rm-dept="${i}" title="Delete">\u2715</button></td></tr>`).join('');
  return`<div class="syncbar"><span class="sdot"></span><div><b>Departments</b><br>Manage teams used across employees, vouchers and approvals.</div><button type="button" class="cd-btn" id="cdAddDept" style="padding:8px 14px;font-size:12.5px;white-space:nowrap">+ Add department</button></div>
  <div class="tblw cd-depts"><table><thead><tr><th>Department</th><th style="width:120px">Status</th><th style="width:140px"></th></tr></thead><tbody>${rows||`<tr><td colspan="3" style="text-align:center;padding:26px;color:var(--muted)">No departments yet. Add one to get started.</td></tr>`}</tbody></table></div>`;
}
function openDeptModal(dept, index){
  const d=dept||{name:'',detail:'',status:true};
  const m=modal(`<div class="cd-card" role="dialog" aria-label="Department"><div class="hd"><div><h3>${dept?'Edit department':'Add department'}</h3><p>Used for users, employees and approvals</p></div></div><div class="bd">
  <div><span class="mini">Name</span><input class="in" id="depName" value="${esc(d.name||'')}" placeholder="e.g. Accounts" autocomplete="off"></div>
  <div><span class="mini">Detail</span><input class="in" id="depDetail" value="${esc(d.detail||'')}" placeholder="Optional description" autocomplete="off"></div>
  <label class="sw2"><input type="checkbox" id="depStatus"${d.status!==false?' checked':''}> Active</label>
  <p id="depErr" style="margin:0;color:var(--bad);font-size:12px" hidden>Enter a department name.</p>
  </div><div class="ft"><button type="button" class="cd-btn ghost" data-close>Cancel</button><button type="button" class="cd-btn" id="depOk">Save</button></div></div>`);
  setTimeout(function(){m.querySelector('#depName')?.focus();},30);
  m.querySelector('#depOk').onclick=function(){
    const name=(m.querySelector('#depName').value||'').trim();
    if(!name){m.querySelector('#depErr').hidden=false;m.querySelector('#depName').focus();return;}
    const payload={name, detail:(m.querySelector('#depDetail').value||'').trim(), status:!!m.querySelector('#depStatus').checked};
    const isEdit=typeof index==='number' && departments[index] && departments[index].id;
    const url=isEdit?(ROUTES.updateDepartment||'').replace('__ID__',String(departments[index].id)):(ROUTES.storeDepartment||'');
    if(!url){toast('Department route missing');return;}
    api(url,{method:isEdit?'PUT':'POST',body:JSON.stringify(payload)}).then(function(res){
      if(Array.isArray(res.departments)){departments=res.departments;}
      else if(res.department){
        if(isEdit){departments[index]=res.department;}
        else{departments.push(res.department);}
      }
      m.remove();renderPanel();toast(isEdit?'Department updated':'Department added');
    }).catch(function(err){toast(err.message||'Save failed');});
  };
}
function bankMatrixHtml(b,i){
  const m=b.matrix||defaultBankMatrix();
  const q=((window.__bmq||{})[i]||'').toLowerCase();
  const all=Object.values(m);
  const colAll=c=>all.length&&all.every(x=>x[c]);
  const n=c=>all.filter(x=>x[c]).length;
  return`<div class="bank-mods"><div class="mini" style="margin-bottom:8px">Show this account on (same module list as Stamp &amp; Signature)</div>
  <div class="mx"><div class="mxbar"><input class="in mxq" data-bmq="${i}" placeholder="Search module" value="${esc((window.__bmq||{})[i]||'')}"><span class="mxsum">${BCOLS.map(([c,t])=>`<b>${n(c)}</b> ${t.toLowerCase()}`).join(' \u00B7 ')} of ${all.length} modules</span></div>
  <div class="mxw"><table class="mxt"><thead><tr><th>Module</th>${BCOLS.map(([c,t])=>`<th><label class="mxh"><input type="checkbox" data-bmall="${c}" data-bi="${i}"${colAll(c)?' checked':''}><span>${t}</span></label></th>`).join('')}</tr></thead><tbody>${MODGROUPS.map(([g,mods])=>{const vis=mods.filter(x=>!q||x.toLowerCase().includes(q));if(!vis.length)return'';return`<tr class="mxg"><td colspan="4">${g}</td></tr>`+vis.map(x=>`<tr><td>${x}</td>${BCOLS.map(([c])=>`<td><label class="mxc${m[x]&&m[x][c]?' on':''}"><input type="checkbox" data-bm="${esc(x)}" data-bc="${c}" data-bi="${i}"${m[x]&&m[x][c]?' checked':''} aria-label="${esc(x)} ${c}"><span>\u2713</span></label></td>`).join('')}</tr>`).join('');}).join('')}</tbody></table></div></div></div>`;
}
function bankHtml(){
  let h='';
  if(cur.type==='br')h+=`<div class="inhbar"><button type="button" class="inh-t" data-hobank aria-pressed="${cur.useHoBank}"><i></i>Use Head Office bank accounts</button></div>`;
  const list=cur.type==='br'&&cur.useHoBank?HO().banks.map(normalizeBank):cur.banks;const ro=cur.type==='br'&&cur.useHoBank;
  h+=`<div${ro?' style="opacity:.55;pointer-events:none"':''}>`+list.map((b,i)=>{b=normalizeBank(b);return`<div class="bank" data-bank="${i}"><div class="bank-h">${ico('bank')} Bank account ${i+1} ${i===0?'<span class="tag br">Primary</span>':`<button type="button" class="x" data-rmbank="${i}">Remove</button>`}</div><div class="g2">
  <div><span class="mini">Bank name</span><select class="in" data-b="bank">${BANKS.map(x=>`<option${x===b.bank?' selected':''}>${x}</option>`).join('')}</select></div>
  <div><span class="mini">Account title</span><input class="in" data-b="title" value="${esc(b.title)}" placeholder="Express Fast Delivery Service L.L.C"></div>
  <div><span class="mini">IBAN</span><input class="in mono" data-b="iban" value="${esc(b.iban)}" placeholder="AE07 0331 2345 6789 0123 456" maxlength="29"><span class="ibanmsg"></span></div>
  <div><span class="mini">SWIFT / BIC</span><input class="in mono" data-b="swift" value="${esc(b.swift)}" placeholder="EBILAEAD" maxlength="11" style="text-transform:uppercase"></div>
  <div><span class="mini">Account number</span><input class="in mono" data-b="acc" value="${esc(b.acc)}"></div>
  <div><span class="mini">Currency</span><select class="in" data-b="cur">${['AED','USD','EUR'].map(x=>`<option${x===b.cur?' selected':''}>${x}</option>`).join('')}</select></div></div>
  ${bankMatrixHtml(b,i)}</div>`;}).join('')+`</div>`;
  if(!ro)h+=`<button type="button" class="addbtn" id="addBank">+ Add another bank account</button>`;else h+='<div style="height:18px"></div>';
  return h;
}
const dGet=(d,f,e=cur)=>d.key&&(f==='no'||f==='exp')?(e.vals[DOC_KEYS[d.key][f==='no'?0:1]]||''):(d[f]||'');
function dSet(d,f,v){if(d.key&&(f==='no'||f==='exp'))cur.vals[DOC_KEYS[d.key][f==='no'?0:1]]=v;else d[f]=v;}
function syncCell(d,e=cur){return d.file?`<span class="st ok">Synced</span><small class="dm">Company \u203A ${esc(e.code)} \u203A ${esc(d.n||'Document')}<br>${esc(d.up)}</small>`:'<span class="st none">Waiting for file</span>';}
function docsHtml(){
  const n=cur.docs.filter(d=>d.file).length;
  return`<div class="syncbar"><span class="sdot"></span><div><b>Linked to the Documents module.</b> Every file uploaded here is saved in Documents under <b>Type: Company \u00B7 Branch: ${esc(cur.code)}</b>, with the same number and expiry date. A change made in either place shows in both. Trade License, MOHRE and Immigration also stay in step with the Legal &amp; Licenses tab.</div><button type="button" class="cd-btn ghost" id="openDocs" style="padding:7px 14px;font-size:12.5px;white-space:nowrap">Open Documents module (${n})</button></div>
  <div class="tblw"><table><thead><tr><th style="width:22%">Document</th><th style="width:15%">Number</th><th style="width:15%">Expiry</th><th>Status</th><th>File</th><th>Documents module</th><th></th></tr></thead><tbody>${cur.docs.map((d,i)=>`<tr data-doc="${i}"><td>${d.file?`<div class="dname"><button type="button" class="dlink" data-view="${i}" title="Open ${esc(d.file)}">${esc(d.n||'Document')}</button>${d.key?'':`<button type="button" class="ren" data-ren="${i}" title="Rename" aria-label="Rename document">\u270E</button>`}</div>`:`<input class="in" data-d="n" value="${esc(d.n)}"${d.key?' readonly title="Linked to Legal & Licenses"':''}>`}${d.key?'<small class="dm">Linked to Legal &amp; Licenses</small>':''}</td><td><input class="in mono" data-d="no" value="${esc(dGet(d,'no'))}" placeholder="Number"></td><td><input type="date" class="in" data-d="exp" value="${esc(dGet(d,'exp'))}"></td><td>${status(dGet(d,'exp'))}</td><td>${d.file?`<div class="fbtns"><label class="filebtn">Replace<input type="file" data-dfile="${i}"></label></div>`:`<label class="filebtn">Choose file<input type="file" data-dfile="${i}"></label>`}</td><td>${syncCell(d)}</td><td>${d.key?'':`<button type="button" class="del" data-rmdoc="${i}" title="Remove document">\u2715</button>`}</td></tr>`).join('')}</tbody></table></div><button type="button" class="addbtn" id="addDoc">+ Add document</button>`;
}
function viewDoc(d){
  const inner=d.kind==='img'?`<img src="${d.url}" alt="${esc(d.file)}" style="max-width:100%;max-height:68vh;border-radius:8px">`:`<iframe src="${d.url}" title="${esc(d.file)}" style="width:100%;height:68vh;border:0;border-radius:8px;background:#fff"></iframe>`;
  const m=modal(`<div class="cd-card wide" role="dialog" aria-label="${esc(d.file)}"><div class="hd"><div><h3>${esc(d.file)}</h3><p>${esc(d.n||'Document')} \u00B7 ${esc(cur.label)} \u00B7 ${status(dGet(d,'exp'))}</p></div><button type="button" class="cd-btn ghost" data-close style="margin-left:auto;padding:7px 14px">Close</button></div><div style="padding:18px 22px;background:var(--surface-2);display:grid;place-items:center">${inner}</div><div class="ft"><label class="filebtn">Replace<input type="file" id="vRep"></label><button type="button" class="cd-btn" data-close>Done</button></div></div>`);
  m.querySelector('#vRep').onchange=e=>{const f=e.target.files[0];if(!f)return;URL.revokeObjectURL(d.url);d.url=URL.createObjectURL(f);d.kind=/\.pdf$/i.test(f.name)?'pdf':'img';d.file=f.name;const n=new Date();d.up='Uploaded '+fmtDate(n,'DD MMM YYYY')+', '+fmtTime(n,{timef:'12'});mark();m.remove();renderPanel();toast(`${d.n||'Document'} replaced \u00B7 Documents module updated`);};
}
function openDocsModule(){
  const rows=[];ents.forEach(e=>e.docs.forEach(d=>{if(d.file)rows.push({e,d});}));
  const m=modal(`<div class="cd-card wide" role="dialog" aria-label="Documents module"><div class="hd"><div><h3>Documents</h3><p>How these files appear in the ERP Documents module \u00B7 filtered by Type: Company</p></div><button type="button" class="cd-btn ghost" data-close style="margin-left:auto;padding:7px 14px">Close</button></div>
  <div class="tblw" style="margin:16px 22px"><table><thead><tr><th>Type</th><th>Branch</th><th>Document type</th><th>Number</th><th>File name</th><th>Expiry date</th><th>Status</th><th>Uploaded</th></tr></thead><tbody>${rows.length?rows.map(({e,d})=>`<tr><td>Company</td><td><span class="tag ${e.type}">${esc(e.code)}</span></td><td>${esc(d.n)}</td><td class="mono">${esc(dGet(d,'no',e)||'—')}</td><td>${esc(d.file)}</td><td>${esc(dGet(d,'exp',e)||'—')}</td><td>${status(dGet(d,'exp',e))}</td><td><small class="dm" style="margin:0">${esc(d.up)}</small></td></tr>`).join(''):`<tr><td colspan="8" style="text-align:center;padding:26px;color:var(--muted)">No company files yet. Upload one in Company Documents or Legal &amp; Licenses and it will appear here.</td></tr>`}</tbody></table></div></div>`);
}
function renderNav(){
  document.getElementById('nav').innerHTML=SECTIONS.map(s=>s.grp?`<div class="grp">${s.grp}</div>`:`<button type="button" class="nb${s.k===curSec?' on':''}${dirty.has(s.k)?' dirty':''}" data-sec="${s.k}"><span class="i">${ico(s.icon)}</span><span><b>${s.t}</b><small>${s.s}</small></span><span class="dot"></span></button>`).join('');
}
function renderHead(){
  const v=V();
  document.getElementById('hEyebrow').textContent=cur.type==='br'?'Branch Profile':'Company Profile';
  document.getElementById('hName').textContent=cur.type==='br'?`${v.name} — ${cur.place}`:v.name;
  const tag=document.getElementById('hTag');tag.className='tag '+(cur.type==='co'?'br':cur.type);tag.textContent=cur.type==='ho'?'Head Office':(cur.code?cur.code+' Branch':'Branch');
  const logo=assetUrl(val('logo'));document.getElementById('hLogo').innerHTML=logo?`<img src="${esc(logo)}" alt="Company logo">`:'<div class="wordmark">EXPRESS<small>FAST DELIVERY</small></div>';
  const tz=(TZ.find(t=>t[0]===v.tz)||TZ[0])[1].slice(1,10);
  document.getElementById('hChips').innerHTML=`<span class="mono">TRN ${esc(v.trn||'—')}</span><span>${esc([v.city,v.country].filter(Boolean).join(', ')||'No location')}</span><span>${esc((v.cur||'').slice(0,3))}</span><span>${tz}</span>`;
  const keys=['name','phone','email','address','city','country','trn','web','tlno','tlexp'];const n=keys.filter(k=>String(v[k]||'').trim()).length+(logo?1:0);const pct=Math.round(n/(keys.length+1)*100);
  document.getElementById('cPct').textContent=pct+'%';document.getElementById('cBar').style.width=pct+'%';document.getElementById('cTxt').textContent=pct===100?'All details complete':`${keys.length+1-n} details missing`;
  const b=document.getElementById('banner');
  b.innerHTML=cur.type==='ho'?'':cur.type==='br'?`<div class="banner">You are editing <b>&nbsp;${esc(cur.label)}</b>. Fields set to <b>&nbsp;Same as Head Office&nbsp;</b> copy the Head Office value. Switch one off to enter a branch-specific value.<button type="button" data-back>Back to Head Office</button></div>`:`<div class="banner co">You are editing <b>&nbsp;${esc(cur.label)}</b>, a separate company with its own TRN, license and bank accounts.<button type="button" data-back>Back to Head Office</button></div>`;
  document.getElementById('save').textContent=cur.type==='ho'?'Save Settings':`Save ${cur.code}`;
}
function render(){renderHead();renderNav();renderPanel();ibanCheckAll();}

/* ---------- menu, create, all ---------- */
const menu=document.getElementById('menu'),swb=document.getElementById('sw');
const typeIc=t=>t==='ho'?ico('building'):t==='co'?ico('building'):ico('pin');
function openMenu(){
  menu.innerHTML=`<div class="sync"><i></i>Synced from Branches module \u00B7 ${ents.length} locations</div>`+ents.map((e,i)=>`<button type="button" class="mi${e===cur?' on':''}" data-ent="${i}"><span class="ic">${typeIc(e.type)}</span><span>${esc(e.label)}<small>${e.type==='ho'?'Head office':'Branch'} \u00B7 ${esc(e.code)}</small></span>${e===cur?'<span class="chk">\u2713</span>':''}</button>`).join('')+`<hr><button type="button" class="mi" data-all><span class="ic">${ico('grid')}</span><span>View all branches<small>Overview of every location</small></span></button><button type="button" class="mi new" data-create><span class="ic">${ico('plus')}</span><span>Create branch<small>Also adds it to the Branches module</small></span></button>`;
  menu.hidden=false;swb.setAttribute('aria-expanded','true');
}
function closeMenu(){menu.hidden=true;swb.setAttribute('aria-expanded','false');}
function switchTo(e){cur=e;closeMenu();document.querySelector('.cd-modal')?.remove();render();window.scrollTo({top:0,behavior:'smooth'});}
function modal(html){
  document.querySelectorAll('.cd-modal').forEach(function(el){el.remove();});
  const m=document.createElement('div');
  // Keep company-details-root class so .in / .cd-btn / .cd-card styles still apply on body.
  m.className='cd-modal company-details-root';
  m.setAttribute('role','presentation');
  m.innerHTML=html;
  document.body.appendChild(m);
  m.addEventListener('click',ev=>{if(ev.target===m||ev.target.closest('[data-close]'))m.remove();});
  if(typeof window.initSettingsSelect2==='function'){window.initSettingsSelect2(m,true);}
  return m;
}
function openCreate(){
  const m=modal(`<div class="cd-card" role="dialog" aria-label="Create branch"><div class="hd"><div><h3>Create branch</h3><p>Add a branch location for this company</p></div></div><div class="bd">
  <div class="syncbar" style="margin:0"><span class="sdot"></span><div><b>Branch of this company</b><br>Same company and TRN in a different location. Shares Head Office details.</div></div>
  <div class="warn" style="margin:0">Need a new legal company? Contact your super admin — separate companies cannot be created here.</div>
  <div><span class="mini">Name</span><input class="in" id="cName" placeholder="Express Fast \u2014 Sharjah" autocomplete="off"></div>
  <div class="g2"><div><span class="mini">Emirate</span><select class="in" id="cEm">${EMIRATES.map(x=>`<option>${x}</option>`).join('')}</select></div><div><span class="mini">Short code</span><input class="in mono" id="cCode" maxlength="5" style="text-transform:uppercase" autocomplete="off"></div></div>
  <p id="cErr" style="margin:0;color:var(--bad);font-size:12px" hidden>Enter a name to continue.</p>
  </div><div class="ft"><button type="button" class="cd-btn ghost" data-close>Cancel</button><button type="button" class="cd-btn" id="cOk">Create and open</button></div></div>`);
  const nm=m.querySelector('#cName'),em=m.querySelector('#cEm'),cd=m.querySelector('#cCode');
  const auto=()=>{if(!nm.dataset.t)nm.value=`Express Fast \u2014 ${em.value}`;if(!cd.dataset.t)cd.value=CODES[em.value]||'';};
  nm.oninput=()=>nm.dataset.t=1;cd.oninput=()=>cd.dataset.t=1;em.onchange=auto;auto();
  m.querySelector('#cOk').onclick=()=>{if(!nm.value.trim()){m.querySelector('#cErr').hidden=false;nm.focus();return;}
    api(ROUTES.storeEntity,{method:'POST',body:JSON.stringify({type:'br',name:nm.value.trim(),emirate:em.value,code:(cd.value||'NEW').toUpperCase(),country:'United Arab Emirates'})}).then(function(res){if(res.bootstrap&&res.bootstrap.entities){ents=res.bootstrap.entities.map(mapEntity);}var e=ents.find(function(x){return String(x.id)===String(res.entity_id);})||ents[ents.length-1];switchTo(e);toast(((e&&e.label)?e.label:'Branch')+' created');}).catch(function(err){toast(err.message||'Create failed');});};
  nm.focus();
}
function openAll(){
  const card=e=>{const v=k=>val(k,e);return`<button type="button" class="bc" data-ent="${ents.indexOf(e)}"><span class="tag ${e.type==='co'?'br':e.type}" style="align-self:flex-start">${e.type==='ho'?'Head Office':(e.code||'Branch')}</span><h4>${esc(e.label)}</h4><div class="m">City: <b>${esc(v('city')||e.place||'\u2014')}</b><br>Phone: <b>${esc(v('phone')||'\u2014')}</b><br>Email: <b>${esc(v('email')||'\u2014')}</b><br>License: <b>${esc(v('tlno')||'\u2014')}</b></div>${status(v('tlexp'))}</button>`;};
  const m=modal(`<div class="cd-card wide" role="dialog" aria-label="All branches"><div class="hd"><div><h3>All branches</h3><p>${ents.length} locations. Select one to open it.</p></div><button type="button" class="cd-btn ghost" data-close style="margin-left:auto;padding:7px 14px">Close</button></div><div class="grid">${ents.map(card).join('')}<button type="button" class="bc add" data-create>+ Create branch</button></div></div>`);
  m.addEventListener('click',ev=>{const c=ev.target.closest('[data-ent]');if(c)switchTo(ents[+c.dataset.ent]);if(ev.target.closest('[data-create]'))openCreate();});
}

/* ---------- events ---------- */
swb.addEventListener('click',ev=>{ev.preventDefault();ev.stopPropagation();menu.hidden?openMenu():closeMenu();});
menu.addEventListener('click',ev=>{ev.stopPropagation();const b=ev.target.closest('.mi');if(!b)return;if(b.dataset.ent)switchTo(ents[+b.dataset.ent]);else if(b.hasAttribute('data-all')){closeMenu();openAll();}else if(b.hasAttribute('data-create')){closeMenu();openCreate();}});
document.addEventListener('click',()=>closeMenu());
document.addEventListener('keydown',ev=>{if(ev.key==='Escape'){closeMenu();document.querySelector('.cd-modal')?.remove();}});
document.getElementById('banner').onclick=ev=>{if(ev.target.closest('[data-back]'))switchTo(ents[0]);};
document.getElementById('nav').addEventListener('click',ev=>{
  const b=ev.target.closest('[data-sec]');
  if(!b)return;
  ev.preventDefault();
  ev.stopPropagation();
  curSec=b.dataset.sec;
  renderNav();
  renderPanel();
  ibanCheckAll();
  const panelEl=document.getElementById('panel');
  if(panelEl){panelEl.scrollIntoView({behavior:'smooth',block:'nearest'});}
});
const panel=document.getElementById('panel');
function mark(){dirty.add(curSec);document.querySelector(`.nb[data-sec="${curSec}"]`)?.classList.add('dirty');document.getElementById('saveNote').textContent='Unsaved changes';}
function setVal(fid,v){
  if(isStampField(fid)){const s=ensureStamp(stampUserId);s[fid]=v;mark();return;}
  cur.vals[fid]=v;mark();
}
panel.addEventListener('input',ev=>{const t=ev.target;
  if(t.id==='mxq'){window.__mq=t.value;const pos=t.selectionStart;renderPanel();const n=document.getElementById('mxq');n.focus();n.setSelectionRange(pos,pos);return;}
  if(t.dataset.bmq!==undefined){window.__bmq=window.__bmq||{};window.__bmq[+t.dataset.bmq]=t.value;const pos=t.selectionStart;const bi=+t.dataset.bmq;renderPanel();const n=panel.querySelector(`[data-bmq="${bi}"]`);if(n){n.focus();n.setSelectionRange(pos,pos);}return;}
  if(t.dataset.f&&t.type!=='checkbox'&&t.type!=='radio'&&t.tagName!=='SELECT'){setVal(t.dataset.f,t.value);if(['name','trn','phone','email','address','web','tlno'].includes(t.dataset.f))renderHead();}
  if(t.dataset.b){const i=+t.closest('[data-bank]').dataset.bank;const b=cur.banks[i];if(t.dataset.b==='iban'){let x=t.value.toUpperCase().replace(/[^A-Z0-9]/g,'');t.value=x.replace(/(.{4})/g,'$1 ').trim();b.iban=t.value;ibanCheck(t);}else if(t.type!=='checkbox'&&t.tagName!=='SELECT')b[t.dataset.b]=t.value;mark();}
  if(t.dataset.d&&t.type!=='date'){dSet(cur.docs[+t.closest('[data-doc]').dataset.doc],t.dataset.d,t.value);mark();}
});
panel.addEventListener('change',ev=>{const t=ev.target;
  if(t.id==='stampUserPick'){stampUserId=String(t.value||'');ensureStamp(stampUserId);renderPanel();return;}
  if(t.dataset.m){const m=val('apm')||defaultMatrix();m[t.dataset.m][t.dataset.c]=t.checked;setVal('apm',m);renderPanel();return;}
  if(t.dataset.mall){const m=val('apm')||defaultMatrix();Object.values(m).forEach(x=>x[t.dataset.mall]=t.checked);setVal('apm',m);renderPanel();return;}
  if(t.dataset.f){setVal(t.dataset.f,t.type==='checkbox'?t.checked:t.value);renderHead();renderPanel();ibanCheckAll();return;}
  if(t.dataset.bm){
    const bi=+t.dataset.bi;const bank=normalizeBank(cur.banks[bi]);
    bank.matrix=bank.matrix||defaultBankMatrix();
    if(!bank.matrix[t.dataset.bm])bank.matrix[t.dataset.bm]=blankBankCell();
    bank.matrix[t.dataset.bm][t.dataset.bc]=!!t.checked;
    bank.modules=bankModsFromMatrix(bank.matrix);bank.show=!!bank.modules.invoice;
    cur.banks[bi]=bank;mark();renderPanel();return;
  }
  if(t.dataset.bmall){
    const bi=+t.dataset.bi;const bank=normalizeBank(cur.banks[bi]);
    bank.matrix=bank.matrix||defaultBankMatrix();
    Object.keys(bank.matrix).forEach(mod=>{bank.matrix[mod][t.dataset.bmall]=!!t.checked;});
    bank.modules=bankModsFromMatrix(bank.matrix);bank.show=!!bank.modules.invoice;
    cur.banks[bi]=bank;mark();renderPanel();return;
  }
  if(t.dataset.b&&(t.type==='checkbox'||t.tagName==='SELECT')){cur.banks[+t.closest('[data-bank]').dataset.bank][t.dataset.b]=t.type==='checkbox'?t.checked:t.value;mark();}
  if(t.dataset.d==='exp'){dSet(cur.docs[+t.closest('[data-doc]').dataset.doc],'exp',t.value);mark();renderPanel();}
  if(t.dataset.up&&t.files[0]){
    const fid=t.dataset.up;const file=t.files[0];const rid=fieldRow[fid];
    if(rid&&inherited(rid)){cur.inh[rid]=false;}
    const localUrl=URL.createObjectURL(file);
    setVal(fid,localUrl);renderHead();renderPanel();
    const fd=new FormData();fd.append('field',fid);fd.append('file',file);fd.append('_token',CSRF);
    let uploadUrl;
    try{
      if(fid==='stampimg'||fid==='signimg'){uploadUrl=stampUserUrl(ROUTES.uploadUserStamp);}
      else{uploadUrl=profileUrl(ROUTES.uploadAsset);}
    }catch(e){toast(e.message||'Upload route missing');return;}
    if(!uploadUrl){toast('Upload route missing');return;}
    api(uploadUrl,{method:'POST',body:fd,headers:{}}).then(function(res){
      if(res.record && (fid==='stampimg'||fid==='signimg')){userStamps[String(stampUserId)]=res.record;}
      swapUploadPreview(fid, localUrl, res.url||res.path||'');
      toast('Uploaded');
    }).catch(function(err){
      // Keep the local preview — clearing it caused the "flash then disappear" bug.
      toast(err.message||'Upload failed');
    });
    return;
  }
  if(t.dataset.dfile&&t.files[0]){const d=cur.docs[+t.dataset.dfile];const fd=new FormData();fd.append('file',t.files[0]);fd.append('name',d.n||'Document');if(d.key)fd.append('key',d.key);if(d.no)fd.append('number',d.no);if(d.exp)fd.append('expiry_date',d.exp);if(d.document_type_id)fd.append('document_type_id',d.document_type_id);let docUrl;try{docUrl=profileUrl(ROUTES.uploadDocument);}catch(e){toast(e.message||'Upload failed');return;}api(docUrl,{method:'POST',body:fd,headers:{}}).then(function(res){if(res.bootstrap&&res.bootstrap.entities){const id=cur.id;ents=res.bootstrap.entities.map(mapEntity);cur=ents.find(function(e){return String(e.id)===String(id);})||cur;}mark();renderPanel();toast((d.n||'Document')+' saved');}).catch(function(err){toast(err.message||'Upload failed');});}
});
panel.addEventListener('click',ev=>{const t=ev.target;
  const be=t.closest('[data-ent]');if(be&&curSec==='branches'){curSec='gen';switchTo(ents[+be.dataset.ent]);return;}
  if(t.id==='cdCreateBranch'||t.closest('[data-create-branch]')){openCreate();return;}
  if(t.closest('#cdAddDept')){openDeptModal(null,null);return;}
  const ed=t.closest('[data-edit-dept]');if(ed){ev.preventDefault();openDeptModal(departments[+ed.dataset.editDept],+ed.dataset.editDept);return;}
  const rdpt=t.closest('[data-rm-dept]');if(rdpt){
    const i=+rdpt.dataset.rmDept;const d=departments[i];if(!d||!d.id){departments.splice(i,1);renderPanel();return;}
    if(!confirm('Delete department "'+(d.name||'')+'"?'))return;
    const url=(ROUTES.destroyDepartment||'').replace('__ID__',String(d.id));
    if(!url){toast('Delete route missing');return;}
    api(url,{method:'DELETE'}).then(function(res){if(Array.isArray(res.departments))departments=res.departments;else departments.splice(i,1);renderPanel();toast('Department deleted');}).catch(function(err){toast(err.message||'Delete failed');});
    return;
  }
  const ih=t.closest('[data-inh]');if(ih){const rid=ih.dataset.inh;const on=!inherited(rid);cur.inh[rid]=on;if(!on){ROWS[rid].f.forEach(f=>{if(f.type==='docmatrix')cur.vals[f.id]=JSON.parse(JSON.stringify(HO().vals[f.id]));else if(cur.vals[f.id]===''||cur.vals[f.id]===undefined)cur.vals[f.id]=HO().vals[f.id];});}mark();renderHead();renderPanel();return;}
  if(t.closest('[data-hobank]')){cur.useHoBank=!cur.useHoBank;mark();renderPanel();ibanCheckAll();return;}
  const cl=t.closest('[data-clear]');if(cl){setVal(cl.dataset.clear,'');renderHead();renderPanel();return;}
  if(t.id==='addBank'){if(cur.banks.length<4){cur.banks.push(blankBank());mark();renderPanel();}else toast('Up to 4 bank accounts');return;}
  const rb=t.closest('[data-rmbank]');if(rb){cur.banks.splice(+rb.dataset.rmbank,1);mark();renderPanel();ibanCheckAll();return;}
  const vw=t.closest('[data-view]');if(vw){viewDoc(cur.docs[+vw.dataset.view]);return;}
  const rn=t.closest('[data-ren]');if(rn){const td=rn.closest('td');const d=cur.docs[+rn.dataset.ren];td.querySelector('.dname').outerHTML=`<input class="in" data-d="n" value="${esc(d.n)}">`;const inp=td.querySelector('input');inp.focus();inp.select();inp.addEventListener('blur',()=>renderPanel(),{once:true});inp.addEventListener('keydown',e=>{if(e.key==='Enter')inp.blur();});return;}
  if(t.dataset&&t.dataset.m!==undefined&&t.type==='checkbox'){}
  const tl=t.closest('[data-tiles]');if(tl){ROWS[tl.dataset.tiles].f.forEach(f=>setVal(f.id,tl.dataset.on==='1'));renderPanel();return;}
  if(t.id==='openDocs'){openDocsModule();return;}
  if(t.id==='addDoc'){cur.docs.push({n:'',key:'',no:'',exp:'',file:'',up:''});mark();renderPanel();panel.querySelector('tbody tr:last-child input')?.focus();return;}
  const rd=t.closest('[data-rmdoc]');if(rd){cur.docs.splice(+rd.dataset.rmdoc,1);mark();renderPanel();return;}
  if(t.id==='resetOrder'){const s=SECS.find(x=>x.k===curSec);order[s.k]=s.rows.map(r=>r.id);renderPanel();}
});
function ibanCheck(t){const m=t.parentElement.querySelector('.ibanmsg');const x=t.value.replace(/\s/g,'');if(!x){m.textContent='';t.classList.remove('bad');return;}const ok=/^AE\d{21}$/.test(x);t.classList.toggle('bad',!ok&&x.length>=23);m.textContent=ok?'Valid UAE IBAN format':x.length<23?`${x.length} of 23 characters`:'A UAE IBAN is AE followed by 21 digits';m.style.color=ok?'var(--ok)':x.length>=23?'var(--bad)':'var(--faint)';}
function ibanCheckAll(){panel.querySelectorAll('[data-b="iban"]').forEach(ibanCheck);}

/* drag to reorder field rows */
panel.addEventListener('pointerdown',ev=>{
  const g=ev.target.closest('.cd-field > .grip');
  if(!g||!CAN_EDIT)return;
  ev.preventDefault();
  ev.stopPropagation();
  const row=g.closest('.cd-field');
  if(!row)return;
  row.classList.add('lift');
  let lastY=ev.clientY;
  const mv=e=>{
    e.preventDefault();
    lastY=e.clientY;
    const others=[...panel.querySelectorAll('.cd-field')].filter(x=>x!==row);
    for(const x of others){
      const b=x.getBoundingClientRect();
      if(lastY>=b.top && lastY<=b.bottom){
        if(lastY < b.top + b.height/2){if(x.previousElementSibling!==row)x.before(row);}
        else if(x.nextElementSibling!==row)x.after(row);
        break;
      }
    }
  };
  const up=()=>{
    row.classList.remove('lift');
    document.removeEventListener('pointermove',mv,true);
    document.removeEventListener('pointerup',up,true);
    document.removeEventListener('pointercancel',up,true);
    order[curSec]=[...panel.querySelectorAll('.cd-field')].map(r=>r.dataset.row).filter(Boolean);
    mark();
    if(ROUTES.fieldOrders){api(ROUTES.fieldOrders,{method:'POST',body:JSON.stringify({field_orders:order})}).catch(function(){});}
  };
  document.addEventListener('pointermove',mv,true);
  document.addEventListener('pointerup',up,true);
  document.addEventListener('pointercancel',up,true);
});

/* save */
let tt;function toast(msg){
  document.querySelectorAll('.cd-toast').forEach(function(el){el.remove();});
  const d=document.createElement('div');
  d.className='cd-toast';
  d.setAttribute('role','status');
  d.textContent=msg;
  const root=document.getElementById('companyDetailsRoot');
  (root||document.body).appendChild(d);
  clearTimeout(tt);
  tt=setTimeout(function(){d.remove();},2200);
}
document.getElementById('save').onclick=async function(){
  if(!CAN_EDIT){toast('You do not have permission to save.');return;}
  const locked=[...dirty].filter(k=>SECS.find(s=>s.k===k)?.warn);
  const adminLocked=[...dirty].filter(k=>ADMIN_SECTIONS.includes(k));
  if(adminLocked.length && !IS_ADMIN){toast('Administrator access required for: '+adminLocked.map(k=>(SECS.find(s=>s.k===k)||{}).t).filter(Boolean).join(', '));return;}
  const payload={
    vals:cur.vals, inh:cur.inh, banks:cur.banks.map(normalizeBank), use_ho_bank:!!cur.useHoBank,
    docs_meta:(cur.docs||[]).filter(d=>!d.key).map(d=>({n:d.n,no:d.no,exp:d.exp,file:d.file,key:d.key||''})),
    field_orders:order, dirty_sections:[...dirty], label:cur.label, place:cur.place, code:cur.code,
    theme:(window.__thmGet?window.__thmGet():(CFG.theme||null)),
    confirm_admin:false
  };
  const doSave=async function(confirm){
    try{
      payload.confirm_admin=!!confirm;
      if(dirty.has('stamp') && ROUTES.saveUserStamp){
        const s=ensureStamp(stampUserId);
        const stampPayload={
          signatory_name:s.signame||'', designation:s.sigdes||'',
          doc_matrix:s.apm||defaultMatrix(),
          other_docs:{ap4:!!s.ap4,ap5:!!s.ap5,ap7:!!s.ap7,ap8:!!s.ap8},
          is_active:s.is_active!==false
        };
        const stampRes=await api(stampUserUrl(ROUTES.saveUserStamp),{method:'POST',body:JSON.stringify(stampPayload)});
        if(stampRes.record){userStamps[String(stampUserId)]=stampRes.record;}
      }
      const res=await api(profileUrl(ROUTES.saveEntity),{method:'POST',body:JSON.stringify(payload)});
      if(res.entity&&res.entity.entities){const id=cur.id;ents=res.entity.entities.map(mapEntity);cur=ents.find(e=>String(e.id)===String(id))||ents[0];}
      if(res.entity&&res.entity.user_stamps){Object.assign(userStamps,res.entity.user_stamps);}
      dirty.clear();render();document.getElementById('saveNote').textContent='One button saves every section.';toast((cur.type==='ho'?'Settings':cur.label)+' saved');
    }catch(err){
      if(err.data&&err.data.needs_confirmation){
        const secs=(err.data.sections||locked).map(k=>(SECS.find(s=>s.k===k)||{}).t).filter(Boolean).join(', ');
        const m=modal(`<div class="cd-card" role="dialog" aria-label="Confirm changes"><div class="hd"><div><h3>Confirm changes</h3><p>You changed ${secs}.</p></div></div><div class="bd"><p style="margin:0;font-size:13px;color:var(--muted)">These settings can affect existing vouchers, VAT returns and reports. The change will be recorded in Activity Logs.</p></div><div class="ft"><button type="button" class="cd-btn ghost" data-close>Cancel</button><button type="button" class="cd-btn" id="cfm">Save changes</button></div></div>`);
        m.querySelector('#cfm').onclick=function(){m.remove();doSave(true);};
        return;
      }
      toast(err.message||'Save failed');
    }
  };
  if(locked.length){
    const m=modal(`<div class="cd-card" role="dialog" aria-label="Confirm changes"><div class="hd"><div><h3>Confirm changes</h3><p>You changed ${locked.map(k=>SECS.find(s=>s.k===k).t).join(', ')}.</p></div></div><div class="bd"><p style="margin:0;font-size:13px;color:var(--muted)">These settings can affect existing vouchers, VAT returns and reports. The change will be recorded in Activity Logs.</p></div><div class="ft"><button type="button" class="cd-btn ghost" data-close>Cancel</button><button type="button" class="cd-btn" id="cfm">Save changes</button></div></div>`);
    m.querySelector('#cfm').onclick=function(){m.remove();doSave(true);};
  } else doSave(false);
};

/* live clock in previews */
setInterval(()=>{if(curSec==='time'&&!panel.querySelector(':focus')&&!document.querySelector('.cd-modal'))panel.querySelectorAll('.pv').forEach((p,i)=>{});const r=panel.querySelector('[data-row="timef"] .pv b');if(r)r.textContent=fmtTime(now(V()),V());},1000);

if(!CAN_EDIT){const b=document.getElementById('save'); if(b){b.disabled=true;b.style.opacity=.55;b.style.cursor='not-allowed';}}
render();
}
if(document.readyState==='loading'){document.addEventListener('DOMContentLoaded',bootCompanyDetails);}else{bootCompanyDetails();}
})();
