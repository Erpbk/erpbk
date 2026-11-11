@extends($layout ?? 'layouts.app')

@section('title', ($moduleLabel ?? 'Voucher Signature Assignment') . ' – ' . ($voucherType->label ?? ''))

@push('third_party_stylesheets')
<style>
:root{
  --vsa-ink:#1B1D2A;--vsa-ink2:#3A3D52;--vsa-mute:#5A5E75;--vsa-mute2:#6A6E85;
  --vsa-line:#E4E6F0;--vsa-line2:#ECEDF4;--vsa-bg:#F4F5FA;--vsa-soft:#FAFAFD;
  --vsa-brand:#2F2FD8;--vsa-brand-ink:#2525B8;--vsa-brand-soft:#ECECFD;
  --vsa-ok:#145234;--vsa-ok-bg:#F1FAF5;--vsa-ok-line:#CFE8DA;--vsa-red:#B42318;
}
.vsa-wrap{display:flex;flex-direction:column;gap:18px;color:var(--vsa-ink);font-size:14px;padding-bottom:80px}
.vsa-card{background:#fff;border:1px solid var(--vsa-line);border-radius:14px;overflow:hidden}
.vsa-chead{padding:18px 24px;display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:space-between}
.vsa-back{border:0;background:transparent;padding:0;color:var(--vsa-brand);font-size:13px;font-weight:500;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.vsa-chead h1{margin:0;font-size:21px;font-weight:700}
.vsa-code{display:inline-block;min-width:42px;text-align:center;font-family:ui-monospace,Menlo,monospace;font-size:12px;font-weight:600;padding:4px 8px;border-radius:6px;background:var(--vsa-brand-soft);color:var(--vsa-brand-ink)}
.vsa-kind{font-size:11.5px;font-weight:600;padding:3px 8px;border-radius:999px;background:var(--vsa-brand-soft);color:var(--vsa-brand-ink)}
.vsa-status{font-size:12px;font-weight:600;padding:4px 10px;border-radius:999px;background:#F1F2F8;color:var(--vsa-ink2)}
.vsa-status.on{background:var(--vsa-ok-bg);color:var(--vsa-ok);border:1px solid var(--vsa-ok-line)}
.vsa-tabs{display:flex;gap:4px;background:#fff;border:1px solid var(--vsa-line);border-radius:12px;padding:5px;align-self:flex-start}
.vsa-tabs button{height:36px;padding:0 18px;border-radius:8px;border:0;background:transparent;font-size:14px;font-weight:500;color:var(--vsa-mute)}
.vsa-tabs button.on{background:var(--vsa-brand);color:#fff;font-weight:600}
.vsa-sec-h{padding:16px 20px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.vsa-sec-h h2{margin:0;font-size:15px;font-weight:600}
.vsa-sec-h p{margin:3px 0 0;font-size:13px;color:var(--vsa-mute)}
.vsa-hrow{display:flex;flex-wrap:wrap;gap:16px 26px;align-items:center;padding:16px 20px;border-top:1px solid var(--vsa-line2)}
.vsa-ctl{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--vsa-ink2);font-weight:500}
.vsa-ctl input[type=range]{width:120px;accent-color:var(--vsa-brand)}
.vsa-ctl output{font-size:12px;color:var(--vsa-mute);min-width:44px;font-variant-numeric:tabular-nums}
.vsa-seg{display:inline-flex;gap:3px;background:#F1F2F8;padding:3px;border-radius:9px}
.vsa-seg button{height:30px;padding:0 10px;border-radius:7px;border:0;background:transparent;color:var(--vsa-mute);font-size:12.5px;display:inline-flex;align-items:center;gap:6px}
.vsa-seg button.on{background:#fff;color:var(--vsa-ink);font-weight:600;box-shadow:0 1px 2px rgba(20,20,40,.12)}
.vsa-dchip{height:32px;padding:0 12px;border-radius:999px;font-size:13px;display:inline-flex;align-items:center;gap:6px;border:1px dashed #C9CBD8;background:#fff;color:var(--vsa-mute)}
.vsa-dchip.on{border:1px solid #C9CBF5;background:#F5F5FF;color:var(--vsa-brand-ink);font-weight:500}
.vsa-linkbtn{border:0;background:transparent;color:var(--vsa-brand);font-size:13px;font-weight:600}
.vsa-btnp{height:38px;padding:0 16px;border-radius:9px;border:0;background:var(--vsa-brand);color:#fff;font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px}
.vsa-btns{height:38px;padding:0 14px;border-radius:9px;border:1px solid #D5D7E5;background:#fff;color:var(--vsa-ink2);font-size:14px}
.vsa-btns.danger{border-color:#F0C4C4;color:var(--vsa-red);font-weight:500}
.vsa-btns.tpl{border-color:#C9CBF5;background:#F5F5FF;color:var(--vsa-brand-ink);font-weight:500}
.vsa-inp,.vsa-sel{height:36px;padding:0 10px;border:1px solid #D5D7E5;border-radius:8px;font-size:13.5px;background:#fff;width:100%;min-width:0}
.vsa-sel:disabled{background:#F4F5F9;color:#9A9DB2}
.vsa-sgrid{display:grid;grid-template-columns:36px minmax(140px,1.2fr) minmax(130px,1fr) minmax(150px,1.2fr) minmax(130px,1fr) 64px 40px;gap:10px;align-items:center}
.vsa-shd{padding:8px 16px;background:var(--vsa-soft);border-top:1px solid var(--vsa-line2)}
.vsa-shd span{font-size:11.5px;font-weight:600;color:var(--vsa-mute);text-transform:uppercase;letter-spacing:.04em}
.vsa-srow{padding:10px 16px;border-top:1px solid var(--vsa-line2)}
.vsa-srow:hover{background:var(--vsa-soft)}
.vsa-srow.off{opacity:.55}
.vsa-srow.dragging{opacity:.35}
.vsa-srow.over-top{box-shadow:inset 0 3px 0 var(--vsa-brand)}
.vsa-srow.over-bot{box-shadow:inset 0 -3px 0 var(--vsa-brand)}
.vsa-grip{width:26px;height:30px;border-radius:6px;display:inline-flex;align-items:center;justify-content:center;color:#A3A6B8;cursor:grab}
.vsa-sw{position:relative;width:38px;height:22px;border-radius:999px;border:0;padding:0;background:#C9CBD8}
.vsa-sw.on{background:var(--vsa-brand)}
.vsa-sw i{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.25);transition:left .15s;display:block}
.vsa-sw.on i{left:19px}
.vsa-del{width:32px;height:32px;border-radius:8px;border:0;background:transparent;color:var(--vsa-mute);display:inline-flex;align-items:center;justify-content:center}
.vsa-del:hover{background:#FDECEC;color:var(--vsa-red)}
.vsa-empty{padding:32px 24px;border-top:1px solid var(--vsa-line2);display:flex;flex-direction:column;align-items:center;gap:12px;text-align:center}
.vsa-prev-wrap{background:var(--vsa-bg);border-radius:12px;padding:30px 20px 20px;margin:0 20px 20px}
.vsa-paper{background:#fff;border:1px solid var(--vsa-line);border-radius:6px;box-shadow:0 2px 8px rgba(20,20,40,.06);padding:22px 32px 26px;max-width:640px;min-height:640px;margin:0 auto;display:flex;flex-direction:column;position:relative}
.vsa-paper .pg{position:absolute;right:0;top:-22px;font-size:11px;color:var(--vsa-mute2)}
.vsa-hdr{display:flex;align-items:center;gap:18px;padding-bottom:10px;margin-bottom:14px}
.vsa-hdr .lg{flex-shrink:0;border:1.5px dashed #C9CBD8;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#8A8EA5;font-size:11px;font-weight:600;letter-spacing:.06em;background:var(--vsa-soft);overflow:hidden}
.vsa-hdr .lg img{max-width:100%;max-height:100%;object-fit:contain}
.vsa-hdr .tx{display:flex;flex-direction:column;gap:3px;min-width:0;flex:1}
.vsa-hdr .nm{font-weight:800;color:#1414C8;line-height:1.15}
.vsa-hdr .dt{color:var(--vsa-mute);line-height:1.5}
.vsa-bar{height:7px;border-radius:4px;background:#EEEFF5}
.vsa-ftbl div{display:grid;grid-template-columns:3fr 1fr 1fr;gap:12px;padding:9px 0;border-bottom:1px solid #F0F1F6}
.vsa-ftbl div:first-child span{background:#DCDDE8}
.vsa-ftbl span{height:7px;border-radius:4px;background:#EEEFF5}
.vsa-sigs{display:grid;gap:28px}
.vsa-paper.bottom .vsa-sigs,.vsa-paper.bottom .vsa-legacy-sig{margin-top:auto}
.vsa-paper.after .vsa-sigs,.vsa-paper.after .vsa-legacy-sig{margin-top:28px}
.vsa-sig{display:flex;flex-direction:column;justify-content:flex-end;min-width:0;text-align:center}
.vsa-sig .ln{border-top:1.5px solid var(--vsa-ink);padding-top:8px;font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:46px}
.vsa-sig .n{font-size:12px;color:var(--vsa-ink2);min-height:17px;margin-top:2px}
.vsa-sig .d{font-size:11px;color:var(--vsa-mute2);min-height:15px}
.vsa-sig .dt{font-size:11px;color:var(--vsa-mute2);margin-top:6px}
.vsa-legacy-sig{padding:28px 8px;text-align:center;font-size:13px;color:var(--vsa-mute);border:1px dashed #D5D7E5;border-radius:8px}
.vsa-posnote{font-size:12px;color:var(--vsa-mute);margin-top:14px;text-align:center}
.vsa-savebar{position:sticky;bottom:16px;z-index:5;background:var(--vsa-ink);color:#fff;border-radius:14px;padding:12px 14px 12px 20px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between}
.vsa-savebar .dot{width:8px;height:8px;border-radius:999px;background:#4CC38A;display:inline-block;margin-right:10px}
.vsa-savebar .dot.dirty{background:#F5B544}
.vsa-savebar .c{height:40px;padding:0 16px;border-radius:10px;border:1px solid #4A4D63;background:transparent;color:#fff;font-size:14px;text-decoration:none;display:inline-flex;align-items:center}
.vsa-savebar .s{height:40px;padding:0 18px;border-radius:10px;border:0;background:#fff;color:var(--vsa-ink);font-size:14px;font-weight:600}
.vsa-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%);background:var(--vsa-ink);color:#fff;padding:10px 16px;border-radius:10px;font-size:13px;opacity:0;transition:opacity .2s;pointer-events:none;z-index:50}
.vsa-toast.show{opacity:1}
@media (max-width:1050px){.vsa-sgrid{grid-template-columns:36px 1fr 1fr}.vsa-shd{display:none}}
</style>
@endpush

@section('content')
@include('flash::message')
@php
  $canEdit = !empty($canEdit);
  $isConfigured = ($slots ?? collect())->isNotEmpty();
  $h = $headerSettings ?? \App\Models\VoucherSignatureConfig::defaultHeaderSettings();
  $pos = $signaturePosition ?? 'bottom';
  $preview = $companyPreview ?? [];
  $GRIP = '<svg width="14" height="18" viewBox="0 0 14 18" fill="currentColor"><circle cx="4" cy="3" r="1.6"/><circle cx="10" cy="3" r="1.6"/><circle cx="4" cy="9" r="1.6"/><circle cx="10" cy="9" r="1.6"/><circle cx="4" cy="15" r="1.6"/><circle cx="10" cy="15" r="1.6"/></svg>';
@endphp

<form method="post"
      action="{{ route('settings-panel.voucher-signature-assignment.save', ['company_slug' => $company_slug, 'code' => $voucherType->code]) }}"
      id="vsa-form"
      class="vsa-wrap"
      data-can-edit="{{ $canEdit ? '1' : '0' }}">
  @csrf
  <input type="hidden" name="redirect_to" value="list">
  <input type="hidden" name="apply_header_to_all" id="vsa-apply-all" value="0">
  <input type="hidden" name="signature_position" id="vsa-position" value="{{ $pos }}">
  <input type="hidden" name="header[align]" id="vsa-align" value="{{ $h['align'] }}">
  <input type="hidden" name="header[height]" id="vsa-h-height" value="{{ $h['height'] }}">
  <input type="hidden" name="header[logo]" id="vsa-h-logo" value="{{ $h['logo'] }}">
  <input type="hidden" name="header[name]" id="vsa-h-name" value="{{ $h['name'] }}">
  <input type="hidden" name="header[det]" id="vsa-h-det" value="{{ $h['det'] }}">
  <input type="hidden" name="header[line]" id="vsa-h-line" value="{{ !empty($h['line']) ? '1' : '0' }}">
  @foreach(($detailOptions ?? []) as $opt)
    <input type="hidden" name="header[on][{{ $opt['key'] }}]" id="vsa-on-{{ $opt['key'] }}" value="{{ !empty($h['on'][$opt['key']]) ? '1' : '0' }}">
  @endforeach

  <div class="vsa-card vsa-chead">
    <div style="display:flex;flex-direction:column;gap:6px">
      <a class="vsa-back" href="{{ route('settings-panel.voucher-signature-assignment.index', ['company_slug' => $company_slug]) }}">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
        All documents
      </a>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <h1>{{ $voucherType->label }}</h1>
        <span class="vsa-code">{{ $voucherType->code }}</span>
        <span class="vsa-kind">Voucher</span>
        <span class="vsa-status {{ $isConfigured ? 'on' : '' }}" id="vsa-status-pill">{{ $isConfigured ? 'Custom flow' : 'Legacy layout' }}</span>
      </div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
      <select id="vsa-copy-from" class="vsa-sel" style="width:240px;height:38px;border-radius:9px" @disabled(!$canEdit)>
        <option value="">Copy from another document…</option>
        @foreach(($copySources ?? []) as $src)
          <option value="{{ $src['code'] }}">{{ $src['code'] }} · {{ $src['label'] }}</option>
        @endforeach
      </select>
      @if($canEdit && $config)
      <button type="button" class="vsa-btns danger" id="vsa-reset-legacy"
              onclick="if(confirm('Reset to legacy layout? This clears signature slots for this document.')){document.getElementById('vsa-clear-form').submit();}">
        Reset to legacy
      </button>
      @endif
    </div>
  </div>

  <div class="vsa-tabs" role="tablist">
    <button type="button" role="tab" data-tab="header" class="on">Header</button>
    <button type="button" role="tab" data-tab="sign">Signatures</button>
  </div>

  <div class="vsa-card" id="vsa-tab-header">
    <div class="vsa-sec-h">
      <div>
        <h2>Header for {{ $voucherType->code }}</h2>
        <p>Printed at the top of this document. The preview below updates as you change it.</p>
      </div>
      <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <span class="vsa-ctl">Layout</span>
        <div class="vsa-seg" id="vsa-align-seg">
          @foreach(['left'=>'Left','center'=>'Center','right'=>'Right'] as $a => $lab)
            <button type="button" data-align="{{ $a }}" class="{{ ($h['align'] ?? 'left') === $a ? 'on' : '' }}">{{ $lab }}</button>
          @endforeach
        </div>
        @if($canEdit)
        <button type="button" class="vsa-btns" id="vsa-apply-header-all" style="height:36px;font-size:13px">Apply this header to all documents</button>
        @endif
      </div>
    </div>
    <div class="vsa-hrow" style="background:var(--vsa-soft)">
      @foreach([
        ['height','Header height',80,220,5],
        ['logo','Logo size',60,220,5],
        ['name','Name size',14,36,1],
        ['det','Details size',9,16,0.5],
      ] as [$key,$label,$min,$max,$step])
        <label class="vsa-ctl">{{ $label }}
          <input type="range" data-h="{{ $key }}" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" value="{{ $h[$key] }}" @disabled(!$canEdit)>
          <output>{{ $h[$key] }}px</output>
        </label>
      @endforeach
      <label class="vsa-ctl">
        <input type="checkbox" id="vsa-line-cb" @checked(!empty($h['line'])) style="width:16px;height:16px;accent-color:var(--vsa-brand)" @disabled(!$canEdit)>
        Bottom line
      </label>
      @if($canEdit)
      <button type="button" class="vsa-linkbtn" id="vsa-reset-header">Reset</button>
      @endif
    </div>
    <div class="vsa-hrow">
      <span class="vsa-ctl">Details shown</span>
      @foreach(($detailOptions ?? []) as $opt)
        <button type="button" class="vsa-dchip {{ !empty($h['on'][$opt['key']]) ? 'on' : '' }}" data-det="{{ $opt['key'] }}" @disabled(!$canEdit)>
          <span class="vsa-dmark">{{ !empty($h['on'][$opt['key']]) ? '✓' : '+' }}</span> {{ $opt['label'] }}
        </button>
      @endforeach
    </div>
  </div>

  <div class="vsa-card d-none" id="vsa-tab-sign">
    <div class="vsa-sec-h">
      <div>
        <h2>Signature slots <span class="muted" style="font-weight:500;color:var(--vsa-mute)" id="vsa-slot-count">· {{ ($slots ?? collect())->count() }}</span></h2>
        <p>Drag the dots to reorder. Slot #1 prints on the left. Inactive slots don't print.</p>
      </div>
      @if($canEdit)
      <button type="button" class="vsa-btnp" id="vsa-add-slot">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        Add slot
      </button>
      @endif
    </div>
    <div class="vsa-shd" id="vsa-slot-head"><div class="vsa-sgrid"><span></span><span>Role label</span><span>Signer type</span><span>Signer</span><span>Designation</span><span>Active</span><span></span></div></div>
    <div id="vsa-slot-rows"></div>
    <div class="vsa-empty" id="vsa-slot-empty">
      <div style="font-size:14px;font-weight:600">No slots yet — this document uses the legacy layout</div>
      @if($canEdit)
      <div style="display:flex;gap:8px;flex-wrap:wrap;justify-content:center">
        <button type="button" class="vsa-btns tpl" data-tpl="3">Prepared → Checked → Approved</button>
        <button type="button" class="vsa-btns tpl" data-tpl="4">Prepared → Checked → Approved → Received</button>
      </div>
      @endif
    </div>
  </div>

  <div class="vsa-card">
    <div class="vsa-sec-h">
      <h2>Print preview</h2>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <span class="vsa-ctl">Signature position</span>
        <div class="vsa-seg" id="vsa-pos-seg">
          <button type="button" data-pos="bottom" class="{{ $pos === 'bottom' ? 'on' : '' }}">Page bottom</button>
          <button type="button" data-pos="after" class="{{ $pos === 'after' ? 'on' : '' }}">After content</button>
        </div>
      </div>
    </div>
    <div class="vsa-prev-wrap">
      <div class="vsa-paper {{ $pos }}" id="vsa-paper"></div>
      <div class="vsa-posnote" id="vsa-posnote"></div>
    </div>
  </div>

  @if($canEdit)
  <div class="vsa-savebar">
    <div><span class="dot" id="vsa-dirty-dot"></span><span id="vsa-dirty-text">All changes saved</span></div>
    <div style="display:flex;gap:8px">
      <a class="c" href="{{ route('settings-panel.voucher-signature-assignment.index', ['company_slug' => $company_slug]) }}">Cancel</a>
      <button type="submit" class="s">Save print layout</button>
    </div>
  </div>
  @endif
</form>

@if($canEdit && $config)
<form id="vsa-clear-form" method="post" action="{{ route('settings-panel.voucher-signature-assignment.clear', ['company_slug' => $company_slug, 'code' => $voucherType->code]) }}" class="d-none">
  @csrf
  @method('DELETE')
</form>
@endif

<div class="vsa-toast" id="vsa-toast" role="status"></div>

<script type="application/json" id="vsa-boot">
{!! json_encode([
  'canEdit' => $canEdit,
  'header' => $h,
  'position' => $pos,
  'slots' => ($slots ?? collect())->map(fn ($s) => [
    'role_label' => $s->role_label,
    'assignee_type' => $s->assignee_type,
    'department_id' => $s->department_id,
    'user_id' => $s->user_id,
    'employee_id' => $s->employee_id,
    'designation_override' => $s->designation_override,
    'is_active' => (bool) $s->is_active,
  ])->values(),
  'copySources' => $copySources ?? [],
  'departments' => ($departments ?? collect())->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values(),
  'users' => ($users ?? collect())->map(function ($u) {
    $name = trim((string) ($u->name ?: trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))));
    return ['id' => $u->id, 'name' => $name !== '' ? $name : ('User #'.$u->id)];
  })->values(),
  'employees' => ($employees ?? collect())->map(fn ($e) => [
    'id' => $e->id,
    'name' => $e->name,
    'designation' => $e->designation,
  ])->values(),
  'assigneeTypes' => $assigneeTypes ?? [],
  'company' => $preview,
  'defaultHeader' => \App\Models\VoucherSignatureConfig::defaultHeaderSettings(),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
</script>
@endsection

@push('third_party_scripts')
<script>
(function () {
  var bootEl = document.getElementById('vsa-boot');
  if (!bootEl) return;
  var BOOT = JSON.parse(bootEl.textContent);
  var canEdit = !!BOOT.canEdit;
  var state = {
    tab: 'header',
    dirty: false,
    header: JSON.parse(JSON.stringify(BOOT.header)),
    position: BOOT.position || 'bottom',
    slots: JSON.parse(JSON.stringify(BOOT.slots || []))
  };
  var GRIP = '<svg width="14" height="18" viewBox="0 0 14 18" fill="currentColor"><circle cx="4" cy="3" r="1.6"/><circle cx="10" cy="3" r="1.6"/><circle cx="4" cy="9" r="1.6"/><circle cx="10" cy="9" r="1.6"/><circle cx="4" cy="15" r="1.6"/><circle cx="10" cy="15" r="1.6"/></svg>';
  var TRASH = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>';

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }
  function toast(msg) {
    var t = document.getElementById('vsa-toast');
    t.textContent = msg; t.classList.add('show');
    clearTimeout(t._h); t._h = setTimeout(function () { t.classList.remove('show'); }, 1800);
  }
  function markDirty() {
    if (state.dirty) return;
    state.dirty = true;
    var d = document.getElementById('vsa-dirty-dot');
    var t = document.getElementById('vsa-dirty-text');
    if (d) d.classList.add('dirty');
    if (t) t.textContent = 'Unsaved changes';
  }
  function syncHidden() {
    document.getElementById('vsa-align').value = state.header.align;
    document.getElementById('vsa-h-height').value = state.header.height;
    document.getElementById('vsa-h-logo').value = state.header.logo;
    document.getElementById('vsa-h-name').value = state.header.name;
    document.getElementById('vsa-h-det').value = state.header.det;
    document.getElementById('vsa-h-line').value = state.header.line ? '1' : '0';
    document.getElementById('vsa-position').value = state.position;
    Object.keys(state.header.on || {}).forEach(function (k) {
      var el = document.getElementById('vsa-on-' + k);
      if (el) el.value = state.header.on[k] ? '1' : '0';
    });
  }
  function whoOptions(type, selected) {
    if (type === 'label_only') return '<option value="">— Signed by hand —</option>';
    var list = type === 'department' ? BOOT.departments : (type === 'user' ? BOOT.users : BOOT.employees);
    var idKey = type === 'department' ? 'department_id' : (type === 'user' ? 'user_id' : 'employee_id');
    var html = '<option value="">Select ' + type + '…</option>';
    (list || []).forEach(function (o) {
      var sel = String(selected || '') === String(o.id) ? ' selected' : '';
      var label = o.name + (o.designation ? (' — ' + o.designation) : '');
      html += '<option value="' + o.id + '"' + sel + '>' + esc(label) + '</option>';
    });
    return html;
  }
  function assigneeSelectName(i, type) {
    if (type === 'department') return 'slots[' + i + '][department_id]';
    if (type === 'user') return 'slots[' + i + '][user_id]';
    if (type === 'employee') return 'slots[' + i + '][employee_id]';
    return 'slots[' + i + '][_who]';
  }
  function selectedWho(slot) {
    if (slot.assignee_type === 'department') return slot.department_id;
    if (slot.assignee_type === 'user') return slot.user_id;
    if (slot.assignee_type === 'employee') return slot.employee_id;
    return '';
  }
  function whoDisplay(slot) {
    var id = selectedWho(slot);
    if (slot.assignee_type === 'label_only' || !id) return '';
    var list = slot.assignee_type === 'department' ? BOOT.departments : (slot.assignee_type === 'user' ? BOOT.users : BOOT.employees);
    var found = (list || []).find(function (o) { return String(o.id) === String(id); });
    return found ? found.name : '[' + slot.assignee_type + ' name]';
  }
  function renderSlots() {
    var rows = document.getElementById('vsa-slot-rows');
    var empty = document.getElementById('vsa-slot-empty');
    var head = document.getElementById('vsa-slot-head');
    var count = document.getElementById('vsa-slot-count');
    if (count) count.textContent = '· ' + state.slots.length;
    var status = document.getElementById('vsa-status-pill');
    if (status) {
      status.textContent = state.slots.length ? 'Custom flow' : 'Legacy layout';
      status.classList.toggle('on', state.slots.length > 0);
    }
    if (!state.slots.length) {
      rows.innerHTML = '';
      empty.classList.remove('d-none');
      head.style.display = 'none';
      return;
    }
    empty.classList.add('d-none');
    head.style.display = '';
    var typeOpts = Object.keys(BOOT.assigneeTypes || {}).map(function (k) {
      return { value: k, label: BOOT.assigneeTypes[k] };
    });
    rows.innerHTML = state.slots.map(function (s, i) {
      var type = s.assignee_type || 'label_only';
      var whoSel = whoOptions(type, selectedWho(s));
      var typeHtml = typeOpts.map(function (t) {
        return '<option value="' + t.value + '"' + (type === t.value ? ' selected' : '') + '>' + esc(t.label) + '</option>';
      }).join('');
      return '<div class="vsa-srow ' + (s.is_active ? '' : 'off') + '" draggable="' + (canEdit ? 'true' : 'false') + '" data-i="' + i + '">' +
        '<div class="vsa-sgrid">' +
        '<span class="vsa-grip" title="Drag to reorder">' + GRIP + '</span>' +
        '<input class="vsa-inp" name="slots[' + i + '][role_label]" data-f="role_label" data-i="' + i + '" value="' + esc(s.role_label || '') + '" placeholder="Role label, e.g. Approved By" ' + (canEdit ? '' : 'disabled') + '>' +
        '<select class="vsa-sel" name="slots[' + i + '][assignee_type]" data-f="assignee_type" data-i="' + i + '" ' + (canEdit ? '' : 'disabled') + '>' + typeHtml + '</select>' +
        '<select class="vsa-sel" name="' + assigneeSelectName(i, type) + '" data-f="who" data-i="' + i + '" ' + (type === 'label_only' || !canEdit ? 'disabled' : '') + '>' + whoSel + '</select>' +
        '<input class="vsa-inp" name="slots[' + i + '][designation_override]" data-f="designation_override" data-i="' + i + '" value="' + esc(s.designation_override || '') + '" placeholder="Designation (optional)" ' + (canEdit ? '' : 'disabled') + '>' +
        '<button type="button" class="vsa-sw ' + (s.is_active ? 'on' : '') + '" data-toggle="' + i + '" ' + (canEdit ? '' : 'disabled') + '><i></i></button>' +
        '<input type="hidden" name="slots[' + i + '][is_active]" value="' + (s.is_active ? '1' : '0') + '">' +
        (canEdit ? '<button type="button" class="vsa-del" data-del="' + i + '">' + TRASH + '</button>' : '<span></span>') +
        '</div></div>';
    }).join('');
  }
  function paperInner() {
    var h = state.header, k = 0.62, company = BOOT.company || {};
    var dets = Object.keys(h.on || {}).filter(function (key) { return h.on[key]; });
    var map = { tel: company.tel, email: company.email, addr: company.addr, trn: company.trn, web: company.web, vat: company.vat, po: company.po };
    var l1 = dets.filter(function (x) { return ['tel','email','web'].indexOf(x) !== -1; }).map(function (x) { return map[x] || ''; }).filter(Boolean).join(' · ');
    var l2 = dets.filter(function (x) { return ['tel','email','web'].indexOf(x) === -1; }).map(function (x) { return map[x] || ''; }).filter(Boolean).join(' · ');
    var ls = h.logo * k * 0.62;
    var dir = h.align === 'center' ? 'column' : (h.align === 'right' ? 'row-reverse' : 'row');
    var act = state.slots.filter(function (s) { return s.is_active; });
    var logoHtml = company.logo_url
      ? '<img src="' + esc(company.logo_url) + '" alt="Logo">'
      : 'LOGO';
    var sigs = act.length
      ? '<div class="vsa-sigs" style="grid-template-columns:repeat(' + act.length + ',minmax(0,1fr))">' + act.map(function (s) {
          return '<div class="vsa-sig"><div class="ln">' + esc(s.role_label || 'Untitled') + '</div><div class="n">' + esc(whoDisplay(s)) + '</div><div class="d">' + esc(s.designation_override || '') + '</div><div class="dt">Date: ____________</div></div>';
        }).join('') + '</div>'
      : '<div class="vsa-legacy-sig">Legacy signature layout will print here.</div>';
    return '<span class="pg">Last page of document</span>' +
      '<div class="vsa-hdr" style="min-height:' + (h.height * k) + 'px;flex-direction:' + dir + ';text-align:center;border-bottom:' + (h.line ? '2px solid #1414C8' : '0') + '">' +
      '<div class="lg" style="width:' + (ls * 1.5) + 'px;height:' + ls + 'px">' + logoHtml + '</div>' +
      '<div class="tx"><div class="nm" style="font-size:' + (h.name * k) + 'px">' + esc(company.name || '') + '</div>' +
      '<div class="dt" style="font-size:' + (h.det * 0.85) + 'px">' + esc(l1) + (l1 && l2 ? '<br>' : '') + esc(l2) + '</div></div></div>' +
      '<div style="display:flex;justify-content:space-between;align-items:center;padding-bottom:12px;border-bottom:1px dashed #D5D7E5;margin-bottom:8px">' +
      '<div style="display:flex;flex-direction:column;gap:6px;width:40%"><div class="vsa-bar" style="width:80%;background:#E6E7EF"></div><div class="vsa-bar" style="width:55%"></div></div>' +
      '<div style="font-size:12px;color:var(--vsa-mute2)">Total <span class="vsa-bar" style="display:inline-block;width:70px;vertical-align:middle"></span></div></div>' +
      '<div class="vsa-ftbl">' + Array.from({length:6}, function () { return '<div><span></span><span></span><span></span></div>'; }).join('') + '</div>' +
      sigs;
  }
  function refreshPreview() {
    var paper = document.getElementById('vsa-paper');
    var note = document.getElementById('vsa-posnote');
    if (paper) {
      paper.className = 'vsa-paper ' + state.position;
      paper.innerHTML = paperInner();
    }
    if (note) {
      note.textContent = state.position === 'bottom'
        ? 'Signatures are fixed at the bottom of the last page, whatever the document length. The row never splits across pages.'
        : 'Signatures print right after the last line. If there is not enough space, the whole row moves to the next page.';
    }
    syncHidden();
  }
  function showTab(tab) {
    state.tab = tab;
    document.querySelectorAll('.vsa-tabs button').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-tab') === tab);
    });
    document.getElementById('vsa-tab-header').classList.toggle('d-none', tab !== 'header');
    document.getElementById('vsa-tab-sign').classList.toggle('d-none', tab !== 'sign');
  }

  document.querySelector('.vsa-tabs')?.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-tab]');
    if (b) showTab(b.getAttribute('data-tab'));
  });
  document.getElementById('vsa-align-seg')?.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-align]');
    if (!b || !canEdit) return;
    state.header.align = b.getAttribute('data-align');
    this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
    markDirty(); refreshPreview();
  });
  document.getElementById('vsa-pos-seg')?.addEventListener('click', function (e) {
    var b = e.target.closest('button[data-pos]');
    if (!b || !canEdit) return;
    state.position = b.getAttribute('data-pos');
    this.querySelectorAll('button').forEach(function (x) { x.classList.toggle('on', x === b); });
    markDirty(); refreshPreview();
  });
  document.querySelectorAll('input[data-h]').forEach(function (inp) {
    inp.addEventListener('input', function () {
      if (!canEdit) return;
      var key = inp.getAttribute('data-h');
      state.header[key] = +inp.value;
      if (inp.nextElementSibling) inp.nextElementSibling.textContent = inp.value + 'px';
      markDirty(); refreshPreview();
    });
  });
  document.getElementById('vsa-line-cb')?.addEventListener('change', function () {
    if (!canEdit) return;
    state.header.line = this.checked;
    markDirty(); refreshPreview();
  });
  document.querySelectorAll('.vsa-dchip[data-det]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!canEdit) return;
      var k = btn.getAttribute('data-det');
      state.header.on[k] = state.header.on[k] ? 0 : 1;
      btn.classList.toggle('on', !!state.header.on[k]);
      var mark = btn.querySelector('.vsa-dmark');
      if (mark) mark.textContent = state.header.on[k] ? '✓' : '+';
      markDirty(); refreshPreview();
    });
  });
  document.getElementById('vsa-reset-header')?.addEventListener('click', function () {
    state.header = JSON.parse(JSON.stringify(BOOT.defaultHeader));
    document.querySelectorAll('input[data-h]').forEach(function (inp) {
      var key = inp.getAttribute('data-h');
      inp.value = state.header[key];
      if (inp.nextElementSibling) inp.nextElementSibling.textContent = state.header[key] + 'px';
    });
    var line = document.getElementById('vsa-line-cb');
    if (line) line.checked = !!state.header.line;
    document.querySelectorAll('#vsa-align-seg button').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-align') === state.header.align);
    });
    document.querySelectorAll('.vsa-dchip[data-det]').forEach(function (btn) {
      var k = btn.getAttribute('data-det');
      btn.classList.toggle('on', !!state.header.on[k]);
      var mark = btn.querySelector('.vsa-dmark');
      if (mark) mark.textContent = state.header.on[k] ? '✓' : '+';
    });
    markDirty(); refreshPreview();
  });
  document.getElementById('vsa-apply-header-all')?.addEventListener('click', function () {
    document.getElementById('vsa-apply-all').value = '1';
    this.textContent = 'Will apply to all documents on save ✓';
    toast('Header will apply to all documents when you save');
    markDirty();
  });

  var slotRows = document.getElementById('vsa-slot-rows');
  slotRows?.addEventListener('click', function (e) {
    if (!canEdit) return;
    var tog = e.target.closest('[data-toggle]');
    if (tog) {
      var i = +tog.getAttribute('data-toggle');
      state.slots[i].is_active = !state.slots[i].is_active;
      markDirty(); renderSlots(); refreshPreview();
      return;
    }
    var del = e.target.closest('[data-del]');
    if (del) {
      state.slots.splice(+del.getAttribute('data-del'), 1);
      markDirty(); renderSlots(); refreshPreview();
    }
  });
  slotRows?.addEventListener('input', function (e) {
    var t = e.target;
    if (!t.dataset.f || !canEdit) return;
    var i = +t.dataset.i;
    if (t.dataset.f === 'role_label' || t.dataset.f === 'designation_override') {
      state.slots[i][t.dataset.f] = t.value;
      markDirty(); refreshPreview();
    }
  });
  slotRows?.addEventListener('change', function (e) {
    var t = e.target;
    if (!t.dataset.f || !canEdit) return;
    var i = +t.dataset.i;
    if (t.dataset.f === 'assignee_type') {
      state.slots[i].assignee_type = t.value;
      state.slots[i].department_id = null;
      state.slots[i].user_id = null;
      state.slots[i].employee_id = null;
      markDirty(); renderSlots(); refreshPreview();
    } else if (t.dataset.f === 'who') {
      var type = state.slots[i].assignee_type;
      state.slots[i].department_id = type === 'department' ? (t.value || null) : null;
      state.slots[i].user_id = type === 'user' ? (t.value || null) : null;
      state.slots[i].employee_id = type === 'employee' ? (t.value || null) : null;
      markDirty(); refreshPreview();
    }
  });

  document.getElementById('vsa-add-slot')?.addEventListener('click', function () {
    state.slots.push({
      role_label: '',
      assignee_type: 'label_only',
      department_id: null,
      user_id: null,
      employee_id: null,
      designation_override: '',
      is_active: true
    });
    markDirty(); renderSlots(); refreshPreview();
    var labels = slotRows.querySelectorAll('[data-f=role_label]');
    labels[labels.length - 1]?.focus();
  });
  document.querySelectorAll('[data-tpl]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      state.slots = [
        { role_label: 'Prepared By', assignee_type: 'user', department_id: null, user_id: null, employee_id: null, designation_override: '', is_active: true },
        { role_label: 'Checked By', assignee_type: 'department', department_id: null, user_id: null, employee_id: null, designation_override: '', is_active: true },
        { role_label: 'Approved By', assignee_type: 'department', department_id: null, user_id: null, employee_id: null, designation_override: '', is_active: true }
      ];
      if (btn.getAttribute('data-tpl') === '4') {
        state.slots.push({ role_label: 'Received By', assignee_type: 'label_only', department_id: null, user_id: null, employee_id: null, designation_override: '', is_active: true });
      }
      markDirty(); renderSlots(); refreshPreview();
    });
  });

  document.getElementById('vsa-copy-from')?.addEventListener('change', function () {
    var code = this.value;
    if (!code || !canEdit) return;
    var src = (BOOT.copySources || []).find(function (s) { return s.code === code; });
    if (!src) return;
    state.slots = JSON.parse(JSON.stringify(src.slots || []));
    state.header = JSON.parse(JSON.stringify(src.header || BOOT.defaultHeader));
    state.position = src.position || 'bottom';
    document.querySelectorAll('input[data-h]').forEach(function (inp) {
      var key = inp.getAttribute('data-h');
      inp.value = state.header[key];
      if (inp.nextElementSibling) inp.nextElementSibling.textContent = state.header[key] + 'px';
    });
    var line = document.getElementById('vsa-line-cb');
    if (line) line.checked = !!state.header.line;
    document.querySelectorAll('#vsa-align-seg button').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-align') === state.header.align);
    });
    document.querySelectorAll('#vsa-pos-seg button').forEach(function (b) {
      b.classList.toggle('on', b.getAttribute('data-pos') === state.position);
    });
    document.querySelectorAll('.vsa-dchip[data-det]').forEach(function (btn) {
      var k = btn.getAttribute('data-det');
      btn.classList.toggle('on', !!state.header.on[k]);
      var mark = btn.querySelector('.vsa-dmark');
      if (mark) mark.textContent = state.header.on[k] ? '✓' : '+';
    });
    this.value = '';
    markDirty(); renderSlots(); refreshPreview();
    toast('Copied from ' + code);
  });

  // drag slots
  var drag = null;
  slotRows?.addEventListener('dragstart', function (e) {
    var r = e.target.closest('.vsa-srow');
    if (!r || !canEdit) return;
    if (['INPUT','SELECT'].indexOf(document.activeElement?.tagName) !== -1 && r.contains(document.activeElement)) {
      e.preventDefault(); return;
    }
    drag = r;
    r.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', '');
  });
  slotRows?.addEventListener('dragover', function (e) {
    if (!drag) return;
    var r = e.target.closest('.vsa-srow');
    if (!r || r === drag) return;
    e.preventDefault();
    slotRows.querySelectorAll('.over-top,.over-bot').forEach(function (x) { x.classList.remove('over-top','over-bot'); });
    var box = r.getBoundingClientRect();
    r.classList.add(e.clientY < box.top + box.height / 2 ? 'over-top' : 'over-bot');
  });
  slotRows?.addEventListener('drop', function (e) {
    if (!drag) return;
    var r = e.target.closest('.vsa-srow');
    if (!r || r === drag) return;
    e.preventDefault();
    var after = r.classList.contains('over-bot');
    var fi = +drag.dataset.i;
    var item = state.slots.splice(fi, 1)[0];
    var ti = +r.dataset.i;
    if (fi < ti) ti--;
    state.slots.splice(ti + (after ? 1 : 0), 0, item);
    markDirty(); renderSlots(); refreshPreview();
  });
  slotRows?.addEventListener('dragend', function () {
    slotRows.querySelectorAll('.dragging,.over-top,.over-bot').forEach(function (x) {
      x.classList.remove('dragging','over-top','over-bot');
    });
    drag = null;
  });

  document.getElementById('vsa-form')?.addEventListener('submit', function () {
    syncHidden();
  });

  renderSlots();
  refreshPreview();
})();
</script>
@endpush
