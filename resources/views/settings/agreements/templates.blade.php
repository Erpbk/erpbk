@extends($layout ?? 'layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', $category->name . ' – Agreement Templates')

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <a href="{{ route('agreements.index', ['company_slug' => request()->route('company_slug')]) }}" class="text-muted small">
            <i class="ti ti-arrow-left"></i> Agreements
          </a>
          <h4 class="card-title mb-0 mt-1">{{ $category->name }}</h4>
        </div>
        @canany(['agreements_create', 'gn_settings'])
        <a href="{{ route('agreements.create', ['company_slug' => request()->route('company_slug'), 'category' => $category->id]) }}"
          class="btn btn-primary btn-sm">
          <i class="ti ti-plus me-1"></i> New Template
        </a>
        @endcanany
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>Template</th>
              <th>Default</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($templates as $template)
            <tr>
              <td>{{ $template->template_name }}</td>
              <td>
                @if($template->is_default)
                <span class="badge bg-label-primary">Default</span>
                @else
                @canany(['agreements_manage_templates', 'gn_settings'])
                <form method="POST" action="{{ route('agreements.set-default', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-outline-secondary btn-xs">Set default</button>
                </form>
                @endcanany
                @endif
              </td>
              <td>
                @if($template->status)
                <span class="badge bg-label-success">Active</span>
                @else
                <span class="badge bg-label-secondary">Disabled</span>
                @endif
              </td>
              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('agreements.preview', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}"
                    class="btn btn-outline-info" target="_blank">Preview</a>
                  <a href="{{ route('agreements.preview-pdf', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}"
                    class="btn btn-outline-secondary">PDF</a>
                  @canany(['agreements_edit', 'gn_settings'])
                  <a href="{{ route('agreements.edit', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}"
                    class="btn btn-outline-primary">Edit</a>
                  @endcanany
                  @canany(['agreements_create', 'gn_settings'])
                  <form method="POST" action="{{ route('agreements.duplicate', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-warning">Duplicate</button>
                  </form>
                  @endcanany
                  @canany(['agreements_edit', 'gn_settings'])
                  <form method="POST" action="{{ route('agreements.toggle-status', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary">{{ $template->status ? 'Disable' : 'Enable' }}</button>
                  </form>
                  @endcanany
                  @canany(['agreements_delete', 'gn_settings'])
                  @if(!$template->is_default || $templates->count() > 1)
                  <form method="POST" action="{{ route('agreements.destroy', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}" class="d-inline" onsubmit="return confirm('Delete this template?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                  </form>
                  @endif
                  @endcanany
                </div>
              </td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-muted text-center py-4">No templates yet.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection