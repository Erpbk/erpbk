@extends($layout ?? 'layouts.app')

@section('title', $moduleLabel . ' — Agreements')

@section('content')
@include('flash::message')

@php $companySlug = request()->route('company_slug'); @endphp

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h4 class="card-title mb-0">{{ $moduleLabel }} Agreements</h4>
        <p class="text-muted small mb-0 mt-1">
          Agreements assigned to this module only. Edit template content here; assign agreements in Settings.
        </p>
      </div>
      <div class="card-body">
        <div class="row g-3">
          @forelse($agreements as $agreement)
          <div class="col-md-6 col-lg-4">
            <div class="card border h-100">
              <div class="card-body d-flex flex-column">
                <h5 class="mb-1">{{ $agreement->name }}</h5>
                <p class="text-muted small mb-2"><code>{{ $agreement->agreement_code ?? $agreement->slug }}</code></p>
                @if($agreement->description)
                <p class="small text-muted flex-grow-1">{{ \Illuminate\Support\Str::limit(strip_tags($agreement->description), 120) }}</p>
                @endif
                <p class="small mb-3">
                  <span class="badge bg-label-primary">{{ $agreement->templates->count() }} system template(s)</span>
                </p>
                <a href="{{ route('module-agreements.show', ['company_slug' => $companySlug, 'module' => $module, 'category' => $agreement->id]) }}"
                  class="btn btn-primary btn-sm mt-auto">
                  Manage templates
                </a>
              </div>
            </div>
          </div>
          @empty
          <div class="col-12">
            <p class="text-muted mb-0">No agreements are assigned to {{ $moduleLabel }}. Assign agreements in Settings → Agreements.</p>
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
