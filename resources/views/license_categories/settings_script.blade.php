@php
$companySlug = $companySlug ?? (request()->route('company_slug') ?? session('company_slug'));
$routePrefix = $routePrefix ?? 'settings-panel.license-categories';
@endphp
<script>
(function() {
    var licenseCategorySortableInstance = null;

    function initLicenseCategorySortable() {
        if (typeof Sortable === 'undefined') return;
        var tbody = document.getElementById('license-categories-tbody');
        if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;
        if (licenseCategorySortableInstance) {
            licenseCategorySortableInstance.destroy();
        }
        licenseCategorySortableInstance = new Sortable(tbody, {
            handle: '.license-category-drag-handle',
            animation: 150,
            ghostClass: 'table-warning',
            onEnd: function() {
                var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(row) {
                    return row.getAttribute('data-id');
                });
                fetch("{{ route($routePrefix . '.reorder', ['company_slug' => $companySlug]) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ order: order })
                });
            }
        });
    }

    document.addEventListener('click', function(e) {
        var categoryDeleteBtn = e.target.closest('.js-license-category-delete-btn');
        if (categoryDeleteBtn) {
            var deleteUrl = categoryDeleteBtn.getAttribute('data-delete-url') || '';
            if (!deleteUrl) return;
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete category?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete'
                }).then(function(result) {
                    if (result.isConfirmed) {
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = deleteUrl;
                        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE">';
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else if (confirm('Delete this category?')) {
                window.location.href = deleteUrl;
            }
            return;
        }

        var categoryEditBtn = e.target.closest('.js-license-category-edit-btn');
        if (!categoryEditBtn) return;
        var editForm = document.getElementById('editLicenseCategoryForm');
        if (!editForm) return;
        var baseUrl = "{{ url('app/' . $companySlug . '/settings-panel/license-categories') }}";
        editForm.action = baseUrl + '/' + String(categoryEditBtn.dataset.id || '');
        document.getElementById('edit_license_category_name').value = categoryEditBtn.dataset.name || '';
        document.getElementById('edit_license_category_display_order').value = categoryEditBtn.dataset.displayOrder || '';
        var isDefault = String(categoryEditBtn.dataset.isDefault || '0') === '1';
        var activeWrap = document.getElementById('edit_license_category_active_wrap');
        if (activeWrap) activeWrap.style.display = isDefault ? 'none' : 'block';
        document.getElementById('edit_license_category_is_active').checked = String(categoryEditBtn.dataset.isActive || '0') === '1';
    });

    var licenseCategoryTabBtn = document.querySelector('[data-bs-target="#tab-license-categories"]');
    if (licenseCategoryTabBtn) {
        licenseCategoryTabBtn.addEventListener('shown.bs.tab', function() {
            setTimeout(initLicenseCategorySortable, 50);
        });
    }

    var hash = window.location.hash;
    if (hash === '#tab-license-categories' && licenseCategoryTabBtn) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(licenseCategoryTabBtn).show();
        }
        setTimeout(initLicenseCategorySortable, 100);
    }

    window.initLicenseCategorySortable = initLicenseCategorySortable;
})();
</script>
