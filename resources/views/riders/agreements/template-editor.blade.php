@extends($layout ?? 'layouts.app')

@section('title', 'Agreement Template Editor')

@section('content')
@include('flash::message')

<div class="row">
  <div class="col-lg-11 mx-auto">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="mb-0">{{ $template->template_name }}</h4>
          <div class="text-muted small mt-1">
            Agreement: {{ $category->name ?? '-' }} | Rider: {{ $rider->name }} ({{ $rider->rider_id }})
          </div>
        </div>
        <div class="d-flex gap-2">
          <a href="{{ route('rider-agreements.preview', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'template_id' => $template->id]) }}" target="_blank" class="btn btn-outline-info btn-sm">Preview</a>
          <a href="{{ route('rider-agreements.pdf', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'template_id' => $template->id, 'download' => 1]) }}" class="btn btn-outline-primary btn-sm">Download PDF</a>
        </div>
      </div>
      <div class="card-body">
        <form method="POST" action="{{ route('rider-agreements.templates.update', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'template' => $template->id]) }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label">Template Content</label>
            <div class="agreement-word-editor"
              data-margin-top="{{ ($letterheadMargins ?? $category?->resolvedLetterheadMarginsMm() ?? [])['top'] ?? '' }}"
              data-margin-right="{{ ($letterheadMargins ?? $category?->resolvedLetterheadMarginsMm() ?? [])['right'] ?? '' }}"
              data-margin-bottom="{{ ($letterheadMargins ?? $category?->resolvedLetterheadMarginsMm() ?? [])['bottom'] ?? '' }}"
              data-margin-left="{{ ($letterheadMargins ?? $category?->resolvedLetterheadMarginsMm() ?? [])['left'] ?? '' }}">
              <textarea name="description" id="agreement_template_editor" class="form-control" rows="40">{{ old('description', $template->description) }}</textarea>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Available placeholders</label>
            <div class="border rounded p-2 small bg-light">
              @forelse($placeholders as $group => $items)
                <div class="fw-semibold mb-1">{{ $group }}</div>
                <div class="mb-2">
                  @foreach($items as $item)
                    <span class="badge bg-label-secondary me-1">{{ $item->placeholder }}</span>
                  @endforeach
                </div>
              @empty
                <span class="text-muted">No placeholders configured.</span>
              @endforelse
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Save Template</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@include('agreements.partials.tinymce-word-document')

@push('third_party_scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.tinymce || !window.erpbkAgreementWordEditor) return;
    tinymce.init(window.erpbkAgreementWordEditor.config({
      selector: '#agreement_template_editor'
    }));
  });
</script>
@endpush
