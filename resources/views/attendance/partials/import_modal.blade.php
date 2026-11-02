@php
  $refType = $refType ?? $userType ?? request('ref_type', request('user_type', 'employee'));
  $personLabel = $refType === 'rider' ? 'rider' : 'employee';
  $personTitle = $refType === 'rider' ? 'Rider' : 'Employee';
  $importPeople = $importPeople ?? collect();
  $openImport = $openImport ?? false;
  $attImportPeoplePayload = $importPeople->map(fn ($p) => [
    'id' => $p->id,
    'name' => $p->name,
    'code' => $p->employee_id ?? $p->rider_id ?? '',
  ])->values();
  $attImportConfigPayload = [
    'url' => route('attendance.import'),
    'template' => route('attendance.import.template', ['ref_type' => $refType]),
    'csrf' => csrf_token(),
    'refType' => $refType,
    'personLabel' => $personLabel,
    'open' => (bool) $openImport,
  ];
@endphp
{{-- Import Attendance — HTML demo 3-step modal (Bootstrap-safe att-dlg classes) --}}
<div class="att-demo att-dlg-ov" id="attImpOv" aria-hidden="true">
  <div class="att-dlg wide" role="dialog" aria-modal="true" aria-labelledby="attImpTitle">
    <div class="hd">
      <h3 id="attImpTitle">Import Attendance</h3>
      <button type="button" class="x" data-imp-close aria-label="Close">&times;</button>
    </div>
    <div class="bd">
      <div class="step">
        <span class="n">1</span>
        <div style="flex:1">
          <h4>Download template</h4>
          <p>CSV template (opens in Excel) with every {{ $personLabel }} and today's date filled in.</p>
          <a class="btn outline" id="attTplBtn" href="{{ route('attendance.import.template', ['ref_type' => $refType]) }}">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5"></path><path d="M12 15V3"></path></svg>
            Download template (.csv)
          </a>
        </div>
      </div>

      <div class="step">
        <span class="n">2</span>
        <div style="flex:1">
          <h4>Upload filled file</h4>
          <p>Columns: {{ $personTitle }} Name, Date (YYYY-MM-DD), Status (Present / Late / Absent / Leave / Half Day / Weekend), Check In, Check Out.</p>
          <label class="drop" id="attImpDrop" for="attImpFile">
            <input type="file" id="attImpFile" accept=".csv,text/csv" hidden>
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3730D9" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5"></path><path d="M12 3v12"></path></svg>
            <div style="margin-top:6px"><b>Click to upload</b> or drag &amp; drop your CSV file here</div>
            <div id="attImpFname" style="margin-top:4px;font-size:12px"></div>
          </label>
        </div>
      </div>

      <div class="step" id="attImpStep3" hidden>
        <span class="n">3</span>
        <div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:10px">
          <h4>Check &amp; import</h4>
          <div class="sum" id="attImpSum"></div>
          <div class="ptbl">
            <table>
              <thead>
                <tr>
                  <th>Row</th>
                  <th>{{ $personTitle }}</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>In</th>
                  <th>Out</th>
                  <th>Result</th>
                </tr>
              </thead>
              <tbody id="attImpPrevBody"></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="ft">
      <button type="button" class="btn reset" data-imp-close>Cancel</button>
      <button type="button" class="btn blue" id="attImpGo" disabled>Import records</button>
    </div>
  </div>
</div>
<div class="att-demo"><div class="toast" id="attImpToast"></div></div>

<script>
window.__attImportPeople = @json($attImportPeoplePayload);
window.__attImportConfig = @json($attImportConfigPayload);

