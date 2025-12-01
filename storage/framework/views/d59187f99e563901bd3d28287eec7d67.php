
<?php
$tableId = $tableId ?? 'dataTableBuilder';
$tableColumns = $tableColumns ?? [];
$exportRoute = $exportRoute ?? null;
$tableIdentifier = $tableIdentifier ?? 'default_table';
?>

<div id="columnControlSidebar" class="column-control-sidebar" style="z-index: 1111;">
    <div class="column-control-header">
        <h5><i class="fa fa-columns"></i> Column Control</h5>
        <button type="button" class="btn-close" id="closeColumnControlSidebar"></button>
    </div>
    <div class="column-control-body">
        
        <div class="mb-4">
            <h6 class="mb-3">
                <i class="fa fa-eye"></i> Column Visibility
                <small class="text-muted d-block">Check to show, uncheck to hide columns</small>
            </h6>
            <div class="column-list" id="columnList">
                <?php $__currentLoopData = $tableColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $column): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                $columnKey = $column['data'] ?? $column['key'] ?? $index;
                // Skip only search and control icons, but allow 'action' column to be controlled
                $isActionColumn = in_array($columnKey, ['search', 'control']) ||
                str_contains($columnKey, 'search') ||
                str_contains($columnKey, 'control');
                ?>

                <?php if(!$isActionColumn): ?>
                <div class="column-item" data-column-index="<?php echo e($index); ?>" data-column-key="<?php echo e($columnKey); ?>">
                    <div class="d-flex align-items-center p-2 border rounded mb-2 bg-light">
                        <div class="drag-handle me-2" style="cursor: grab;">
                            <i class="fa fa-grip-vertical text-muted"></i>
                        </div>
                        <div class="form-check me-2">
                            <input class="form-check-input column-visibility-checkbox"
                                type="checkbox"
                                checked
                                data-column-index="<?php echo e($index); ?>"
                                id="col_<?php echo e($index); ?>">
                        </div>
                        <label class="form-check-label flex-grow-1" for="col_<?php echo e($index); ?>">
                            <?php echo e($column['title'] ?? $column['name'] ?? 'Column ' . ($index + 1)); ?>

                        </label>
                        <small class="text-muted"><?php echo e($columnKey); ?></small>
                    </div>
                </div>
                <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        
        <div class="mb-4">
            <h6 class="mb-3"><i class="fa fa-bolt"></i> Quick Actions</h6>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm" id="showAllColumns">
                    <i class="fa fa-check-square"></i> Show All
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="hideAllColumns">
                    <i class="fa fa-square"></i> Hide All
                </button>
                <button type="button" class="btn btn-outline-info btn-sm" id="resetColumns">
                    <i class="fa fa-undo"></i> Reset to Default
                </button>
            </div>
        </div>

        
        <?php if($exportRoute): ?>
        <div class="mb-4">
            <h6 class="mb-3"><i class="fa fa-download"></i> Export Options</h6>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-success btn-sm export-btn" data-format="excel">
                    <i class="fa fa-file-excel"></i> Export to Excel
                </button>
                <button type="button" class="btn btn-info btn-sm export-btn" data-format="csv">
                    <i class="fa fa-file-csv"></i> Export to CSV
                </button>
                <button type="button" class="btn btn-danger btn-sm export-btn" data-format="pdf">
                    <i class="fa fa-file-pdf"></i> Export to PDF
                </button>
            </div>
            <small class="text-muted d-block mt-2">
                <i class="fa fa-info-circle"></i> Only visible columns will be exported in the current order
            </small>
        </div>
        <?php endif; ?>

        
        <div class="column-stats mt-4 p-3 bg-light rounded">
            <small class="text-muted">
                <strong>Visible:</strong> <span id="visibleCount"><?php echo e(count($tableColumns)); ?></span> of <span id="totalCount"><?php echo e(count($tableColumns)); ?></span> columns
            </small>
        </div>
    </div>
</div>


<div class="column-control-overlay" id="columnControlOverlay"></div>

