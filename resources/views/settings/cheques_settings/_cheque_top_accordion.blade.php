@include('settings.partials.top_bar.accordion', [
  'topBarCategories' => $chequeTopCategories ?? collect(),
  'topBarEmptyMessage' => 'No Cheque Top categories yet. Add your first category to begin.',
])
