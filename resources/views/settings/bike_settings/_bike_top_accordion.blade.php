@include('settings.partials.top_bar.accordion', [
  'topBarCategories' => $bikeTopCategories ?? collect(),
  'topBarEmptyMessage' => 'No Vehicle Top categories yet. Add your first category to begin.',
])
