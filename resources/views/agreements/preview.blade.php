<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Agreement Preview — {{ $template->template_name ?? 'Preview' }}</title>
  <style>
    body { margin: 0; font-family: system-ui, sans-serif; background: #f1f5f9; }
    .toolbar {
      position: sticky; top: 0; z-index: 10;
      background: #fff; border-bottom: 1px solid #e2e8f0;
      padding: 12px 20px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center;
      box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .toolbar strong { margin-right: auto; font-size: 14px; }
    .toolbar a, .toolbar button {
      padding: 8px 14px; font-size: 13px; cursor: pointer;
      border-radius: 6px; text-decoration: none; border: 1px solid #cbd5e1;
      background: #fff; color: #334155;
    }
    .toolbar .btn-primary { background: #2563eb; color: #fff; border-color: #2563eb; }
    .preview-frame { max-width: 210mm; margin: 20px auto; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,.1); }
    .preview-body { min-height: 200px; }
  </style>
</head>
<body>
  <div class="toolbar">
    <strong>{{ $template->template_name ?? 'Agreement Preview' }}</strong>
    <button type="button" onclick="window.print()" class="btn-primary">Print</button>
    @if(isset($rider) && $rider->exists)
    <a href="{{ route('agreements.pdf', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'template_id' => $template->id, 'agreement_date' => request('agreement_date', now()->format('Y-m-d')), 'download' => 1]) }}">Download PDF</a>
    @elseif($template->exists ?? false)
    <a href="{{ route('settings-panel.agreements.preview-pdf', ['company_slug' => request()->route('company_slug'), 'id' => $template->id]) }}">Download PDF</a>
    @endif
    <button type="button" onclick="window.close()">Close</button>
  </div>
  <div class="preview-frame">
    <div class="preview-body">
      {!! $html !!}
    </div>
  </div>
</body>
</html>
