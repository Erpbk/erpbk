@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', 'Agreement – Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug');
$groupLabel = $groups[$category->group_key]['label'] ?? $category->group_key;
  $assignedModules = $category->normalizedAssignedModules();
@endphp

<div class="row">
  <div class="col-lg-10 mx-auto">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">{{ $category->name }}</h4>
          <div class="text-muted small mt-1">
            Code: <code>{{ $category->agreement_code ?? $category->slug }}</code> · Group: {{ $groupLabel }}
          </div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('agreements.edit-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}" class="btn btn-outline-primary btn-sm">
            Edit
          </a>
          <a href="{{ route('agreements.index', ['company_slug' => $companySlug, 'group' => $category->group_key]) }}" class="btn btn-outline-secondary btn-sm">
            Back
          </a>
        </div>
      </div>

      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="mb-2 text-muted small">Assigned Modules</div>
            @if(!empty($assignedModules))
            @foreach($assignedModules as $moduleKey)
            <span class="badge bg-label-secondary me-1 mb-1">
              {{ $modules[$moduleKey] ?? $moduleKey }}
            </span>
            @endforeach
            @else
            <div class="text-muted">—</div>
            @endif
          </div>

          <div class="col-md-12">
            <div class="mb-2 text-muted small">Description</div>
            <div class="border rounded p-3 bg-light">
              {!! nl2br(e($category->description ?? '')) !!}
            </div>
          </div>

          <div class="col-md-6">
            <div class="mb-2 text-muted small">Contract template</div>
            @php $contractTpl = $category->contractTemplate(); @endphp
            @if($contractTpl)
            <div>{{ $contractTpl->template_name }}</div>
            <span class="badge bg-label-primary">
              {{ \App\Models\AgreementTemplate::TYPES[$contractTpl->template_type] ?? $contractTpl->template_type }}
            </span>
            @else
            <span class="text-muted">Not assigned — edit agreement to select a template.</span>
            @endif
          </div>

          <div class="col-md-12">
            <div class="mb-2 text-muted small">Status</div>
            @if($category->status)
            <span class="badge bg-label-success">Active</span>
            @else
            <span class="badge bg-label-secondary">Inactive</span>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection