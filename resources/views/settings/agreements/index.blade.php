@extends($layout ?? 'layouts.app')

@section('title', 'Agreements')

@section('content')
@include('flash::message')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">Agreements</h4>
          <p class="text-muted small mb-0 mt-1">
            Create agreements and assign them to modules. Template editing is module-side only.
          </p>
        </div>
        @canany(['agreements_create', 'gn_settings'])
        <a class="btn btn-primary btn-sm" href="{{ route('agreements.create-agreement', ['company_slug' => request()->route('company_slug'), 'group' => $groupKey]) }}">
          <i class="ti ti-plus me-1"></i> New Agreement
        </a>
        @endcanany
      </div>

      <div class="card-body">
        <ul class="nav nav-tabs mb-3">
          @foreach($groups as $key => $group)
          <li class="nav-item">
            <a class="nav-link {{ $groupKey === $key ? 'active' : '' }}"
              href="{{ route('agreements.index', ['company_slug' => request()->route('company_slug'), 'group' => $key]) }}">
              {{ $group['label'] ?? $key }}
            </a>
          </li>
          @endforeach
        </ul>

        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>Agreement</th>
                <th>Agreement Code</th>
                <th>Assigned Modules</th>
                <th>Contract Template</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($categories as $category)
              <tr>
                <td class="text-start">
                  <div class="fw-semibold">{{ $category->name }}</div>
                </td>
                <td>
                  <code>{{ $category->agreement_code ?? $category->slug }}</code>
                </td>
                <td>
                  @php $assignedModules = $category->normalizedAssignedModules(); @endphp
                  @if(!empty($assignedModules))
                  @foreach($assignedModules as $moduleKey)
                  <span class="badge bg-label-secondary me-1">{{ $modules[$moduleKey] ?? $moduleKey }}</span>
                  @endforeach
                  @else
                  <span class="text-muted small">—</span>
                  @endif
                </td>
                <td>
                  @php $contractTpl = $category->defaultTemplate; @endphp
                  @if($contractTpl)
                  <span class="small">{{ $contractTpl->template_name }}</span>
                  @else
                  <span class="text-muted small">Not set</span>
                  @endif
                </td>
                <td>
                  @if($category->status)
                  <span class="badge bg-label-success">Active</span>
                  @else
                  <span class="badge bg-label-secondary">Inactive</span>
                  @endif
                </td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    @canany(['agreements_view', 'gn_settings'])
                    <a href="{{ route('agreements.show-agreement', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}" class="btn btn-outline-info">
                      View
                    </a>
                    @endcanany

                    @canany(['agreements_edit', 'gn_settings'])
                    <a href="{{ route('agreements.edit-agreement', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}" class="btn btn-outline-primary">
                      Edit
                    </a>
                    @endcanany

                    @canany(['agreements_edit', 'gn_settings'])
                    <form method="POST" action="{{ route('agreements.toggle-agreement-status', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}" class="d-inline">
                      @csrf
                      <button type="submit" class="btn btn-outline-secondary">
                        {{ $category->status ? 'Deactivate' : 'Activate' }}
                      </button>
                    </form>
                    @endcanany

                    @canany(['agreements_delete', 'gn_settings'])
                    <form method="POST" action="{{ route('agreements.destroy-agreement', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}" class="d-inline" onsubmit="return confirm('Delete this agreement? This will also delete its templates.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-outline-danger">
                        Delete
                      </button>
                    </form>
                    @endcanany
                  </div>
                </td>
              </tr>
              @empty
              <tr>
                <td colspan="6" class="text-muted text-center py-4">No agreements found.</td>
              </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection