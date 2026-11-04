@php
  $companySlug = request()->route('company_slug');
  $recordLabel = trim((string) ($meta['name'] ?? ''));
  if ($recordLabel === '') {
    $recordLabel = (string) ($meta['code'] ?? $record);
  }
@endphp

<div id="module-record-agreements-modal">
  <p class="text-muted small mb-3">
    Select an agreement for <strong>{{ $recordLabel }}</strong>@if(!empty($meta['code']) && $recordLabel !== $meta['code']) ({{ $meta['code'] }})@endif. It will open in a new tab.
  </p>

  @if($agreements->isEmpty())
  <div class="text-center py-4">
    <i class="ti ti-file-certificate display-6 text-muted d-block mb-2"></i>
    <p class="mb-0 text-muted">No agreements assigned to this module</p>
  </div>
  @else
  <div class="list-group">
    @foreach($agreements as $agreement)
      @php
        $template = $agreement->contractTemplate();
        $showUrl = $template
          ? route('module-record-agreements.show', [
              'company_slug' => $companySlug,
              'module' => $module,
              'record' => $record,
              'category' => $agreement->id,
            ])
          : null;
      @endphp
      @if($showUrl)
      <a href="{{ $showUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        class="list-group-item list-group-item-action d-flex align-items-center gap-2 js-open-agreement-tab">
        <i class="ti ti-file-certificate text-primary"></i>
        <span class="flex-grow-1">
          <span class="fw-semibold d-block">{{ $agreement->name }}</span>
          <span class="text-muted small"><code>{{ $agreement->agreement_code ?? $agreement->slug }}</code></span>
        </span>
        <i class="ti ti-external-link text-muted"></i>
      </a>
      @else
      <div class="list-group-item d-flex align-items-center gap-2 text-muted">
        <i class="ti ti-alert-circle"></i>
        <span class="flex-grow-1">
          <span class="fw-semibold d-block">{{ $agreement->name }}</span>
          <span class="small">No template assigned</span>
        </span>
      </div>
      @endif
    @endforeach
  </div>
  @endif
</div>

<script>
(function () {
  document.querySelectorAll('#module-record-agreements-modal a.js-open-agreement-tab').forEach(function (link) {
    link.addEventListener('click', function () {
      if (typeof toggleModalTop === 'function') {
        toggleModalTop('hide');
      }
    });
  });
})();
</script>
