@include('agreements.pdf.partials.page-decor', [
  'pageWidthMm' => $pageWidthMm ?? 210,
  'pageHeightMm' => $pageHeightMm ?? 297,
])
@include('agreements.pdf.partials.page-header')
