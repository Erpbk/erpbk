@php
$companySlug = $companySlug ?? (request()->route('company_slug') ?? session('company_slug'));
$routePrefix = $routePrefix ?? 'settings-panel.visa-renewal-categories';
@endphp
<script>
(function() {
    var visaRenewalSortableInstance = null;

    function initVisaRenewalSortable() {
        if (typeof Sortable === 'undefined') return;
        var tbody = document.getElementById('visa-renewal-categories-tbody');
        if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;
        if (visaRenewalSortableInstance) {
            visaRenewalSortableInstance.destroy();
        }
        visaRenewalSortableInstance = new Sortable(tbody, {
            handle: '.visa-renewal-drag-handle',
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
        var renewalDeleteBtn = e.target.closest('.js-visa-renewal-delete-btn');
        if (renewalDeleteBtn) {
            var deleteUrl = renewalDeleteBtn.getAttribute('data-delete-url') || '';
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

        var renewalEditBtn = e.target.closest('.js-visa-renewal-edit-btn');
        if (!renewalEditBtn) return;
        var editForm = document.getElementById('editVisaRenewalCategoryForm');
        if (!editForm) return;
        var baseUrl = "{{ url('app/' . $companySlug . '/settings-panel/visa-renewal-categories') }}";
        editForm.action = baseUrl + '/' + String(renewalEditBtn.dataset.id || '');
        document.getElementById('edit_visa_renewal_name').value = renewalEditBtn.dataset.name || '';
        document.getElementById('edit_visa_renewal_display_order').value = renewalEditBtn.dataset.displayOrder || '';
        var isDefault = String(renewalEditBtn.dataset.isDefault || '0') === '1';
        var activeWrap = document.getElementById('edit_visa_renewal_active_wrap');
        if (activeWrap) activeWrap.style.display = isDefault ? 'none' : 'block';
        document.getElementById('edit_visa_renewal_is_active').checked = String(renewalEditBtn.dataset.isActive || '0') === '1';
    });

    var visaRenewalTabBtn = document.querySelector('[data-bs-target="#tab-visa-renewal-categories"]');
    if (visaRenewalTabBtn) {
        visaRenewalTabBtn.addEventListener('shown.bs.tab', function() {
            setTimeout(initVisaRenewalSortable, 50);
        });
    }

    var hash = window.location.hash;
    if (hash === '#tab-visa-renewal-categories' && visaRenewalTabBtn) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(visaRenewalTabBtn).show();
        }
        setTimeout(initVisaRenewalSortable, 100);
    }

    window.initVisaRenewalSortable = initVisaRenewalSortable;
})();
</script>
