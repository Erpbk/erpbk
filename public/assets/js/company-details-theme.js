window.mountTheme=function(root,onChange){
"use strict";
const CSS=`
.thm{--tp:#1f3bff;--tl:#e3e6f0;--tl2:#eef0f6;--tf:#121a33;--tm:#5d6883;--tb:#fff;--tb2:#f7f8fc;font-size:13px;color:var(--tf)}
.thm *{box-sizing:border-box}
.thm .tm-tabs{display:flex;gap:2px;border-bottom:1px solid var(--tl);padding:0 24px;overflow-x:auto}
.thm .tm-tab{all:unset;cursor:pointer;padding:12px 12px;font-weight:600;font-size:13px;color:var(--tm);border-bottom:2px solid transparent;white-space:nowrap}
.thm .tm-tab.on{color:var(--tp);border-color:var(--tp)}
.thm .tm-wrap{display:grid;grid-template-columns:minmax(0,1fr) 290px;gap:20px;padding:18px 24px 22px}
@media(max-width:1100px){.thm .tm-wrap{grid-template-columns:1fr}}
.thm .tm-bar{display:flex;align-items:center;gap:14px;flex-wrap:wrap;border:1px dashed var(--tl);border-radius:12px;padding:12px 14px;background:var(--tb2)}
.thm .tm-bar>b{font-weight:600}
.thm .tm-seg{display:inline-flex;background:var(--tl2);border-radius:9px;padding:3px}
.thm .tm-seg button{all:unset;cursor:pointer;padding:6px 14px;border-radius:7px;font-weight:600;color:var(--tm)}
.thm .tm-seg button.on{background:var(--tb);color:var(--tp);box-shadow:0 1px 3px rgba(18,26,51,.12)}
.thm .tm-vr{width:1px;height:28px;background:var(--tl)}
.thm .tm-cur{width:34px;height:34px;border-radius:9px;border:2px solid var(--tb);box-shadow:0 0 0 1px var(--tl);position:relative;overflow:hidden;cursor:pointer}
.thm .tm-cur input{position:absolute;inset:-6px;width:calc(100% + 12px);height:calc(100% + 12px);opacity:0;cursor:pointer}
.thm .tm-sws{display:flex;gap:7px;flex-wrap:wrap}
.thm .tm-sw{all:unset;cursor:pointer;width:24px;height:24px;border-radius:50%;box-shadow:0 0 0 2px var(--tb),0 0 0 3px var(--tl)}
.thm .tm-sw.on{box-shadow:0 0 0 2px var(--tb),0 0 0 4px var(--tf)}
.thm h5{margin:18px 0 10px;font-size:11px;letter-spacing:.09em;text-transform:uppercase;color:var(--tm)}
.thm .tm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}
.thm .tm-pr{all:unset;box-sizing:border-box;cursor:pointer;border:1.5px solid var(--tl);border-radius:12px;padding:8px;background:var(--tb);display:flex;flex-direction:column;gap:8px}
.thm .tm-pr:hover{border-color:#b9c3f5}.thm .tm-pr.on{border-color:var(--tp);box-shadow:0 0 0 3px rgba(31,59,255,.12)}
.thm .tm-th{display:grid;grid-template-columns:30% 1fr;height:62px;border-radius:7px;overflow:hidden;border:1px solid var(--tl2)}
.thm .tm-th .tm-s{display:flex;flex-direction:column;gap:4px;padding:7px 6px}
.thm .tm-th .tm-s i{height:4px;border-radius:3px;opacity:.55}.thm .tm-th .tm-s i.tm-a{opacity:1}
.thm .tm-th .tm-m{padding:7px;display:flex;flex-direction:column;gap:5px}
.thm .tm-th .tm-m i{height:5px;border-radius:3px}.thm .tm-th .tm-m .tm-row{display:flex;gap:4px;margin-top:auto}.thm .tm-th .tm-m .tm-row i{width:18px;height:5px}
.thm .tm-pn{display:flex;align-items:center;justify-content:space-between;font-weight:600;font-size:12.5px}
.thm .tm-pn .tm-ck{width:17px;height:17px;border-radius:50%;background:var(--tp);color:#fff;display:none;place-items:center;font-size:10px}
.thm .tm-pr.on .tm-pn .tm-ck{display:grid}
.thm .tm-rows{display:grid;gap:8px}
.thm .tm-crow{display:flex;align-items:center;gap:10px;border:1px solid var(--tl);border-radius:10px;padding:8px 10px;background:var(--tb)}
.thm .tm-crow span{flex:1;font-weight:600;font-size:12.5px}
.thm .tm-crow small{display:block;font-weight:400;color:var(--tm);font-size:11px}
.thm .tm-crow .tm-hex{width:84px;height:30px;border:1px solid var(--tl);border-radius:7px;padding:0 8px;font:12px ui-monospace,Menlo,Consolas,monospace;text-transform:uppercase}
.thm .tm-pvt{font-size:11px;letter-spacing:.09em;text-transform:uppercase;color:var(--tm);font-weight:700;margin:0 0 8px}
.thm .tm-pv{--p:#1f3bff;--sb:#ffffff;--st:#334155;--hd:#ffffff;--bg:#f4f5fa;--card:#ffffff;--fg:#121a33;--mut:#64748b;--th:#f3f4f8;--ln:#e8ebf2;border:1px solid var(--tl);border-radius:12px;overflow:hidden;display:grid;grid-template-columns:82px minmax(0,1fr);min-height:330px;background:var(--bg);color:var(--fg);font-size:10.5px}
.thm .tm-pv .tm-ps{background:var(--sb);color:var(--st);padding:10px 8px;border-right:1px solid var(--ln)}
.thm .tm-pv .tm-ps b{display:block;font-size:11px;letter-spacing:.06em;margin-bottom:10px}
.thm .tm-pv .tm-ps small{display:block;font-size:8px;letter-spacing:.08em;opacity:.6;margin:8px 0 4px}
.thm .tm-pv .tm-ps div{padding:5px 6px;border-radius:6px;margin-bottom:2px}
.thm .tm-pv .tm-ps div.on{background:var(--p);color:#fff}
.thm .tm-pv .tm-pm{min-width:0;padding:8px;display:flex;flex-direction:column;gap:8px}
.thm .tm-pv .tm-ph{height:24px;border-radius:7px;background:var(--hd);border:1px solid var(--ln)}
.thm .tm-pv .tm-pc{overflow:hidden;background:var(--card);border:1px solid var(--ln);border-radius:var(--rad,8px);padding:8px}
.thm .tm-pv .tm-pc b{font-size:11.5px}
.thm .tm-pv .tm-btns{display:flex;gap:4px;align-items:center;margin:6px 0}
.thm .tm-pv .tm-b1{background:var(--p);color:#fff;border-radius:var(--rad,5px);padding:3px 7px;font-weight:700}
.thm .tm-pv .tm-b2{background:var(--ln);color:var(--mut);border-radius:var(--rad,5px);padding:3px 7px;font-weight:700}
.thm .tm-pv .tm-lk{color:var(--p);font-weight:700}
.thm .tm-pv table{width:100%;border-collapse:collapse;table-layout:fixed;min-width:0!important;font-size:10px}.thm .tm-pv th,.thm .tm-pv td{background:none;border-bottom:1px solid var(--ln);padding:var(--pad,4px) 5px;text-transform:none;letter-spacing:0}.thm .tm-pv th{background:var(--th);font-size:9px;color:var(--mut)}.thm .tm-pv td,.thm .tm-pv th{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.thm .tm-pv .tm-b1,.thm .tm-pv .tm-b2,.thm .tm-pv .tm-lk{white-space:nowrap;font-size:9px;padding:3px 5px}.thm .tm-pv .tm-btns{flex-wrap:wrap}
.thm .tm-pv th{background:var(--th);text-align:left;padding:4px 5px;font-size:9px}
.thm .tm-pv td{padding:4px 5px;border-bottom:1px solid var(--ln)}
.thm .tm-pv td:first-child{color:var(--p)}
.thm .tm-pv .tm-bd{border-radius:10px;padding:1px 6px;font-weight:700;font-size:8.5px}
.thm .tm-pv .tm-kp{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:6px}
.thm .tm-pv .tm-kp div{border-radius:6px;padding:5px;text-align:center;font-weight:700;font-size:9px}
.thm .tm-opt{display:grid;gap:10px}
.thm .tm-orow{display:flex;align-items:center;gap:12px;border:1px solid var(--tl);border-radius:10px;padding:10px 12px;background:var(--tb);flex-wrap:wrap}
.thm .tm-orow>span:first-child{flex:1;min-width:160px;font-weight:600;font-size:12.5px}.thm .tm-orow small{display:block;font-weight:400;color:var(--tm);font-size:11px}
.thm .tm-tg{position:relative;width:36px;height:20px;border-radius:12px;background:#cbd5e1;cursor:pointer;flex:none;transition:.15s}.thm .tm-tg:after{content:"";position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:50%;background:#fff;transition:.15s}.thm .tm-tg.on{background:var(--tp)}.thm .tm-tg.on:after{left:18px}
.thm .tm-sel{height:32px;border:1px solid var(--tl);border-radius:8px;padding:0 8px;font-size:12.5px;background:#fff}
.thm .tm-txt{height:32px;border:1px solid var(--tl);border-radius:8px;padding:0 10px;font-size:12.5px;min-width:200px;flex:1}
.thm .tm-btn{all:unset;cursor:pointer;padding:7px 14px;border-radius:8px;font-weight:700;font-size:12px;background:var(--tp);color:#fff;white-space:nowrap}.thm .tm-btn.gh{background:#fff;color:var(--tf);border:1px solid var(--tl)}
.thm .tm-ct{display:inline-flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;border-radius:20px;padding:4px 10px}.thm .tm-ct.ok{background:#dcfce7;color:#15803d}.thm .tm-ct.bad{background:#fef3c7;color:#b45309}
.thm .tm-mine{display:flex;gap:8px;flex-wrap:wrap}.thm .tm-mine button{all:unset;cursor:pointer;display:inline-flex;align-items:center;gap:7px;border:1.5px solid var(--tl);border-radius:20px;padding:5px 12px 5px 6px;font-size:12px;font-weight:600}.thm .tm-mine button i{width:18px;height:18px;border-radius:50%}.thm .tm-mine button:hover{border-color:var(--tp)}
.thm textarea.tm-json{width:100%;min-height:110px;border:1px solid var(--tl);border-radius:8px;padding:8px;font:11px ui-monospace,Menlo,Consolas,monospace}
.thm .tm-lg{border:1px solid var(--tl);border-radius:12px;min-height:330px;display:grid;place-items:center;background-size:cover;background-position:center;padding:16px}
.thm .tm-lgc{background:#fff;border-radius:12px;padding:18px;width:100%;max-width:220px;box-shadow:0 10px 30px rgba(0,0,0,.18);display:flex;flex-direction:column;gap:8px;font-size:10.5px;color:#121a33}
.thm .tm-lgc b{font-size:13px}.thm .tm-lgc i{display:block;height:24px;border:1px solid #e3e6f0;border-radius:6px}.thm .tm-lgc em{font-style:normal;color:#fff;border-radius:6px;text-align:center;padding:6px;font-weight:700}
.thm .tm-wm{font-weight:800;font-style:italic;color:var(--tp);font-size:14px;text-align:center}
.thm .tm-foot{display:flex;align-items:center;gap:10px;padding:12px 24px;border-top:1px solid var(--tl);color:var(--tm);font-size:12px}
.thm .tm-foot button{all:unset;cursor:pointer;margin-left:auto;color:#b91c1c;font-weight:700;background:#fee2e2;padding:7px 14px;border-radius:9px}
`;
if(!document.getElementById('thm-css')){const st=document.createElement('style');st.id='thm-css';st.textContent=CSS;document.head.appendChild(st);}
const PRESETS=[
 {n:'FleetValue Blue',p:'#1f3bff',sb:'#ffffff',st:'#334155',dark:0},
 {n:'Express Navy',p:'#1d4ed8',sb:'#0f1e3d',st:'#cbd5e1',dark:0},
 {n:'Royal Purple',p:'#7c6cf0',sb:'#ffffff',st:'#334155',dark:0},
 {n:'Emerald',p:'#059669',sb:'#ffffff',st:'#334155',dark:0},
 {n:'Teal Ocean',p:'#0d9488',sb:'#08332f',st:'#cbd5e1',dark:0},
 {n:'Sunset Orange',p:'#ea580c',sb:'#ffffff',st:'#334155',dark:0},
 {n:'Crimson Red',p:'#dc2626',sb:'#1f1416',st:'#e5c9c9',dark:0},
 {n:'Graphite',p:'#334155',sb:'#1e293b',st:'#cbd5e1',dark:0},
 {n:'Midnight Dark',p:'#7c6cf0',sb:'#1b1e2e',st:'#c7cbe0',dark:1},
 {n:'Ocean Dark',p:'#0ea5e9',sb:'#16202c',st:'#c3d0de',dark:1}];
const SW=['#1f3bff','#1d4ed8','#7c6cf0','#059669','#0d9488','#ea580c','#dc2626','#db2777','#334155','#ca8a04'];
const def=()=>({font:'Public Sans',size:'m',density:'comfortable',radius:'rounded',sbstyle:'full',loginBg:'#1f3bff',loginImg:'',loginTitle:'Welcome to Express Fast Delivery',loginLogo:true,loginLink:true,userPick:false,autoMode:false,pdf:true,scope:'all',mode:'light',preset:0,p:'#1f3bff',sb:'#ffffff',st:'#334155',hd:'#ffffff',hdt:'#121a33',bg:'#f4f5fa',btn2:'#e8ebf2',link:'#1f3bff',card:'#ffffff',th:'#f3f4f8',ln:'#e8ebf2',ba:'#dcfce7',bi:'#fee2e2',bw:'#fef3c7',bn:'#f1f5f9'});
let S=def(),tab='themes',mine=[],json='';
const TABS=[['themes','Themes'],['sidebar','Sidebar'],['header','Header'],['buttons','Buttons & Links'],['tables','Tables & Cards'],['badges','Status Badges'],['layout','Layout & Font'],['login','Login Page'],['options','Options']];
const FIELDS={sidebar:[['sb','Sidebar background','Left menu'],['st','Menu text','Items and icons'],['p','Active item','Selected menu item']],header:[['hd','Header bar','Top bar background'],['hdt','Header text','Titles and icons'],['bg','Page background','Behind cards']],buttons:[['p','Primary button','Save, Add, Upload'],['btn2','Secondary button','Filter, Cancel'],['link','Links','View all, names']],tables:[['card','Card background',''],['th','Table header',''],['ln','Lines and borders','']],badges:[['ba','Active / Valid',''],['bi','Inactive / Expired',''],['bw','Warning / Expiring',''],['bn','Neutral','']]};
const thumb=pr=>{const dk=pr.dark;const bg=dk?'#232838':'#fff',m=dk?'#2d3346':'#f1f3f8';return`<div class="tm-th" style="background:${bg}"><div class="tm-s" style="background:${pr.sb}"><i style="background:${pr.st}"></i><i class="tm-a" style="background:${pr.p}"></i><i style="background:${pr.st}"></i><i style="background:${pr.st}"></i></div><div class="tm-m"><i style="background:${m}"></i><i style="background:${m};width:70%"></i><div class="tm-row"><i style="background:${pr.p}"></i><i style="background:#86efac;opacity:.6"></i><i style="background:#fca5a5;opacity:.6"></i></div></div></div>`;};
const applyDark=()=>{if(S.mode==='dark'){Object.assign(S,{hd:'#1b1f2e',hdt:'#e7eaf6',bg:'#11141f',card:'#1b1f2e',th:'#232838',ln:'#2c3245',btn2:'#2c3245'});}else{Object.assign(S,{hd:'#ffffff',hdt:'#121a33',bg:'#f4f5fa',card:'#ffffff',th:'#f3f4f8',ln:'#e8ebf2',btn2:'#e8ebf2'});}};
const fg=()=>S.mode==='dark'?'#e7eaf6':'#121a33';
const lum=h=>{const c=[1,3,5].map(i=>parseInt(h.substr(i,2),16)/255).map(v=>v<=.03928?v/12.92:((v+.055)/1.055)**2.4);return .2126*c[0]+.7152*c[1]+.0722*c[2];};
const ratio=(a,b)=>{const x=lum(a),y=lum(b);return (Math.max(x,y)+.05)/(Math.min(x,y)+.05);};
const contrast=()=>{const r1=ratio('#ffffff',S.p),r2=ratio(S.st,S.sb);const ok=r1>=3&&r2>=3;return`<span class="tm-ct ${ok?'ok':'bad'}" title="Button text ${r1.toFixed(1)}:1 \u00B7 Sidebar text ${r2.toFixed(1)}:1">${ok?'\u2713 Easy to read':'\u26A0 Some text may be hard to read'}</span>`;};
const seg=(k,opts)=>`<div class="tm-seg">${opts.map(([v,t])=>`<button type="button" data-set="${k}" data-v="${v}" class="${S[k]===v?'on':''}">${t}</button>`).join('')}</div>`;
const tg=k=>`<span class="tm-tg${S[k]?' on':''}" data-tg="${k}" role="switch" aria-checked="${!!S[k]}" tabindex="0"></span>`;
const orow=(l,h,ctl)=>`<div class="tm-orow"><span>${l}${h?`<small>${h}</small>`:''}</span>${ctl}</div>`;
const FONTS=['Public Sans','Inter','Roboto','Poppins','Open Sans','Noto Sans Arabic'];
function body(){
 if(tab==='layout')return`<div class="tm-opt">${orow('Font','Used across the whole software',`<select class="tm-sel" data-sel="font">${FONTS.map(f=>`<option${S.font===f?' selected':''}>${f}</option>`).join('')}</select>`)}${orow('Text size','Bigger text is easier to read on large screens',seg('size',[['s','Small'],['m','Medium'],['l','Large']]))}${orow('Density','Compact shows more rows on one screen',seg('density',[['compact','Compact'],['comfortable','Comfortable']]))}${orow('Corners','Buttons, cards and inputs',seg('radius',[['square','Square'],['rounded','Rounded'],['pill','Pill']]))}${orow('Sidebar style','Compact keeps only the icons',seg('sbstyle',[['full','Full'],['compact','Icons only']]))}</div>`;
 if(tab==='login')return`<div class="tm-opt">${orow('Background color','',`<label class="tm-cur" style="background:${S.loginBg};width:30px;height:30px"><input type="color" value="${S.loginBg}" data-k="loginBg"></label>`)}${orow('Background image','Office or fleet photo, 1920\u00D71080',`<label class="tm-btn gh">${S.loginImg?'Replace image':'Choose image'}<input type="file" accept="image/*" data-login-img hidden></label>${S.loginImg?'<button type="button" class="tm-btn gh" data-login-clear>Remove</button>':''}`)}${orow('Welcome text','Shown above the sign-in form',`<input class="tm-txt" data-txt="loginTitle" value="${S.loginTitle.replace(/"/g,'&quot;')}">`)}${orow('Show company logo','',tg('loginLogo'))}${orow('Show \u201CForgot password\u201D link','',tg('loginLink'))}</div>`;
 if(tab==='options')return`<div class="tm-opt">${orow('Follow device light / dark','Switches mode automatically with the user\u2019s device',tg('autoMode'))}${orow('Let each user choose a theme','Users can pick their own colors in Profile',tg('userPick'))}${orow('Use main color on invoices and PDFs','Headers and lines on vouchers, invoices, agreements',tg('pdf'))}${orow('Apply to','',`<select class="tm-sel" data-sel="scope"><option value="all"${S.scope==='all'?' selected':''}>All branches</option><option value="branch"${S.scope==='branch'?' selected':''}>Each branch can have its own</option></select>`)}
 ${orow('Save as my theme','Keep these colors as a ready-made theme',`<input class="tm-txt" id="tmName" placeholder="Theme name, e.g. Express Ramadan"><button type="button" class="tm-btn" data-save-mine>Save</button>`)}
 ${orow('Export / import','Copy the theme to another company or branch',`<button type="button" class="tm-btn gh" data-export>Export</button><button type="button" class="tm-btn gh" data-import>Import</button>`)}${json!==''?`<textarea class="tm-json" id="tmJson" spellcheck="false">${json}</textarea><div style="display:flex;gap:8px;justify-content:flex-end"><button type="button" class="tm-btn gh" data-json-close>Close</button><button type="button" class="tm-btn" data-json-apply>Apply this theme</button></div>`:''}</div>`;
 if(tab==='themes')return`<div class="tm-bar"><b>Mode</b><div class="tm-seg"><button type="button" data-mode="light" class="${S.mode==='light'?'on':''}">\u2600 Light</button><button type="button" data-mode="dark" class="${S.mode==='dark'?'on':''}">\u263E Dark</button><button type="button" data-mode="auto" class="${S.autoMode?'on':''}">\u25D0 Auto</button></div><span class="tm-vr"></span><b>Main color</b><label class="tm-cur" style="background:${S.p}" title="Pick any color"><input type="color" value="${S.p}" data-k="p"></label><div class="tm-sws">${SW.map(c=>`<button type="button" class="tm-sw${c===S.p?' on':''}" style="background:${c}" data-sw="${c}" aria-label="${c}"></button>`).join('')}</div>${contrast()}</div>${mine.length?`<h5>My themes</h5><div class="tm-mine">${mine.map((m,i)=>`<button type="button" data-mine="${i}"><i style="background:${m.p}"></i>${m.name}</button>`).join('')}</div>`:''}
 <h5>Ready-made themes</h5><div class="tm-grid">${PRESETS.map((pr,i)=>`<button type="button" class="tm-pr${S.preset===i?' on':''}" data-pr="${i}">${thumb(pr)}<span class="tm-pn">${pr.n}<span class="tm-ck">\u2713</span></span></button>`).join('')}</div>`;
 return`<div class="tm-rows">${FIELDS[tab].map(([k,l,h])=>`<div class="tm-crow"><span>${l}${h?`<small>${h}</small>`:''}</span><label class="tm-cur" style="background:${S[k]};width:30px;height:30px"><input type="color" value="${S[k]}" data-k="${k}"></label><input class="tm-hex" value="${S[k]}" data-hex="${k}" maxlength="7"></div>`).join('')}</div>`;
}
function preview(){
 if(tab==='login')return`<p class="tm-pvt">Login page preview</p><div class="tm-lg" style="background-color:${S.loginBg};${S.loginImg?`background-image:url(${S.loginImg})`:''}"><div class="tm-lgc">${S.loginLogo?'<div class="tm-wm">EXPRESS</div>':''}<b>${S.loginTitle.replace(/</g,'&lt;')}</b><span>Email</span><i></i><span>Password</span><i></i><em style="background:${S.p}">Sign in</em>${S.loginLink?`<span style="color:${S.link};text-align:center;font-weight:600">Forgot password?</span>`:''}</div></div>`;
 const fs={s:.9,m:1,l:1.12}[S.size],rad={square:'0px',rounded:'6px',pill:'14px'}[S.radius],pad=S.density==='compact'?'2px':'5px';const v=`--p:${S.p};--sb:${S.sb};--st:${S.st};--hd:${S.hd};--bg:${S.bg};--card:${S.card};--fg:${fg()};--mut:${S.mode==='dark'?'#9aa4c0':'#64748b'};--th:${S.th};--ln:${S.ln}`;
 return`<p class="tm-pvt">Live preview</p><div class="tm-pv" style="${v};font-family:'${S.font}',system-ui,sans-serif;zoom:${fs};--rad:${rad};--pad:${pad};grid-template-columns:${S.sbstyle==='compact'?'40px':'82px'} minmax(0,1fr)">${S.sbstyle==='compact'?`<div class="tm-ps"><b>E</b><div>\u25A6</div><div>\u263A</div><div class="on">\u2261</div><div>\u25F7</div><div>\u26A1</div></div>`:`<div class="tm-ps"><b>EXPRESS</b><div>Dashboard</div><small>FLEET</small><div>Riders</div><div class="on">Riders List</div><div>Attendance</div><div>Bikes</div></div>`}<div class="tm-pm"><div class="tm-ph" style="color:${S.hdt}"></div><div class="tm-pc"><b>Riders</b><div class="tm-btns"><span class="tm-b1">+ Add Rider</span><span class="tm-b2" style="background:${S.btn2}">Filter</span><span class="tm-lk" style="color:${S.link}">View all \u2192</span></div><table><tr><th>NAME</th><th>STATUS</th></tr><tr><td style="color:${S.link}">Ahmed Khan</td><td><span class="tm-bd" style="background:${S.ba};color:#15803d">Active</span></td></tr><tr><td style="color:${S.link}">Ravi Sharma</td><td><span class="tm-bd" style="background:${S.bi};color:#b91c1c">Absconded</span></td></tr><tr><td style="color:${S.link}">Peter Otieno</td><td><span class="tm-bd" style="background:${S.bw};color:#b45309">Travel Ban</span></td></tr><tr><td style="color:${S.link}">Samuel M.</td><td><span class="tm-bd" style="background:${S.bn};color:#64748b">Cyclist</span></td></tr></table><div class="tm-kp"><div style="background:${S.ba};color:#15803d">ACTIVE<br>68</div><div style="background:${S.bi};color:#b91c1c">INACTIVE<br>8</div></div></div></div></div>`;}
