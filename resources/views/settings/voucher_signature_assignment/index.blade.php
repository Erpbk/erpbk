@extends($layout ?? 'layouts.app')

@section('title', ($moduleLabel ?? 'Voucher Signature Assignment') . ' – Site Settings')

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">{{ $moduleLabel ?? 'Voucher Signature Assignment' }}</h4>
          <p class="text-muted small mb-0 mt-1">
            Configure which departments or people must sign each voucher type. Configured slots appear on that voucher's view, print, and PDF.
          </p>
        </div>
      </div>
      <div class="card-body border-top">
        <form method="post"
              action="{{ route('settings-panel.voucher-signature-assignment.store-module-label', ['company_slug' => $company_slug]) }}"
              class="row g-2 align-items-end">
          @csrf
          <div class="col-md-6 col-lg-4">
            <label class="form-label" for="module_label">Module display name</label>
            <input type="text" name="module_label" id="module_label" class="form-control"
                   value="{{ old('module_label', $moduleLabel) }}" maxlength="100"
                   @disabled(empty($canEdit))>
          </div>
          @if(!empty($canEdit))
          <div class="col-auto">
            <button type="submit" class="btn btn-outline-primary">Save name</button>
          </div>
          @endif
        </form>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Voucher types</h5>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 100px;">Code</th>
              <th>Label</th>
              <th style="width: 140px;">Signatories</th>
              <th style="width: 140px;" class="text-end">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($voucherTypes as $type)
              @php
                $key = strtoupper((string) $type->code);
                $slotCount = (int) ($configs[$key]->active_slots_count ?? 0);
              @endphp
              <tr>
                <td><span class="badge bg-label-secondary">{{ $type->code }}</span></td>
                <td>{{ $type->label }}</td>
                <td>
                  @if($slotCount > 0)
                    <span class="badge bg-label-success">{{ $slotCount }} configured</span>
                  @else
                    <span class="text-muted small">Legacy default</span>
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('settings-panel.voucher-signature-assignment.configure', ['company_slug' => $company_slug, 'code' => $type->code]) }}"
                     class="btn btn-sm btn-primary">
                    Configure
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-4">No active voucher types found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
