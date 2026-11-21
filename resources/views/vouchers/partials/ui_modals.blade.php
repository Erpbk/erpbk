{{-- Bulk delete confirm --}}
<div class="vc-modal-ov" id="vcDelM" hidden>
  <div class="vc-mbox" role="dialog" aria-labelledby="vcDelT">
    <div class="vc-mhd">
      <div class="vc-mic">🗑</div>
      <div style="flex:1">
        <h5 id="vcDelT">Delete vouchers?</h5>
        <small>A delete request will be submitted for each selected voucher.</small>
      </div>
      <button type="button" class="x" data-vc-mclose aria-label="Close">×</button>
    </div>
    <div id="vcDelL" style="max-height:180px;overflow:auto;border:1px solid #e2e8f0;border-radius:10px;margin-bottom:12px"></div>
    <label for="vcDelR" style="font-size:12.5px;font-weight:600;color:#1e4b8f">Delete reason</label>
    <textarea id="vcDelR" placeholder="Why should these vouchers be deleted?"></textarea>
    <div class="vc-mft">
      <button type="button" class="vc-ireset" data-vc-mclose>Cancel</button>
      <button type="button" class="vc-igo" id="vcDelGo" style="min-width:140px;background:#dc2626">Delete</button>
    </div>
  </div>
</div>

{{-- Import voucher — Excel-like review grid (demo layout) --}}
@php
  $vcImportAccounts = \App\Models\Accounts::bankAndCashDropdown();
  $vcImportDraftKey = 'voucher_import_draft.'.(string) (request()->route('company_slug') ?? session('company_slug') ?? 'default').'.'.(int) (auth()->id() ?? 0);
  $vcImportDraftRaw = session($vcImportDraftKey);
  $vcImportDraft = (is_array($vcImportDraftRaw) && ! empty($vcImportDraftRaw['rows'])) ? [
    'filename' => $vcImportDraftRaw['filename'] ?? null,
    'payment_from' => $vcImportDraftRaw['payment_from'] ?? null,
    'rows' => $vcImportDraftRaw['rows'] ?? [],
    'counts' => $vcImportDraftRaw['counts'] ?? ['ok' => 0, 'err' => 0, 'warn' => 0],
    'saved_at' => $vcImportDraftRaw['saved_at'] ?? null,
  ] : null;
