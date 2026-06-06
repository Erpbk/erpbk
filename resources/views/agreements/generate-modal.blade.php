@isset($rider)
@isset($defaultTemplate)
@include('agreements.partials.contract-document-panel', [
  'rider' => $rider,
  'category' => $category,
  'template' => $defaultTemplate,
  'panelId' => $category->id,
])
@else
<div class="alert alert-warning mb-0">
  No contract template is assigned for <strong>{{ $category->name ?? 'this agreement' }}</strong>.
  Assign one in Settings → Agreements.
</div>
@endisset
@endisset
