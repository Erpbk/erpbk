@php
$prefix = $prefix ?? '';
@endphp
<div class="mb-3">
  <label class="form-label">Template Name <span class="text-danger">*</span></label>
  <input type="text" name="template_name" class="form-control" value="{{ old('template_name', $template->template_name ?? '') }}" required maxlength="120">
</div>
<div class="mb-3">
  <label class="form-label">Layout Style <span class="text-danger">*</span></label>
  <select name="layout_key" class="form-select" required>
    @foreach($layouts as $key => $label)
    <option value="{{ $key }}" @selected(old('layout_key', $template->layout_key ?? 'modern') === $key)>{{ $label }}</option>
    @endforeach
  </select>
  <div class="form-text">Modern Card uses the detailed card layout. Classic Sales uses a compact sales-invoice style.</div>
</div>
<div class="mb-3">
  <label class="form-label">Description</label>
  <textarea name="description" class="form-control" rows="2" maxlength="1000">{{ old('description', $template->description ?? '') }}</textarea>
</div>
<div class="form-check mb-2">
  <input type="hidden" name="is_default" value="0">
  <input type="checkbox" name="is_default" value="1" class="form-check-input" id="{{ $prefix }}is_default" @checked(old('is_default', $template->is_default ?? false))>
  <label class="form-check-label" for="{{ $prefix }}is_default">Set as default template</label>
</div>
<div class="form-check">
  <input type="hidden" name="status" value="0">
  <input type="checkbox" name="status" value="1" class="form-check-input" id="{{ $prefix }}status" @checked(old('status', $template->status ?? true))>
  <label class="form-check-label" for="{{ $prefix }}status">Active</label>
</div>
