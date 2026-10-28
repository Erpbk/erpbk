<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script type="text/javascript">
    var visaIndexUrl = @json($visaAjaxIndexUrl ?? route(($visaRoute ?? 'settings-panel.visa-statuses') . '.index', array_filter(['company_slug' => request()->route('company_slug') ?? session('company_slug') ?: null])));
    var selectedCategoryId = "{{ (int) ($selectedCategoryId ?? 0) }}";
    var visaEmbeddedInModuleSettings = {{ !empty($embeddedInModuleSettings) ? 'true' : 'false' }};
    var visaModuleSettingsBaseUrl = @json($moduleSettingsVisaUrl ?? '');

    function syncCreateModalCategoryChecks(categoryId) {
        var selected = String(categoryId || '');
        document.querySelectorAll('.js-create-visa-cat-check').forEach(function(cb) {
            cb.checked = selected !== '' && String(cb.value) === selected;
        });
    }

    function populateVisaStatusEditModal(editBtn) {
        var config = document.getElementById('visa-status-manager-config');
        var form = document.getElementById('editVisaStatusForm');
        if (!form || !editBtn) return;
        var editUrlTemplate = (config && config.getAttribute('data-edit-url-template')) || '';
        var updateBase = form.getAttribute('data-update-base') || '';
        var id = String(editBtn.dataset.id || '');
        if (editUrlTemplate) {
            form.action = editUrlTemplate.replace('__ID__', id);
        } else if (updateBase) {
            form.action = updateBase.replace(/\/$/, '') + '/' + id;
        }
        document.getElementById('editVisaStatusName').value = editBtn.dataset.name || '';
        document.getElementById('editVisaStatusCode').value = editBtn.dataset.code || '';
        document.getElementById('editVisaStatusCategory').value = editBtn.dataset.category || 'Other';
        var selectedIds = String(editBtn.dataset.visaRenewalCategoryIds || '')
            .split(',')
            .map(function(v) { return String(v || '').trim(); })
            .filter(Boolean);
        document.querySelectorAll('.js-edit-visa-cat-check').forEach(function(cb) {
            cb.checked = selectedIds.indexOf(String(cb.value)) !== -1;
        });
        document.getElementById('editVisaStatusDefaultFee').value = editBtn.dataset.defaultFee || 0;
        document.getElementById('editVisaStatusDisplayOrder').value = editBtn.dataset.displayOrder || '';
        document.getElementById('editVisaStatusDescription').value = editBtn.dataset.description || '';
        document.getElementById('editVisaStatusIsRequired').checked = String(editBtn.dataset.isRequired || '0') === '1';
        document.getElementById('editVisaStatusIsActive').checked = String(editBtn.dataset.isActive || '0') === '1';
    }

    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function deleteVisaStatusAjax(url, triggerBtn) {
        return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'DELETE'
                })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                }).catch(function() {
                    return {
                        ok: response.ok,
                        data: {
                            success: false,
                            message: 'Invalid server response.'
                        }
                    };
                });
            })
            .then(function(result) {
                if (!result.ok || !result.data || result.data.success !== true) {
                    throw new Error((result.data && result.data.message) ? result.data.message : 'Delete failed.');
                }
                var row = triggerBtn ? triggerBtn.closest('tr[data-id]') : null;
                if (row) {
                    row.remove();
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted',
                    text: result.data.message || 'Visa status deleted successfully.',
                    timer: 1600,
                    showConfirmButton: false
                });
                return result.data;
            });
    }

    function initSortable() {
        var tbody = document.getElementById('visa-statuses-tbody');
        if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;
        var reorderUrl = '{{ route($visaRoute . ".reorder") }}';
        var token = '{{ csrf_token() }}';
        if (window.visaStatusSortable) {
            window.visaStatusSortable.destroy();
        }
        window.visaStatusSortable = new Sortable(tbody, {
            handle: '.visa-drag-handle',
            animation: 150,
            ghostClass: 'table-warning',
            onEnd: function(evt) {
                var rows = tbody.querySelectorAll('tr[data-id]');
                var order = Array.from(rows).map(function(row) {
                    return row.getAttribute('data-id');
                });
                fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            category_id: parseInt(selectedCategoryId || '0', 10) || 0,
                            order: order
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            var toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            toast.fire({
                                icon: 'success',
                                title: 'Order saved.'
                            });
                            var idx = 1;
                            rows.forEach(function(row) {
                                var orderCell = row.cells[1];
                                if (orderCell) orderCell.textContent = idx++;
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Could not save order.'
                            });
                        }
                    })
                    .catch(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not save order.'
                        });
                    });
            }
        });
    }

    function loadCategoryStatuses(categoryId, pushState) {
        $('#loading-overlay').show();
        selectedCategoryId = String(categoryId || '');
        $('#filter_category_id').val(selectedCategoryId);
        var url = visaIndexUrl + (selectedCategoryId ? ('?category_id=' + encodeURIComponent(selectedCategoryId)) : '');
        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(data) {
                $('#table-data').html(data.tableData);
                syncCreateModalCategoryChecks(selectedCategoryId);
                $('.visa-cat-item').removeClass('active');
                $('.visa-cat-item[data-category-id="' + selectedCategoryId + '"]').addClass('active');
                var nameEl = $('.visa-cat-item.active .visa-cat-name').clone();
                nameEl.find('.badge').remove();
                var catName = $.trim(nameEl.text()) || 'Visa';
                $('#visa-status-heading').html('<i class="ti ti-list-check me-1"></i> ' + catName + ' tickets');
                $('#visa-status-subtitle').text('Tickets for this category are generated from these statuses only. Same ticket ID is shared across categories.');
                if (pushState !== false) {
                    var historyUrl = url;
                    if (visaEmbeddedInModuleSettings && visaModuleSettingsBaseUrl) {
                        historyUrl = visaModuleSettingsBaseUrl
                            + (selectedCategoryId ? ('?category_id=' + encodeURIComponent(selectedCategoryId)) : '')
                            + '#tab-visa-status-management';
                    }
                    history.pushState(null, '', historyUrl);
                }
                initSortable();
                $('#loading-overlay').hide();
            },
            error: function() {
                $('#loading-overlay').hide();
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initSortable();
        syncCreateModalCategoryChecks(selectedCategoryId);

        $(document).on('click', '.js-visa-status-edit-btn', function() {
            populateVisaStatusEditModal(this);
        });

        $('#createVisaStatusModal').on('show.bs.modal', function() {
            syncCreateModalCategoryChecks(selectedCategoryId);
        });

        $(document).on('click', '.visa-cat-item', function(e) {
            if ($(e.target).closest('button').length) {
                return;
            }
            e.preventDefault();
            var categoryId = this.getAttribute('data-category-id');
            if (!categoryId) return;
            loadCategoryStatuses(categoryId, true);
        });

        $(document).on('click', '.js-visa-status-delete-btn', function(e) {
            e.preventDefault();
            var btn = this;
            var deleteUrl = btn.getAttribute('data-delete-url') || '';
            if (!deleteUrl) return;
            Swal.fire({
                title: 'Delete from all categories?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                deleteVisaStatusAjax(deleteUrl, btn).catch(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (err && err.message) ? err.message : 'Could not delete visa status.'
                    });
                });
            });
        });

        $(document).on('click', '.js-visa-status-unassign-btn', function(e) {
            e.preventDefault();
            var btn = this;
            var unassignUrl = btn.getAttribute('data-unassign-url') || '';
            var categoryId = btn.getAttribute('data-category-id') || selectedCategoryId || '';
            if (!unassignUrl || !categoryId) return;
            Swal.fire({
                title: 'Remove from this category?',
                text: 'The ticket will remain in other categories.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, remove'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                fetch(unassignUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ category_id: parseInt(categoryId, 10) || 0 })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (!data.success) throw new Error(data.message || 'Unassign failed');
                    var row = btn.closest('tr[data-id]');
                    if (row) row.remove();
                    Swal.fire({ icon: 'success', title: 'Removed', text: data.message || 'Removed from category.', timer: 1600, showConfirmButton: false });
                }).catch(function(err) {
                    Swal.fire({ icon: 'error', title: 'Error', text: (err && err.message) ? err.message : 'Could not remove from category.' });
                });
            });
        });

        $(document).on('click', '.openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
        });
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');

            var formData = $(this).serialize();
            var url = formData ? visaIndexUrl + '?' + formData : visaIndexUrl;

            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    history.pushState(null, '', url);
                    initSortable();
                    $('#loading-overlay').hide();
                },
                error: function() {
                    $('#loading-overlay').hide();
                }
            });
        });

        $('#category, #status, #is_required').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: 'Select'
        });
    });
</script>
@include('visa_renewal_categories.settings_script')
