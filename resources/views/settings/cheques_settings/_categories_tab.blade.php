{{-- Categories tab: same UI/UX as Rider settings --}}
<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
  <p class="text-muted small mb-0">Add, edit, reorder cheque categories. Custom categories can be deleted if they have no custom fields.</p>
  <button type="button" class="btn btn-primary btn-sm" id="btnAddRiderCategory" data-bs-toggle="modal" data-bs-target="#addRiderCategoryModal">
    <i class="ti ti-plus me-1"></i> Add Category
  </button>
</div>
<div class="table-responsive">
  <table class="table table-hover cheque-settings-table mb-0">
    <thead class="table-light">
      <tr>
        <th style="width: 36px;"></th>
        <th>#</th>
        <th>Label</th>
        <th>Slug</th>
        <th>Type</th>
        <th class="text-end" style="width: 160px;">Actions</th>
      </tr>
    </thead>
    <tbody id="riderCategoriesTbody">
      @include('settings.cheques_settings._categories_tbody', ['categories' => $categories])
    </tbody>
  </table>
</div>
