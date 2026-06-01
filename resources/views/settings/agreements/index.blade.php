@extends($layout ?? 'layouts.app')

@section('title', 'Agreements – Settings')

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="card-title mb-0">Agreements</h4>
        <p class="text-muted small mb-0 mt-1">Manage agreement templates by category. Templates are company-specific.</p>
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs mb-3">
          @foreach($groups as $key => $group)
          <li class="nav-item">
            <a class="nav-link {{ $groupKey === $key ? 'active' : '' }}"
               href="{{ route('settings-panel.agreements.index', ['company_slug' => request()->route('company_slug'), 'group' => $key]) }}">
              {{ $group['label'] ?? $key }}
            </a>
          </li>
          @endforeach
        </ul>

        <div class="row g-3">
          @forelse($categories as $category)
          <div class="col-md-6 col-lg-4">
            <div class="card border h-100">
              <div class="card-body">
                <h5 class="mb-2">{{ $category->name }}</h5>
                <p class="text-muted small mb-3">{{ $category->activeTemplates()->count() }} template(s)</p>
                @canany(['agreement_view', 'gn_settings'])
                <a href="{{ route('settings-panel.agreements.templates', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}"
                   class="btn btn-primary btn-sm">
                  <i class="ti ti-file-text me-1"></i> Manage Templates
                </a>
                @endcanany
              </div>
            </div>
          </div>
          @empty
          <div class="col-12">
            <p class="text-muted">No agreement categories configured.</p>
          </div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