@endphp
<div class="vc-modal-ov" id="vcImpM" hidden
     data-preview-url="{{ route('vouchers.import.preview') }}"
     data-validate-url="{{ route('vouchers.import.validate') }}"
     data-commit-url="{{ route('vouchers.import.commit') }}"
     data-draft-url="{{ route('vouchers.import.draft') }}"
     data-draft-save-url="{{ route('vouchers.import.draft.save') }}"
     data-draft-clear-url="{{ route('vouchers.import.draft.destroy') }}"
     data-balance-url="{{ route('vouchers.import.balance') }}"
     data-csrf="{{ csrf_token() }}"
     data-has-draft="{{ $vcImportDraft ? '1' : '0' }}">
  <script type="application/json" id="vcImpDraftJson">@json($vcImportDraft)</script>
  <div class="vc-mbox ibox" role="dialog" aria-labelledby="vcImpTitle">
    <div class="vc-ihd">
      <h4 id="vcImpTitle">Import Voucher</h4>
      <small>Create payment lines for many riders at once from an Excel file</small>
      <button type="button" class="x" data-vc-mclose aria-label="Close">×</button>
    </div>
    <div class="vc-ibody" id="vcImpBody">
      <div class="vc-drop-ov" id="vcImpDropOv" hidden>
        <div class="vc-drop-card">
          <span class="vc-drop-xls">XLS</span>
          <b>Drop the file here</b>
          <small>Excel (.xlsx) or CSV</small>
        </div>
      </div>

      <div class="vc-stp" id="vcImpSteps">
        <div class="s on" data-s="1"><span>1</span>Upload file</div><i></i>
        <div class="s" data-s="2"><span>2</span>Review rows</div><i></i>
        <div class="s" data-s="3"><span>3</span>Import</div>
      </div>

      <div class="vc-icards">
        <div class="vc-icard">
          <span class="ic">⬇</span>
          <b>Sample file</b>
          <small>Use this format</small>
          <a class="vc-smp" href="{{ url('sample/voucher_debit_sample.xlsx') }}" download>⬇ Download Sample File</a>
        </div>
        <div class="vc-icard">
          <span class="ic">🏦</span>
          <b>Credit account</b>
          <small>Every line is paid from this account</small>
          <div style="width:100%;margin-top:4px">
            <select class="form-control form-select select2" id="vc_payment_from" data-placeholder="Select credit account" style="width:100%;">
              @foreach($vcImportAccounts as $accId => $accLabel)
                <option value="{{ $accId }}">{{ $accLabel }}</option>
              @endforeach
            </select>
          </div>
          <div class="vc-ibal" id="vcImpBal" hidden></div>
        </div>
        <div class="vc-icard up">
          <span class="ic">⇪</span>
          <b>Upload</b>
          <small>Excel (.xlsx) or CSV</small>
          <label class="vc-idrop" id="vcImpDrop">
            <span>⇪</span>
            <span>Drag the file here or <u>browse</u></span>
            <span class="fn" id="vcImpFn">No file chosen</span>
            <input type="file" id="vcImpFile" accept=".xlsx,.xls,.csv,text/csv">
          </label>
          <div id="vcImpFerr" class="vc-iferr" hidden></div>
        </div>
      </div>

      <div class="vc-isum" id="vcImpSum" hidden>
        <div><span>Total rows</span><b id="vcImpSumAll">0</b></div>
        <div class="ok"><span>Ready</span><b id="vcImpSumOk">0</b></div>
        <div class="bad"><span>Errors</span><b id="vcImpSumErr">0</b></div>
        <div class="amt"><span>Amount (ready rows)</span><b id="vcImpSumAmt">0.00</b></div>
        <div class="warn"><span>Warnings</span><b id="vcImpSumWarn">0</b></div>
      </div>

      <div class="vc-iempty" id="vcImpEmpty">
        <div class="vc-iempty-ico">▦</div>
        <b>No file selected yet</b>
        <span>Choose an Excel or CSV file above — every row will show here so you can edit like a spreadsheet before importing.</span>
      </div>

      <div class="vc-iprev" id="vcImpPrev" hidden>
        <div class="vc-itool">
          <div class="vc-ifilts">
            <button type="button" class="on" data-f="all">All</button>
            <button type="button" data-f="ok">Ready (<b id="vcImpNOk">0</b>)</button>
            <button type="button" data-f="err">Errors (<b id="vcImpNErr">0</b>)</button>
          </div>
          <input type="search" id="vcImpQ" placeholder="Search rider ID, name, particulars, amount…" aria-label="Search import rows">
          <button type="button" class="vc-itool-btn" id="vcImpErrXls" title="Download error rows as CSV">Errors (Excel)</button>
          <span class="vc-irestored" id="vcImpRestored" hidden></span>
          <button type="button" class="vc-itool-btn ghost" id="vcImpDiscard">Discard</button>
          <button type="button" class="vc-itool-btn ghost" id="vcImpResetBtn" title="Clear saved import cache">Reset</button>
        </div>
        <div class="vc-iscroll">
          <table class="vc-igrid" id="vcImpTable">
            <thead>
              <tr class="letters">
                <th class="cn"></th>
                <th>A</th>
                <th>B</th>
                <th>C</th>
                <th>D</th>
                <th>E</th>
                <th>F</th>
                <th class="ro"></th>
                <th class="cn"></th>
              </tr>
              <tr>
                <th class="cn">#</th>
                <th>Date</th>
                <th>Billing Month</th>
                <th>Rider ID</th>
                <th class="ro">Rider</th>
                <th>Particulars</th>
                <th>Paid</th>
                <th class="ro">Check</th>
                <th class="cn"></th>
              </tr>
            </thead>
            <tbody id="vcImpTb"></tbody>
            <tfoot id="vcImpTf"></tfoot>
          </table>
        </div>
        <div class="vc-ixb">
          <button type="button" id="vcImpAdd">＋ Add row</button>
          <span>Click any cell to edit · Enter / Tab to move · Paste from Excel</span>
        </div>
      </div>

      <div class="vc-ift2">
        <button type="button" class="vc-igo" id="vcImpGo" disabled>Start Import</button>
      </div>
    </div>
  </div>