(function () {
  if (window.__attImportBound) return;
  window.__attImportBound = true;

  const cfg = window.__attImportConfig || {};
  const people = window.__attImportPeople || [];
  const ov = document.getElementById('attImpOv');
  const drop = document.getElementById('attImpDrop');
  const fileIn = document.getElementById('attImpFile');
  const go = document.getElementById('attImpGo');
  const step3 = document.getElementById('attImpStep3');
  const tbody = document.getElementById('attImpPrevBody');
  const sum = document.getElementById('attImpSum');
  const fname = document.getElementById('attImpFname');
  let pendingFile = null;

  const CHIP = {
    P: 'Present', L: 'Late', A: 'Absent', V: 'Leave', H: 'Half Day', W: 'Weekend',
  };
  const SMAP = {
    present: 'P', p: 'P',
    late: 'L', 'late in': 'L', l: 'L',
    absent: 'A', a: 'A',
    leave: 'V', v: 'V', 'on leave': 'V',
    'half day': 'H', half: 'H', h: 'H', 'half-day': 'H', halfday: 'H',
    weekend: 'W', off: 'W', w: 'W',
  };

  const esc = (s) => String(s ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

  const toast = (msg) => {
    const el = document.getElementById('attImpToast') || document.getElementById('attToast');
    if (!el) return;
    el.textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2800);
  };

  const openImp = () => {
    if (!ov) return;
    if (ov.parentElement !== document.body) document.body.appendChild(ov);
    ov.classList.add('show');
    ov.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  };
  const resetImp = () => {
    pendingFile = null;
    if (fileIn) fileIn.value = '';
    if (fname) fname.textContent = '';
    if (step3) step3.hidden = true;
    if (tbody) tbody.innerHTML = '';
    if (sum) sum.innerHTML = '';
    if (go) { go.disabled = true; go.textContent = 'Import records'; }
  };
  const closeImp = () => {
    ov?.classList.remove('show');
    ov?.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
    resetImp();
  };

  document.getElementById('attImpBtn')?.addEventListener('click', openImp);
  ov?.querySelectorAll('[data-imp-close]').forEach((b) => b.addEventListener('click', closeImp));
  ov?.addEventListener('click', (e) => { if (e.target === ov) closeImp(); });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && ov?.classList.contains('show')) closeImp();
  });
  if (cfg.open) openImp();

  const byName = {};
  people.forEach((p) => {
    byName[String(p.name || '').toLowerCase().replace(/\s+/g, ' ')] = p;
    const code = String(p.code || '').toLowerCase().trim();
    if (code) byName[code] = p;
  });

  const normTime = (t) => {
    t = String(t || '').trim();
    if (!t) return '';
    const m = t.match(/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])?$/);
    if (!m) return null;
    let h = +m[1];
    if (m[3]) {
      const pm = /p/i.test(m[3]);
      if (pm && h < 12) h += 12;
      if (!pm && h === 12) h = 0;
    }
    if (h > 23) return null;
    return String(h).padStart(2, '0') + ':' + m[2];
  };

  const parseCSV = (text) => {
    const rows = [];
    let row = [], f = '', q = false;
    text = String(text || '').replace(/^\uFEFF/, '');
    for (let i = 0; i < text.length; i++) {
      const c = text[i];
      if (q) {
        if (c === '"') {
          if (text[i + 1] === '"') { f += '"'; i++; }
          else q = false;
        } else f += c;
      } else if (c === '"') q = true;
      else if (c === ',') { row.push(f); f = ''; }
      else if (c === '\n' || c === '\r') {
        if (c === '\r' && text[i + 1] === '\n') i++;
        row.push(f); rows.push(row); row = []; f = '';
      } else f += c;
    }
    if (f || row.length) { row.push(f); rows.push(row); }
    return rows.filter((r) => r.some((x) => String(x).trim()));
  };

  const handleFile = (file) => {
    if (!file) return;
    pendingFile = file;
    if (fname) fname.textContent = file.name;
    const rd = new FileReader();
    rd.onload = () => {
      const rows = parseCSV(rd.result);
      const H = (rows.shift() || []).map((h) => String(h).trim().toLowerCase());
      const ix = (names) => {
        for (const n of names) {
          const i = H.indexOf(n);
          if (i >= 0) return i;
        }
        return -1;
      };
      const iN = ix(['employee name', 'rider name', 'name', 'employee', 'rider']);
      const iD = ix(['date']);
      const iS = ix(['status']);
      const iI = ix(['check in', 'check_in', 'in', 'time in']);
      const iO = ix(['check out', 'check_out', 'out', 'time out']);
      if (iN < 0 || iD < 0) {
        toast('CSV needs Name and Date columns');
        return;
      }

      let ok = 0, bad = 0, skip = 0;
      const cnt = {};
      const html = rows.map((r, k) => {
        const nm = String(r[iN] ?? '').trim();
        const ds = String(r[iD] ?? '').trim();
        const s = String(r[iS] ?? '').trim();
        const ci = String(r[iI] ?? '').trim();
        const co = String(r[iO] ?? '').trim();
        const e = byName[nm.toLowerCase().replace(/\s+/g, ' ')];
        const code = SMAP[s.toLowerCase()];
        const tin = normTime(ci);
        const tout = normTime(co);
        let res, rc = '#B42318';

        if (!s) {
          skip++; res = 'Skipped (no status)'; rc = '#5B6478';
        } else if (!e) {
          bad++; res = (cfg.personLabel === 'rider' ? 'Rider' : 'Employee') + ' not found';
        } else if (!/^\d{4}-\d{2}-\d{2}$/.test(ds)) {
          bad++; res = 'Date must be YYYY-MM-DD';
        } else if (!code) {
          bad++; res = 'Unknown status';
        } else if (tin === null || tout === null) {
          bad++; res = 'Wrong time format';
        } else {
          ok++; cnt[code] = (cnt[code] || 0) + 1; res = 'Ready'; rc = '#17663A';
        }

        return `<tr><td>${k + 2}</td><td>${esc(e ? e.name : nm)}</td><td>${esc(ds)}</td><td>${code ? CHIP[code] : esc(s)}</td><td>${esc(ci)}</td><td>${esc(co)}</td><td style="color:${rc};font-weight:600">${res}</td></tr>`;
      }).join('');

      if (tbody) {
        tbody.innerHTML = html || '<tr><td colspan="7" style="text-align:center;padding:16px;color:#5B6478">No rows found</td></tr>';
      }
      if (sum) {
        sum.innerHTML =
          `<span style="background:#E7F6EC;color:#17663A">${ok} ready</span>` +
          (bad ? `<span style="background:#FDECEC;color:#B42318">${bad} with errors</span>` : '') +
          (skip ? `<span style="background:#F1F3F8;color:#5B6478">${skip} skipped</span>` : '') +
          Object.keys(cnt).map((k) => `<span style="background:#F7F8FB;color:#3A4256">${CHIP[k]}: ${cnt[k]}</span>`).join('');
      }
      if (step3) step3.hidden = false;
      if (go) {
        go.disabled = !ok;
        go.textContent = ok ? `Import ${ok} records` : 'Import records';
      }
    };
    rd.readAsText(file);
  };

  ['dragenter', 'dragover'].forEach((ev) => drop?.addEventListener(ev, (e) => {
    e.preventDefault(); drop.classList.add('over');
  }));
  ['dragleave', 'drop'].forEach((ev) => drop?.addEventListener(ev, (e) => {
    e.preventDefault(); drop.classList.remove('over');
  }));
  drop?.addEventListener('drop', (e) => {
    const f = e.dataTransfer?.files?.[0];
    if (f) handleFile(f);
  });
  fileIn?.addEventListener('change', (e) => {
    const f = e.target.files?.[0];
    if (f) handleFile(f);
  });

  go?.addEventListener('click', async () => {
    if (!pendingFile || go.disabled) return;
    go.disabled = true;
    go.textContent = 'Importing…';
    const fd = new FormData();
    fd.append('file', pendingFile);
    fd.append('ref_type', cfg.refType || 'employee');
    fd.append('_token', cfg.csrf || '');
    try {
      const res = await fetch(cfg.url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': cfg.csrf || '',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: fd,
      });
      const data = await res.json();
      if (!res.ok || !data.success) throw new Error(data.message || 'Import failed');
      toast(data.message || 'Import finished');
      closeImp();
      setTimeout(() => { window.location.href = data.redirect || window.location.href; }, 600);
    } catch (err) {
      toast(err.message || 'Import failed');
      go.disabled = false;
      go.textContent = 'Import records';
    }
  });
})();
</script>
