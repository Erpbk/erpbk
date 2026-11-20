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

<div class="vc-toast" id="vcToast" hidden></div>