<style>
    .column-control-sidebar {
        position: fixed;
        top: 0;
        right: -420px;
        width: 420px;
        height: 100%;
        background: #ffffff;
        box-shadow: -2px 0 8px rgba(0, 0, 0, .1);
        z-index: 1051;
        transition: right .3s ease;
        overflow-y: auto;
        border-left: 1px solid #dee2e6;
    }

    .column-control-sidebar.open {
        right: 0;
    }

    .column-control-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, .4);
        z-index: 1050;
        display: none;
    }

    .column-control-overlay.show {
        display: block;
    }

    .column-control-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .column-control-body {
        padding: 1rem;
        height: calc(100vh - 70px);
        overflow-y: auto;
    }

    .column-control-sidebar .btn-close {
        box-shadow: none;
    }

    .column-item {
        transition: all 0.2s ease;
    }

    .column-item:hover {
        transform: translateX(2px);
    }

    .column-item.dragging {
        opacity: 0.5;
        transform: rotate(2deg);
    }

    .drag-handle:hover {
        color: #495057 !important;
    }

    .drag-handle:active {
        cursor: grabbing !important;
    }

    .column-list {
        max-height: 400px;
        overflow-y: auto;
    }

    /* Custom scrollbar */
    .column-control-body::-webkit-scrollbar,
    .column-list::-webkit-scrollbar {
        width: 6px;
    }

    .column-control-body::-webkit-scrollbar-track,
    .column-list::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }

    .column-control-body::-webkit-scrollbar-thumb,
    .column-list::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }

    .column-control-body::-webkit-scrollbar-thumb:hover,
    .column-list::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }

    @media (max-width: 576px) {
        .column-control-sidebar {
            width: 100%;
            right: -100%;
        }
    }

    /* Animation for column hiding */
    .table th.column-hidden,
    .table td.column-hidden {
        display: none !important;
    }

    /* Ensure action column dropdowns are visible */
    .table td .dropdown {
        position: relative !important;
    }

    .table td .dropdown .dropdown-menu {
        position: absolute !important;
        z-index: 1050 !important;
    }

    .table td .dropdown .btn {
        visibility: visible !important;
        display: inline-block !important;
    }

    /* Sortable list styles */
    .column-item.sortable-chosen {
        transform: rotate(5deg);
    }

    .column-item.sortable-ghost {
        opacity: 0.3;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ColumnController = {
            tableId: '<?php echo e($tableId); ?>',
            tableIdentifier: '<?php echo e($tableIdentifier); ?>',
            exportRoute: '<?php echo e($exportRoute); ?>',
            columns: <?php echo json_encode($tableColumns, 15, 512) ?>,
            isInitialLoad: true, // Flag to prevent saving during initial load

            init() {
                this.setupEventListeners();
                this.initializeSortable();

                // Add a small delay to ensure table DOM is fully loaded
                setTimeout(() => {
                    this.loadUserSettings();
                    this.initializeDropdowns();
                }, 100);
            },

            setupEventListeners() {
                // Open/Close sidebar
                document.addEventListener('click', (e) => {
                    if (e.target.closest('.openColumnControlSidebar')) {
                        this.openSidebar();
                    }
                });

                document.getElementById('closeColumnControlSidebar')?.addEventListener('click', () => {
                    this.closeSidebar();
                });

                document.getElementById('columnControlOverlay')?.addEventListener('click', () => {
                    this.closeSidebar();
                });

                // Column visibility checkboxes
                document.querySelectorAll('.column-visibility-checkbox').forEach(checkbox => {
                    checkbox.addEventListener('change', (e) => {
                        // Mark as user interaction (not initial load)
                        const wasInitialLoad = this.isInitialLoad;
                        this.isInitialLoad = false;

                        this.toggleColumnVisibility(e.target.dataset.columnIndex, e.target.checked);

                        // Force save for user interactions
                        if (!wasInitialLoad) {
                            this.saveSettings();
                        }
                    });
                });

                // Quick actions
                document.getElementById('showAllColumns')?.addEventListener('click', () => {
                    this.showAllColumns();
                });

                document.getElementById('hideAllColumns')?.addEventListener('click', () => {
                    this.hideAllColumns();
                });

                document.getElementById('resetColumns')?.addEventListener('click', () => {
                    this.resetColumns();
                });

                // Export buttons
                document.querySelectorAll('.export-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        this.exportTable(e.target.dataset.format);
                    });
                });
            },

            initializeSortable() {
                if (typeof Sortable !== 'undefined') {
                    const columnList = document.getElementById('columnList');
                    if (columnList) {
                        new Sortable(columnList, {
                            handle: '.drag-handle',
                            animation: 150,
                            ghostClass: 'sortable-ghost',
                            chosenClass: 'sortable-chosen',
                            onEnd: (evt) => {
                                // Mark as user interaction
                                this.isInitialLoad = false;
                                this.reorderColumns(evt.oldIndex, evt.newIndex);
                            }
                        });
                    }
                }
            },

            openSidebar() {
                document.getElementById('columnControlSidebar').classList.add('open');
                document.getElementById('columnControlOverlay').classList.add('show');
            },

            closeSidebar() {
                document.getElementById('columnControlSidebar').classList.remove('open');
                document.getElementById('columnControlOverlay').classList.remove('show');
            },

            toggleColumnVisibility(columnIndex, isVisible) {
                const table = document.getElementById(this.tableId);
                if (!table) return;

                // Get total headers and check if this is a fixed action column
                const headerCells = table.querySelectorAll('thead th');
                const totalDataColumns = headerCells.length - 2; // Exclude last 2 fixed action columns (search, control)

                // Only toggle visibility for data columns (not action columns)
                if (columnIndex < totalDataColumns && headerCells[columnIndex]) {
                    headerCells[columnIndex].classList.toggle('column-hidden', !isVisible);
                }

                // Toggle body cells (only data cells, not action cells)
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    if (columnIndex < totalDataColumns && row.children[columnIndex]) {
                        row.children[columnIndex].classList.toggle('column-hidden', !isVisible);
                    }
                });

                this.updateColumnStats();

                // Only save settings if not during initial load
                if (!this.isInitialLoad) {
                    this.saveSettings();
                }
            },

            reorderColumns(oldIndex, newIndex) {
                // Store the new column order
                this.applyColumnOrder();

                // Only save settings if not during initial load
                if (!this.isInitialLoad) {
                    this.saveSettings();
                }
            },

            applyColumnOrder() {
                const table = document.getElementById(this.tableId);
                if (!table) return;

                // Get current column order from the sidebar
                const columnItems = document.querySelectorAll('.column-item');
                const newOrder = Array.from(columnItems).map(item =>
                    parseInt(item.dataset.columnIndex)
                );

                // Reorder header (but preserve last 2 action columns)
                const headerRow = table.querySelector('thead tr');
                const originalHeaders = Array.from(headerRow.children);

                // Separate data columns from fixed action columns (last 2 columns are fixed: search, control)
                const dataHeaders = originalHeaders.slice(0, -2);
                const actionHeaders = originalHeaders.slice(-2);

                // Reorder only the data columns
                const reorderedDataHeaders = newOrder
                    .filter(index => index < dataHeaders.length)
                    .map(index => dataHeaders[index])
                    .filter(cell => cell); // Remove undefined cells

                // Clear header and rebuild with reordered data + action columns
                headerRow.innerHTML = '';
                reorderedDataHeaders.forEach(cell => headerRow.appendChild(cell));
                actionHeaders.forEach(cell => headerRow.appendChild(cell));

                // Reorder body cells (but preserve last 2 action columns)
                const bodyRows = table.querySelectorAll('tbody tr');
                bodyRows.forEach(row => {
                    const originalCells = Array.from(row.children);

                    // Separate data cells from fixed action cells (last 2 cells are fixed: search, control)
                    const dataCells = originalCells.slice(0, -2);
                    const actionCells = originalCells.slice(-2);

                    // Reorder only the data cells
                    const reorderedDataCells = newOrder
                        .filter(index => index < dataCells.length)
                        .map(index => dataCells[index])
                        .filter(cell => cell); // Remove undefined cells

                    // Clear row and rebuild with reordered data + action cells
                    row.innerHTML = '';
                    reorderedDataCells.forEach(cell => row.appendChild(cell));
                    actionCells.forEach(cell => row.appendChild(cell));
                });
            },

            showAllColumns() {
                // Mark as user interaction
                this.isInitialLoad = false;

                document.querySelectorAll('.column-visibility-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                    this.toggleColumnVisibility(checkbox.dataset.columnIndex, true);
                });
            },

            hideAllColumns() {
                // Mark as user interaction
                this.isInitialLoad = false;

                // Keep at least the first column visible
                document.querySelectorAll('.column-visibility-checkbox').forEach((checkbox, index) => {
                    const shouldHide = index !== 0;
                    checkbox.checked = !shouldHide;
                    this.toggleColumnVisibility(checkbox.dataset.columnIndex, !shouldHide);
                });
            },

            resetColumns() {
                // Reset user settings in database
                fetch('<?php echo e(route("user-table-settings.reset")); ?>', {
                        method: 'DELETE',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            table_identifier: this.tableIdentifier
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.showNotification('Settings reset successfully', 'success');
                            // Apply default settings
                            this.applyDefaultSettings();
                            // Reload the page to ensure clean state
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            console.error('Failed to reset settings:', data.message);
                            this.showNotification('Failed to reset settings', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error resetting settings:', error);
                        this.showNotification('Error resetting settings', 'error');
                    });
            },

            updateColumnStats() {
                const visibleCount = document.querySelectorAll('.column-visibility-checkbox:checked').length;
                const totalCount = document.querySelectorAll('.column-visibility-checkbox').length;

                document.getElementById('visibleCount').textContent = visibleCount;
                document.getElementById('totalCount').textContent = totalCount;
            },

            exportTable(format) {
                if (!this.exportRoute) return;

                // Get visible columns (only data columns, not action columns)
                const visibleColumns = [];
                const columnOrder = [];

                document.querySelectorAll('.column-item').forEach(item => {
                    const checkbox = item.querySelector('.column-visibility-checkbox');
                    const columnKey = item.dataset.columnKey;

                    // Only include data columns (exclude search and control icons, but include action column)
                    if (columnKey && !columnKey.includes('search') && !columnKey.includes('control')) {
                        columnOrder.push(columnKey);

                        if (checkbox.checked) {
                            visibleColumns.push(columnKey);
                        }
                    }
                });

                // Create export URL with column parameters
                const url = new URL(this.exportRoute, window.location.origin);
                url.searchParams.set('format', format);
                url.searchParams.set('visible_columns', JSON.stringify(visibleColumns));
                url.searchParams.set('column_order', JSON.stringify(columnOrder));

                // Include current filter parameters if available
                const currentUrl = new URL(window.location);
                const filterParams = ['rider_id', 'name', 'fleet_supervisor', 'status', 'emirate_hub', 'quick_search'];
                filterParams.forEach(param => {
                    const value = currentUrl.searchParams.get(param);
                    if (value) {
                        url.searchParams.set(param, value);
                    }
                });

                // Trigger download
                window.location.href = url.toString();
            },

            saveSettings() {
                const settings = {
                    visible_columns: [],
                    column_order: []
                };

                document.querySelectorAll('.column-item').forEach(item => {
                    const checkbox = item.querySelector('.column-visibility-checkbox');
                    const columnKey = item.dataset.columnKey;

                    // Only save data columns (exclude search and control icons, but include action column)
                    if (columnKey && !columnKey.includes('search') && !columnKey.includes('control')) {
                        settings.column_order.push(columnKey);

                        if (checkbox.checked) {
                            settings.visible_columns.push(columnKey);
                        }
                    }
                });

                // Save to database via AJAX
                this.saveToDatabase(settings);
            },

            saveToDatabase(settings) {
                fetch('<?php echo e(route("user-table-settings.save")); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        },
                        body: JSON.stringify({
                            table_identifier: this.tableIdentifier,
                            visible_columns: settings.visible_columns,
                            column_order: settings.column_order
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            console.error('Failed to save settings:', data.message);
                            this.showNotification('Failed to save column settings', 'error');
                        } else {
                            this.showNotification('Column settings saved', 'success');
                        }
                    })
                    .catch(error => {
                        console.error('Error saving settings:', error);
                        this.showNotification('Error saving column settings', 'error');
                    });
            },

            loadUserSettings() {
                // Load settings from database via AJAX
                fetch(`<?php echo e(route("user-table-settings.get")); ?>?table_identifier=${encodeURIComponent(this.tableIdentifier)}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            this.applyUserSettings(data.data);
                        }
                    })
                    .catch(error => {
                        console.warn('Failed to load user table settings:', error);
                        // Apply default settings if loading fails
                        this.applyDefaultSettings();
                    });
            },

            applyUserSettings(settings) {
                try {
                    // Apply visibility settings
                    if (settings.visible_columns) {
                        document.querySelectorAll('.column-visibility-checkbox').forEach(checkbox => {
                            const columnKey = checkbox.closest('.column-item').dataset.columnKey;
                            const isVisible = settings.visible_columns.includes(columnKey);

                            checkbox.checked = isVisible;
                            this.toggleColumnVisibility(checkbox.dataset.columnIndex, isVisible);
                        });
                    }

                    // Apply column order if available
                    if (settings.column_order) {
                        this.applyColumnOrderFromSettings(settings.column_order);
                    }

                    this.updateColumnStats();

                    // Mark initial load as complete
                    setTimeout(() => {
                        this.isInitialLoad = false;
                    }, 500);

                } catch (e) {
                    console.warn('Failed to apply user settings:', e);
                    this.applyDefaultSettings();
                }
            },

            applyDefaultSettings() {
                // Show all columns by default
                document.querySelectorAll('.column-visibility-checkbox').forEach(checkbox => {
                    checkbox.checked = true;
                    this.toggleColumnVisibility(checkbox.dataset.columnIndex, true);
                });
                this.updateColumnStats();

                // Mark initial load as complete
                setTimeout(() => {
                    this.isInitialLoad = false;
                }, 500);
            },

            applyColumnOrderFromSettings(columnOrder) {
                const columnList = document.getElementById('columnList');
                if (!columnList) return;

                // Create a map of current items
                const itemsMap = new Map();
                const currentItems = Array.from(columnList.querySelectorAll('.column-item'));

                currentItems.forEach(item => {
                    const columnKey = item.dataset.columnKey;
                    itemsMap.set(columnKey, item);
                });

                // Clear the list
                columnList.innerHTML = '';

                // Add items in the specified order
                columnOrder.forEach(columnKey => {
                    const item = itemsMap.get(columnKey);
                    if (item) {
                        columnList.appendChild(item);
                    }
                });

                // Add any remaining items that weren't in the saved order
                itemsMap.forEach((item, columnKey) => {
                    if (!columnOrder.includes(columnKey)) {
                        columnList.appendChild(item);
                    }
                });

                // Apply the column order to the actual table
                this.applyColumnOrder();
            },

            // Method to reapply settings after table content updates
            reapplySettings() {
                // Reload user settings and apply them
                this.loadUserSettings();

                // Reinitialize Bootstrap dropdowns after table update
                this.initializeDropdowns();
            },

            // Initialize Bootstrap dropdowns
            initializeDropdowns() {
                // Wait a bit for the DOM to be ready
                setTimeout(() => {
                    const dropdownElements = document.querySelectorAll('[data-bs-toggle="dropdown"]');
                    dropdownElements.forEach(element => {
                        // Remove any existing Bootstrap dropdown instance
                        const existingDropdown = bootstrap.Dropdown.getInstance(element);
                        if (existingDropdown) {
                            existingDropdown.dispose();
                        }

                        // Create new Bootstrap dropdown instance
                        new bootstrap.Dropdown(element);
                    });

                    console.log('Initialized', dropdownElements.length, 'dropdowns');
                }, 200);
            },

            // Debug method to check table structure
            debugTableStructure() {
                const table = document.getElementById(this.tableId);
                if (!table) {
                    console.log('Table not found');
                    return;
                }

                const headers = table.querySelectorAll('thead th');
                console.log('Total headers:', headers.length);
                console.log('Data columns (including Actions):', headers.length - 2);
                console.log('Fixed action columns (search, control):', 2);

                headers.forEach((header, index) => {
                    console.log(`Column ${index}:`, header.textContent.trim());
                });
            },

            showNotification(message, type = 'info') {
                // Create notification element
                const notification = document.createElement('div');
                notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show`;
                notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;';

                notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

                document.body.appendChild(notification);

                // Auto remove after 3 seconds
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 3000);
            }
        };

        // Initialize the column controller
        ColumnController.init();

        // Make it globally accessible
        window.ColumnController = ColumnController;
    });
</script><?php /**PATH /var/www/laravel/resources/views/components/column-control-panel.blade.php ENDPATH**/ ?>