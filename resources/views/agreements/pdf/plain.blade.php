<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $template->template_name ?? 'Agreement' }}</title>
  <style>
    @page { margin: 36pt 42pt 48pt 42pt; }
    * { box-sizing: border-box; }
    body {
      font-family: 'DejaVu Sans', Calibri, sans-serif;
      font-size: 9.5pt;
      color: #1e293b;
      line-height: 1.35;
      margin: 0;
    }
    .content { font-size: 9.5pt; line-height: 1.35; width: 100%; max-width: 100%; overflow-wrap: break-word; }
    .content p { margin: 0 0 6pt; }
    .content h2, .content h3 {
      font-size: 10.5pt;
      margin: 10pt 0 5pt;
      color: #1e293b;
    }
    .content table {
      width: 100% !important;
      max-width: 100% !important;
      table-layout: fixed;
      border-collapse: collapse;
      margin: 6pt 0;
    }
    .content table th, .content table td {
      padding: 4pt 6pt;
      font-size: 8.5pt;
      border: 1px solid #cbd5e1;
      overflow-wrap: anywhere;
      word-wrap: break-word;
      word-break: break-word;
    }
    .content ul, .content ol { margin: 4pt 0 6pt 16pt; padding: 0; }
    .content img { max-width: 100% !important; height: auto !important; }
    .content div, .content p, .content span, .content li {
      max-width: 100%;
      overflow-wrap: break-word;
      word-wrap: break-word;
    }
  </style>
</head>
<body>
  <div class="content">
    {!! $body !!}
  </div>
</body>
</html>
