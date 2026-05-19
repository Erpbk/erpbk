@include('settings.partials.top_bar.accordion', [
  'topBarCategories' => $employeeTopCategories ?? collect(),
  'topBarEmptyMessage' => 'No Employee Top categories yet. Add your first category to begin.',
])
