@extends('layouts.app')

@section('title', $filterLabel ?? 'Documents')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2 align-items-center">
      <div class="col-sm-8">
        <h3 class="mb-0">{{ $filterLabel }}</h3>
        <p class="text-muted small mb-0">{{ __('Showing :total document(s) for modules you are allowed to access.', ['total' => $total ?? 0]) }}</p>
      </div>
      <div class="col-sm-4 text-sm-end">
        <a href="{{ route('files.index', ['company_slug' => request()->route('company_slug')]) }}" class="btn btn-outline-secondary btn-sm">
          {{ __('All documents') }}
        </a>
      </div>
    </div>
  </div>
</section>

<div class="content px-0">
  @if(!empty($byModule))
  <div class="card mb-3">
    <div class="card-body py-3">
      <div class="d-flex flex-wrap gap-2">
        @foreach($byModule as $row)
        <span class="badge bg-label-primary">{{ $row['label'] }}: {{ number_format($row['count']) }}</span>
        @endforeach
      </div>
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>{{ __('Module') }}</th>
              <th>{{ __('Document') }}</th>
              <th>{{ __('Expiry date') }}</th>
              <th>{{ __('Status') }}</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse(($items ?? []) as $item)
            <tr>
              <td><span class="badge bg-label-secondary">{{ $item['module_label'] }}</span></td>
              <td>{{ $item['title'] }}</td>
              <td>{{ $item['expiry_date'] }}</td>
              <td>
                @if(($item['days_left'] ?? 0) < 0)
                <span class="badge bg-danger">{{ __('Expired') }}</span>
                @else
                <span class="badge bg-warning">{{ $item['days_left'] }} {{ __('days left') }}</span>
                @endif
              </td>
              <td class="text-end">
                @if(!empty($item['url']))
                <a href="{{ $item['url'] }}" class="btn btn-sm btn-outline-primary">{{ __('Open') }}</a>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-4">{{ __('No documents found for this filter.') }}</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
