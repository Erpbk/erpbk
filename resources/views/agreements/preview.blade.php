<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Agreement Preview — {{ $template->template_name ?? 'Preview' }}</title>
  <style>
    body {
      margin: 0;
      font-family: system-ui, sans-serif;
      background: #f1f5f9;
    }

    .toolbar {
      position: sticky;
      top: 0;
      z-index: 10;
      background: #fff;
      border-bottom: 1px solid #e2e8f0;
      padding: 12px 20px;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .06);
    }

    .toolbar strong {
      margin-right: auto;
      font-size: 14px;
    }

    .toolbar a,
    .toolbar button {
      padding: 8px 14px;
      font-size: 13px;
      cursor: pointer;
      border-radius: 6px;
      text-decoration: none;
      border: 1px solid #cbd5e1;
      background: #fff;
      color: #334155;
    }

    .toolbar .btn-primary {
      background: #2563eb;
      color: #fff;
      border-color: #2563eb;
    }

    .toolbar .letterhead-badge {
      font-size: 12px;
      padding: 4px 10px;
      border-radius: 999px;
      background: #e0f2fe;
      color: #0369a1;
      border: 1px solid #bae6fd;
    }

    .preview-shell {
      max-width: calc(210mm + 40px);
      margin: 20px auto;
      padding: 0 12px 24px;
    }

    #agreement-preview-frame {
      display: block;
      width: 210mm;
      max-width: 100%;
      min-height: 80vh;
      margin: 0 auto;
      border: 0;
      background: transparent;
    }

    @media print {
      body {
        background: #fff;
      }

      .toolbar {
        display: none !important;
      }

      .preview-shell {
        margin: 0;
        padding: 0;
        max-width: none;
      }

      #agreement-preview-frame {
        width: 100%;
        max-width: none;
        min-height: 0;
        height: auto;
        margin: 0;
      }
    }
  </style>
</head>

<body>
  @php
  $withLetterhead = $withLetterhead ?? request()->boolean('letterhead', true);
  $letterheadParam = $withLetterhead ? 1 : 0;
  @endphp
  <div class="toolbar">
    <strong>{{ $template->template_name ?? 'Agreement Preview' }}</strong>
    <span class="letterhead-badge">{{ $withLetterhead ? 'With letterhead' : 'Without letterhead' }}</span>
    <button type="button" id="btn-print-agreement" class="btn-primary">Print</button>
    @php
    $pdfDownloadUrl = $pdfDownloadUrl ?? null;
    if (! $pdfDownloadUrl && isset($rider) && $rider instanceof \Illuminate\Database\Eloquent\Model && $rider->exists) {
    $pdfDownloadUrl = route('rider-agreements.pdf', [
    'company_slug' => request()->route('company_slug'),
    'riderId' => $rider->id,
    'template_id' => $template->id,
    'agreement_date' => request('agreement_date', now()->format('Y-m-d')),
    'download' => 1,
    'letterhead' => $letterheadParam,
    ]);
    }
    if (! $pdfDownloadUrl && ! empty($template->id)) {
    $pdfDownloadUrl = route('agreements.preview-pdf', [
    'company_slug' => request()->route('company_slug'),
    'id' => $template->id,
    'letterhead' => $letterheadParam,
    ]);
    }
    @endphp
    @if($pdfDownloadUrl)
    <a href="{{ $pdfDownloadUrl }}" id="btn-download-pdf">Download PDF</a>
    @endif
    <button type="button" onclick="window.close()">Close</button>
  </div>
  <div class="preview-shell">
    <iframe id="agreement-preview-frame" title="Agreement preview"></iframe>
  </div>

  @include('agreements.partials.print-letterhead-dialog')

  <script>
    (function() {
      var html = @json($html);
      var withLetterhead = @json($withLetterhead);
      var frame = document.getElementById('agreement-preview-frame');
      var dialog = document.getElementById('letterhead-print-dialog');
      var cancelBtn = document.getElementById('letterhead-print-cancel');
      var pendingPrint = new URLSearchParams(window.location.search).get('autoprint') === '1';

      var previewUrl = null;

      function blobUrl(markup) {
        return URL.createObjectURL(new Blob([markup], { type: 'text/html;charset=utf-8' }));
      }

      function resizeFrame() {
        try {
          var doc = frame.contentDocument || frame.contentWindow.document;
          var height = Math.max(doc.body.scrollHeight, doc.documentElement.scrollHeight);
          frame.style.height = (height + 24) + 'px';
        } catch (e) {}
      }

      previewUrl = blobUrl(html);
      frame.src = previewUrl;
      frame.onload = function() {
        resizeFrame();
        if (pendingPrint) {
          pendingPrint = false;
          runPrint(withLetterhead);
        }
      };

      window.addEventListener('beforeunload', function() {
        if (previewUrl) {
          URL.revokeObjectURL(previewUrl);
        }
      });

      function runPrint(useLetterhead) {
        try {
          var win = frame.contentWindow;
          if (typeof win.__agreementRepaginate === 'function') {
            win.__agreementRepaginate();
          }
          win.focus();
          setTimeout(function() {
            win.print();
          }, 150);
        } catch (e) {
          try {
            frame.contentWindow.print();
          } catch (err) {
            window.print();
          }
        }
      }

      function reloadWithLetterhead(useLetterhead, autoprint) {
        var url = new URL(window.location.href);
        url.searchParams.set('letterhead', useLetterhead ? '1' : '0');
        if (autoprint) {
          url.searchParams.set('autoprint', '1');
        } else {
          url.searchParams.delete('autoprint');
        }
        window.location.href = url.toString();
      }

      function showPrintDialog() {
        if (!dialog || typeof dialog.showModal !== 'function') {
          var useLetterhead = window.confirm('Print with letterhead?\n\nOK = with letterhead\nCancel = without letterhead');
          if (useLetterhead === withLetterhead) {
            runPrint(useLetterhead);
          } else {
            reloadWithLetterhead(useLetterhead, true);
          }
          return;
        }

        dialog.showModal();
      }

      if (dialog) {
        dialog.addEventListener('close', function() {
          var choice = dialog.returnValue;
          if (choice !== 'with' && choice !== 'without') {
            return;
          }

          var useLetterhead = choice === 'with';
          if (useLetterhead === withLetterhead) {
            runPrint(useLetterhead);
          } else {
            reloadWithLetterhead(useLetterhead, true);
          }
        });
      }

      if (cancelBtn && dialog) {
        cancelBtn.addEventListener('click', function() {
          dialog.close('cancel');
        });
      }

      document.getElementById('btn-print-agreement').addEventListener('click', showPrintDialog);
      window.printPreview = showPrintDialog;
    })();
  </script>
</body>

</html>