</div>

{{-- Row action menu (positioned by JS) --}}
<div class="vc-menu vc-rmenu" id="vcRowMenu" hidden></div>

{{-- Attachment document viewer (matches Payment/Visa doc preview chrome) --}}
{{-- Inline critical styles so a stale/cached vouchers-demo.css on server cannot hide the modal --}}
<style id="vc-docv-critical">
  .vc-docv.open,.vc-docv.open[hidden]{display:flex!important;visibility:visible!important;opacity:1!important;position:fixed!important;inset:0!important;z-index:200000!important;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.55);box-sizing:border-box}
  .vc-docv-panel{width:min(1120px,96vw);height:min(920px,94vh);max-height:94vh;background:#fff;border-radius:14px;box-shadow:0 24px 64px rgba(15,23,42,.28);display:flex;flex-direction:column;overflow:hidden;position:relative}
  .vc-docv-hd{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #e2e8f0;background:#fff;flex:none}
  .vc-docv-hd-l{display:flex;align-items:center;gap:10px;min-width:0}
  .vc-docv-ico{width:36px;height:36px;border-radius:9px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;flex:none}
  .vc-docv-titles{min-width:0}
  .vc-docv-name{font:700 14px/1.25 Inter,system-ui,sans-serif;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .vc-docv-meta{margin-top:2px;font:500 11.5px/1.35 Inter,system-ui,sans-serif;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .vc-docv-hd-r{display:flex;align-items:center;gap:8px;flex:none}
  .vc-docv-dl{display:inline-flex;align-items:center;gap:6px;border:1px solid #3b82f6;color:#2563eb;background:#fff;border-radius:8px;padding:7px 12px;font:600 12.5px Inter,system-ui,sans-serif;text-decoration:none}
  .vc-docv-x{width:34px;height:34px;border:0;border-radius:8px;background:transparent;color:#64748b;font-size:22px;line-height:1;cursor:pointer}
  .vc-docv-body{flex:1 1 auto;min-height:0;height:100%;background:#525659;position:relative;overflow:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain}
  .vc-docv-body iframe,.vc-docv-body embed,.vc-docv-body object{position:absolute;inset:0;width:100%;height:100%;min-height:100%;border:0;display:block;background:#525659}
  .vc-docv-img-wrap{min-height:100%;display:block;padding:16px;box-sizing:border-box;background:#0f172a}
  .vc-docv-body img{display:block;width:auto;max-width:100%;height:auto;margin:0 auto;background:#fff}
  .vc-docv-note{position:absolute;left:50%;bottom:18px;transform:translateX(-50%);background:#1e3a8a;color:#fff;border-radius:999px;padding:9px 16px;font:600 12.5px Inter,system-ui,sans-serif;box-shadow:0 8px 24px rgba(15,23,42,.28);z-index:2;white-space:nowrap}
  body.vc-docv-open{overflow:hidden}
</style>
<div class="vc-docv" id="vcDocViewer" hidden aria-hidden="true">
  <div class="vc-docv-panel" role="dialog" aria-modal="true" aria-labelledby="vcDocVTitle">
    <div class="vc-docv-hd">
      <div class="vc-docv-hd-l">
        <span class="vc-docv-ico" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
            <path d="M14 3v5h5"/>
            <path d="M9 13h6M9 17h4"/>
          </svg>
        </span>
        <div class="vc-docv-titles">
          <div class="vc-docv-name" id="vcDocVTitle">Document</div>
          <div class="vc-docv-meta" id="vcDocVMeta"></div>
        </div>
      </div>
      <div class="vc-docv-hd-r">
        <a href="#" class="vc-docv-dl" id="vcDocVDownload" download target="_blank" rel="noopener">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
          Download
        </a>
        <button type="button" class="vc-docv-x" id="vcDocVClose" aria-label="Close">&times;</button>
      </div>
    </div>
    <div class="vc-docv-body" id="vcDocVBody"></div>
    <div class="vc-docv-note" id="vcDocVNote" hidden></div>
  </div>
</div>

<div class="vc-toast" id="vcToast" hidden></div>
