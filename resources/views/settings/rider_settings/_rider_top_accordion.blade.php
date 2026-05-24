@include('settings.partials.top_bar.accordion', [
  'topBarCategories' => $riderTopCategories ?? collect(),
  'topBarEmptyMessage' => 'No Rider Top categories yet. Add your first category to begin.',
])
