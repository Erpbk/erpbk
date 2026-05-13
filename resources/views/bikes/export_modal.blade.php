<div class="modal-header">
    <h5 class="modal-title">Export Bikes</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Export Bikes to Excel/CSV/PDF</h3>
                    </div>
                    <div class="card-body">
                        <form id="exportBikesForm" action="{{ route('bikes.export-download') }}" method="GET">
                            <div class="form-group">
                                <label for="format">Export Format</label>
                                <select class="form-control" id="format" name="format">
                                    <option value="excel">Excel (.xlsx)</option>
                                    <option value="csv">CSV</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Select Columns to Export</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        @php
                                        $columns = App\Exports\CustomizableBikeExport::getAvailableColumns();
                                        $half = ceil(count($columns) / 2);
                                        $firstHalf = array_slice($columns, 0, $half, true);
                                        $secondHalf = array_slice($columns, $half, null, true);
                                        @endphp

                                        @foreach($firstHalf as $key => $column)
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input export-column"
                                                id="column_{{ $key }}" name="columns[]" value="{{ $key }}" checked>
                                            <label class="custom-control-label" for="column_{{ $key }}">
                                                {{ $column['title'] }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    <div class="col-md-6">
                                        @foreach($secondHalf as $key => $column)
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input export-column"
                                                id="column_{{ $key }}" name="columns[]" value="{{ $key }}" checked>
                                            <label class="custom-control-label" for="column_{{ $key }}">
                                                {{ $column['title'] }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Apply Current Filters</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="apply_filters" name="apply_filters" checked>
                                    <label class="custom-control-label" for="apply_filters">Include current filters in export</label>
                                </div>
                                <small class="form-text text-muted">
                                    If checked, the current filters applied to the bikes list will be used for the export.
                                </small>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="exportSubmitBtn">
                                    <i class="fas fa-download"></i> Export Bikes
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Export Options</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-info-circle"></i> Export Information</h5>
                            <ul class="mb-0">
                                <li>Select the format for your export (Excel, CSV, or PDF)</li>
                                <li>Choose which columns to include in the export</li>
                                <li>Apply current filters to limit the exported data</li>
                                <li>Large exports may take some time to generate</li>
                            </ul>
                        </div>

                        <div class="form-group">
                            <label>Quick Actions</label>
                            <div class="btn-group-vertical w-100">
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="selectAllColumns">
                                    <i class="fas fa-check-square"></i> Select All Columns
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm mb-2" id="deselectAllColumns">
                                    <i class="fas fa-square"></i> Deselect All Columns
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="selectEssentialColumns">
                                    <i class="fas fa-star"></i> Essential Columns Only
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Handle select/deselect all columns
        $('#selectAllColumns').on('click', function() {
            $('.export-column').prop('checked', true);
        });

        $('#deselectAllColumns').on('click', function() {
            $('.export-column').prop('checked', false);
        });

        // Handle select essential columns only
        $('#selectEssentialColumns').on('click', function() {
            // First uncheck all
            $('.export-column').prop('checked', false);

            // Then check essential columns
            var essentialColumns = ['bike_code', 'plate', 'rider_name', 'rider_id', 'emirates', 'warehouse', 'status', 'expiry_date'];
            essentialColumns.forEach(function(column) {
                $('#column_' + column).prop('checked', true);
            });
        });

        // Handle form submission
        $('#exportBikesForm').on('submit', function(e) {
            // Get selected columns
            var selectedColumns = [];
            $('.export-column:checked').each(function() {
                selectedColumns.push($(this).val());
            });

            // Check if at least one column is selected
            if (selectedColumns.length === 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'No Columns Selected',
                    text: 'Please select at least one column to export.',
                    confirmButtonText: 'OK'
                });
                return false;
            }

            // Add selected columns to form as JSON
            $('<input>').attr({
                type: 'hidden',
                name: 'visible_columns',
                value: JSON.stringify(selectedColumns)
            }).appendTo($(this));

            // Add column order to form as JSON (using the same order as selected)
            $('<input>').attr({
                type: 'hidden',
                name: 'column_order',
                value: JSON.stringify(selectedColumns)
            }).appendTo($(this));

            // Show loading indicator
            $('#exportSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Exporting...');

            // Let the form submit normally (will download the file)
            return true;
        });
    });
</script>

<style>
    .custom-control {
        margin-bottom: 8px;
    }

    .alert-info ul {
        padding-left: 20px;
        margin-bottom: 0;
    }

    .btn-group-vertical {
        width: 100%;
    }

    .modal-dialog {
        max-width: 90%;
    }
</style>