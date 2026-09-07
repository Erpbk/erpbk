@extends('layouts.settingsPanelLayout')

@section('title', $pageTitle ?? 'Agreement Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
$activeTab = $activeTab ?? 'general';
@endphp

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="agreementSettingsTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab !== 'letterhead' ? 'active' : '' }}" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
              General
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $activeTab === 'letterhead' ? 'active' : '' }}" id="tab-letterhead-btn" data-bs-toggle="tab" data-bs-target="#tab-letterhead" type="button" role="tab">
              Letterhead &amp; Watermark
            </button>
          </li>
        </ul>

        <div class="tab-content">
          <div class="tab-pane fade {{ $activeTab !== 'letterhead' ? 'show active' : '' }}" id="tab-general" role="tabpanel">
            @include('settings.partials._module_general_label_form', [
              'settingsRoutePrefix' => $settingsRoutePrefix,
              'settingsRouteParams' => $settingsRouteParams,
              'moduleMenuKey' => $moduleKey ?? 'agreements',
              'moduleLabel' => $moduleLabel ?? null,
              'settingsHeading' => $settingsHeading ?? null,
              'defaultLabel' => $defaultLabel ?? $settingsHeading,
            ])
            @include('settings.partials._module_menu_icon_form', [
              'settingsRoutePrefix' => $settingsRoutePrefix,
              'settingsRouteParams' => $settingsRouteParams,
              'moduleMenuKey' => $moduleKey ?? 'agreements',
              'moduleLabel' => $moduleLabel ?? null,
              'defaultLabel' => $defaultLabel ?? $settingsHeading,
            ])
          </div>

          <div class="tab-pane fade {{ $activeTab === 'letterhead' ? 'show active' : '' }}" id="tab-letterhead" role="tabpanel">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
              <div>
                <h5 class="mb-1">Letterheads</h5>
                <p class="text-muted small mb-0">
                  Upload full-page designs (JPG, PNG, WebP, or PDF). Each agreement can then pick one of these, the default company header, or none.
                </p>
              </div>
            </div>

            <form method="POST" action="{{ route('settings-panel.agreement-letterheads.store', ['company_slug' => $companySlug]) }}" enctype="multipart/form-data" class="border rounded p-3 mb-4 bg-light">
              @csrf
              <input type="hidden" name="kind" value="letterhead">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label small mb-1">Name</label>
                  <input type="text" name="name" class="form-control form-control-sm" value="{{ old('kind') === 'watermark' ? '' : old('name') }}" placeholder="e.g. Stellar Innovations">
                </div>
                <div class="col-md-5">
                  <label class="form-label small mb-1">File <span class="text-danger">*</span></label>
                  <input type="file" name="letterhead" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,application/pdf,.jpg,.jpeg,.png,.webp,.pdf" required>
                </div>
                <div class="col-md-3">
                  <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="ti ti-upload me-1"></i> Upload
                  </button>
                </div>
              </div>
              <div class="form-text mt-2">Max 10 MB. PDF uses the first page (requires Ghostscript or Imagick; otherwise upload a JPG/PNG).</div>
              @error('letterhead')
              @if(old('kind', 'letterhead') !== 'watermark')
              <div class="text-danger small mt-1">{{ $message }}</div>
              @endif
              @enderror
            </form>

            <div class="row g-3 mb-5">
              @forelse($letterheads as $letterhead)
              @php $thumb = $letterhead->publicUrl(); @endphp
              <div class="col-md-6 col-lg-4">
                <div class="card h-100 border">
                  <div class="card-body">
                    @if($thumb)
                    <img src="{{ $thumb }}" alt="{{ $letterhead->name }}"
                      class="img-fluid border rounded mb-2"
                      style="max-height:180px;width:100%;object-fit:contain;background:#f8fafc;">
                    @else
                    <div class="border rounded mb-2 text-muted small d-flex align-items-center justify-content-center" style="height:120px;background:#f8fafc;">
                      Preview unavailable
                    </div>
                    @endif
                    <form method="POST" action="{{ route('settings-panel.agreement-letterheads.update', ['company_slug' => $companySlug, 'letterhead' => $letterhead->id]) }}" class="mb-2">
                      @csrf
                      @method('PUT')
                      <div class="input-group input-group-sm">
                        <input type="text" name="name" class="form-control" value="{{ $letterhead->name }}" required>
                        <button type="submit" class="btn btn-outline-secondary">Rename</button>
                      </div>
                    </form>
                    <form method="POST" action="{{ route('settings-panel.agreement-letterheads.destroy', ['company_slug' => $companySlug, 'letterhead' => $letterhead->id]) }}" onsubmit="return confirm('Remove this letterhead? Agreements using it will fall back to the company header.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm w-100">Delete</button>
                    </form>
                  </div>
                </div>
              </div>
              @empty
              <div class="col-12">
                <p class="text-muted mb-0">No letterheads uploaded yet. Agreements can use the company header, or none, until you add one.</p>
              </div>
              @endforelse
            </div>

            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3" id="watermarks">
              <div>
                <h5 class="mb-1">Watermarks</h5>
                <p class="text-muted small mb-0">
                  Upload a logo or mark (JPG, PNG, WebP, or PDF). Each agreement can place one in the page centre, behind the text.
                </p>
              </div>
            </div>

            <form method="POST" action="{{ route('settings-panel.agreement-letterheads.store', ['company_slug' => $companySlug]) }}" enctype="multipart/form-data" class="border rounded p-3 mb-4 bg-light">
              @csrf
              <input type="hidden" name="kind" value="watermark">
              <div class="row g-2 align-items-end">
                <div class="col-md-4">
                  <label class="form-label small mb-1">Name</label>
                  <input type="text" name="name" class="form-control form-control-sm" value="{{ old('kind') === 'watermark' ? old('name') : '' }}" placeholder="e.g. Company seal">
                </div>
                <div class="col-md-5">
                  <label class="form-label small mb-1">File <span class="text-danger">*</span></label>
                  <input type="file" name="letterhead" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,application/pdf,.jpg,.jpeg,.png,.webp,.pdf" required>
                </div>
                <div class="col-md-3">
                  <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="ti ti-upload me-1"></i> Upload
                  </button>
                </div>
              </div>
              <div class="form-text mt-2">Max 10 MB. PNG with a transparent background works best.</div>
              @error('letterhead')
              @if(old('kind') === 'watermark')
              <div class="text-danger small mt-1">{{ $message }}</div>
              @endif
              @enderror
            </form>

            <div class="row g-3">
              @forelse($watermarks ?? [] as $watermark)
              @php $thumb = $watermark->publicUrl(); @endphp
              <div class="col-md-6 col-lg-4">
                <div class="card h-100 border">
                  <div class="card-body">
                    @if($thumb)
                    <img src="{{ $thumb }}" alt="{{ $watermark->name }}"
                      class="img-fluid border rounded mb-2"
                      style="max-height:180px;width:100%;object-fit:contain;background:#f8fafc;">
                    @else
                    <div class="border rounded mb-2 text-muted small d-flex align-items-center justify-content-center" style="height:120px;background:#f8fafc;">
                      Preview unavailable
                    </div>
                    @endif
                    <form method="POST" action="{{ route('settings-panel.agreement-letterheads.update', ['company_slug' => $companySlug, 'letterhead' => $watermark->id]) }}" class="mb-2">
                      @csrf
                      @method('PUT')
                      <div class="input-group input-group-sm">
                        <input type="text" name="name" class="form-control" value="{{ $watermark->name }}" required>
                        <button type="submit" class="btn btn-outline-secondary">Rename</button>
                      </div>
                    </form>
                    <form method="POST" action="{{ route('settings-panel.agreement-letterheads.destroy', ['company_slug' => $companySlug, 'letterhead' => $watermark->id]) }}" onsubmit="return confirm('Remove this watermark? Agreements using it will print without a watermark.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger btn-sm w-100">Delete</button>
                    </form>
                  </div>
                </div>
              </div>
              @empty
              <div class="col-12">
                <p class="text-muted mb-0">No watermarks uploaded yet.</p>
              </div>
              @endforelse
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (window.location.hash === '#tab-letterhead') {
      var btn = document.getElementById('tab-letterhead-btn');
      if (btn && window.bootstrap && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(btn).show();
      }
    }
  });
</script>
@endpush