function render(){root.innerHTML=`<div class="thm"><div class="tm-tabs">${TABS.map(([k,t])=>`<button type="button" class="tm-tab${k===tab?' on':''}" data-tab="${k}">${t}</button>`).join('')}</div><div class="tm-wrap"><div>${body()}</div><div>${preview()}</div></div><div class="tm-foot"><span>${S.preset>=0?PRESETS[S.preset].n:'Custom colors'} \u00B7 ${S.autoMode?'Auto':S.mode==='dark'?'Dark':'Light'} mode \u00B7 ${S.font} \u00B7 ${{s:'Small',m:'Medium',l:'Large'}[S.size]} text</span><button type="button" data-reset>\u21BA Reset to default</button></div></div>`;}
const changed=()=>{render();onChange&&onChange(S);};
root.addEventListener('click',e=>{const t=e.target;
 const tb=t.closest('[data-tab]');if(tb){tab=tb.dataset.tab;render();return;}
 const md=t.closest('[data-mode]');if(md){if(md.dataset.mode==='auto'){S.autoMode=!S.autoMode;changed();return;}S.autoMode=false;S.mode=md.dataset.mode;applyDark();S.preset=-1;changed();return;}
 const st=t.closest('[data-set]');if(st){S[st.dataset.set]=st.dataset.v;changed();return;}
 const tgl=t.closest('[data-tg]');if(tgl){S[tgl.dataset.tg]=!S[tgl.dataset.tg];changed();return;}
 if(t.closest('[data-login-clear]')){S.loginImg='';changed();return;}
 if(t.closest('[data-save-mine]')){const n=root.querySelector('#tmName').value.trim()||('My theme '+(mine.length+1));mine.push({...S,name:n});tab='themes';changed();return;}
 const mi=t.closest('[data-mine]');if(mi){const m=mine[+mi.dataset.mine];S={...m};delete S.name;S.preset=-1;changed();return;}
 if(t.closest('[data-export]')){json=JSON.stringify({...S,loginImg:''},null,1);render();return;}
 if(t.closest('[data-import]')){json='{ }';render();root.querySelector('#tmJson').select();return;}
 if(t.closest('[data-json-close]')){json='';render();return;}
 if(t.closest('[data-json-apply]')){try{const o=JSON.parse(root.querySelector('#tmJson').value);S={...def(),...o,preset:-1};json='';changed();}catch(err){root.querySelector('#tmJson').style.borderColor='#dc2626';}return;}
 const sw=t.closest('[data-sw]');if(sw){S.p=S.link=sw.dataset.sw;S.preset=-1;changed();return;}
 const pr=t.closest('[data-pr]');if(pr){const P=PRESETS[+pr.dataset.pr];S.preset=+pr.dataset.pr;S.p=S.link=P.p;S.sb=P.sb;S.st=P.st;S.mode=P.dark?'dark':'light';applyDark();changed();return;}
 if(t.closest('[data-reset]')){S=def();changed();}
});
root.addEventListener('input',e=>{const t=e.target;if(t.dataset.txt){S[t.dataset.txt]=t.value;root.querySelector('.tm-wrap>div:last-child').innerHTML=preview();return;}if(t.dataset.k){S[t.dataset.k]=t.value;if(t.dataset.k==='p')S.link=t.value;S.preset=-1;const c=t.closest('.tm-cur');if(c)c.style.background=t.value;const h=root.querySelector(`[data-hex="${t.dataset.k}"]`);if(h)h.value=t.value;root.querySelector('.tm-wrap>div:last-child').innerHTML=preview();onChange&&onChange(S);}});
root.addEventListener('keydown',e=>{const t=e.target.closest('[data-tg]');if(t&&(e.key==='Enter'||e.key===' ')){e.preventDefault();t.click();}});
root.addEventListener('change',e=>{const t=e.target;if(t.dataset.sel){S[t.dataset.sel]=t.value;changed();return;}if(t.dataset.txt){S[t.dataset.txt]=t.value;onChange&&onChange(S);return;}if(t.hasAttribute('data-login-img')&&t.files[0]){const r=new FileReader();r.onload=()=>{S.loginImg=r.result;changed();};r.readAsDataURL(t.files[0]);return;}if(t.dataset.k){changed();}if(t.dataset.hex&&/^#[0-9a-f]{6}$/i.test(t.value)){S[t.dataset.hex]=t.value;S.preset=-1;changed();}});
render();
return{get:()=>S};
};
