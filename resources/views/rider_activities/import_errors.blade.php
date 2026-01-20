@extends('layouts.app')

@section('title', $importType . ' Import Errors')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">
                        <i class="fa fa-exclamation-triangle"></i>
                        {{ $importType }} Import Errors - Detailed Report
                    </h4>
                </div>
                <div class="card-body">
                    @if(empty($errors) && empty($summary))
                    <div class="alert alert-info">
                        <i class="fa fa-info-circle"></i>
                        <strong>No errors to display.</strong>
                        <p class="mb-0">There are no recent import errors. This page shows errors from your last import attempt.</p>
                    </div>
                    <div class="text-center mt-4">
                        <a href="{{ $importRoute }}" class="btn btn-primary">
                            <i class="fa fa-upload"></i> Go to Import Page
                        </a>
                    </div>
                    @else
                    <!-- Import Summary Card -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0">{{ $summary['total_rows'] ?? 0 }}</h2>
                                    <p class="mb-0">Total Rows</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0">{{ $summary['success_count'] ?? 0 }}</h2>
                                    <p class="mb-0">Imported Successfully</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0">{{ $summary['skipped_count'] ?? 0 }}</h2>
                                    <p class="mb-0">Skipped</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h2 class="mb-0">{{ $summary['error_count'] ?? count($errors ?? []) }}</h2>
                                    <p class="mb-0">Errors</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(!empty($missingRecords))
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card bg-warning">
                                <div class="card-body text-center">
                                    <h2 class="mb-0">{{ count($missingRecords) }}</h2>
                                    <p class="mb-0">Missing Records (Riders Not Found)</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Date Filter Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-filter"></i> Filter by Date
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="{{ route('rider.activities_import_errors') }}" class="row">
                                <div class="form-group col-md-6">
                                    <label for="from_date_range">From Date (Quick Select)</label>
                                    <select class="form-control" id="from_date_range" name="from_date_range">
                                        <option value="" selected>Select</option>
                                        <option value="Today" {{ request('from_date_range') == 'Today' ? 'selected' : '' }}>Today</option>
                                        <option value="Yesterday" {{ request('from_date_range') == 'Yesterday' ? 'selected' : '' }}>Yesterday</option>
                                        <option value="Last 7 Days" {{ request('from_date_range') == 'Last 7 Days' ? 'selected' : '' }}>Last 7 Days</option>
                                        <option value="Last 30 Days" {{ request('from_date_range') == 'Last 30 Days' ? 'selected' : '' }}>Last 30 Days</option>
                                        <option value="Last 90 Days" {{ request('from_date_range') == 'Last 90 Days' ? 'selected' : '' }}>Last 90 Days</option>
                                    </select>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="from_date">From Date</label>
                                    <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="to_date">To Date</label>
                                    <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
                                </div>
                                <div class="form-group col-md-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-filter"></i> Apply Filter
                                    </button>
                                    <a href="{{ route('rider.activities_import_errors') }}" class="btn btn-secondary">
                                        <i class="fa fa-times"></i> Clear Filter
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div class="btn-action-group">
                            <button onclick="window.print()" class="btn btn-secondary">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                            <button onclick="exportToExcel()" class="btn btn-success">
                                <i class="fa fa-file-excel"></i> Export Errors to Excel
                            </button>
                        </div>
                        <a href="{{ $importRoute }}" class="btn btn-primary">
                            <i class="fa fa-arrow-left"></i> Back to Import
                        </a>
                    </div>

                    @if(!empty($missingRecords))
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fa fa-exclamation-triangle"></i>
                                Missing Records List ({{ count($missingRecords) }} records skipped)
                            </h5>
                            <small class="text-muted">These records were skipped because the Rider ID does not exist in the system.</small>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mb-0" id="missingRecordsTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Excel Row</th>
                                            <th>Rider ID</th>
                                            <th>Date</th>
                                            <th>Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($missingRecords as $index => $record)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-info">Row {{ $record['row'] ?? 'N/A' }}</span>
                                            </td>
                                            <td><code>{{ $record['rider_id'] ?? 'N/A' }}</code></td>
                                            <td>{{ $record['date'] ?? 'N/A' }}</td>
                                            <td><span class="badge badge-warning">{{ $record['message'] ?? 'Rider ID does not exist in system' }}</span></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if(!empty($errors))
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0">
                                <i class="fa fa-list"></i>
                                Critical Errors ({{ count($errors) }} errors found)
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mb-0" id="errorTable">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th class="text-center">#</th>
                                            <th class="text-center">Excel Row</th>
                                            <th>Error Category</th>
                                            <th>Specific Issue</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($errors as $index => $error)
                                        <tr>
                                            <td class="text-center">{{ $index + 1 }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-info">Row {{ $error['row'] ?? 'N/A' }}</span>
                                            </td>
                                            <td><span class="badge badge-danger">{{ $error['error_type'] ?? 'N/A' }}</span></td>
                                            <td>{{ $error['message'] ?? '-' }}</td>
                                            <td><code>{{ $error['rider_id'] ?? $error['payout_type'] ?? '-' }}</code></td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @elseif(empty($missingRecords))
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle me-2"></i>No import errors were found for the latest upload.
                    </div>
                    @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        // Auto-fill from_date when from_date_range is selected
        $('#from_date_range').on('change', function() {
            const selectedValue = $(this).val();
            if (selectedValue === 'Today') {
                $('#from_date').val(new Date().toISOString().split('T')[0]);
            } else if (selectedValue === 'Yesterday') {
                $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().split('T')[0]);
            } else if (selectedValue === 'Last 7 Days') {
                $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 7)).toISOString().split('T')[0]);
            } else if (selectedValue === 'Last 30 Days') {
                $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0]);
            } else if (selectedValue === 'Last 90 Days') {
                $('#from_date').val(new Date(new Date().setDate(new Date().getDate() - 90)).toISOString().split('T')[0]);
            }
        });
    });

    // Export to Excel function
    function exportToExcel() {
        // Check if errors table exists
        var table = document.getElementById('errorTable');
        var missingTable = document.getElementById('missingRecordsTable');

        // If neither table exists, show an error message
        if (!table && !missingTable) {
            alert('No tables found to export.');
            return;
        }

        try {
            var html = '';
            if (missingTable) {
                html += '<h2>Missing Records</h2>' + missingTable.outerHTML + '<br><br>';
            }
            if (table) {
                html += '<h2>Critical Errors</h2>' + table.outerHTML;
            }

            var url = 'data:application/vnd.ms-excel;base64,' + btoa(unescape(encodeURIComponent(html)));
            var downloadLink = document.createElement("a");
            downloadLink.href = url;

            downloadLink.download = 'rider_activities_import_report_' + new Date().getTime() + '.xls';

            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        } catch (error) {
            console.error('Excel Export Error:', error);

            // Provide more specific error messages
            var errorMessage = 'An error occurred while exporting to Excel.';
            if (error.message) {
                errorMessage += ' Details: ' + error.message;
            }

            alert(errorMessage);
        }
    }
</script>
@endsection

<style>
    @media print {

        .btn,
        .card-header,
        nav,
        .sidebar {
            display: none !important;
        }

        .card {
            border: 1px solid #000 !important;
            margin-bottom: 20px !important;
        }

        .table {
            font-size: 10px !important;
        }
    }

    .badge {
        font-size: 12px;
        padding: 5px 10px;
    }

    code {
        background: #f4f4f4;
        padding: 2px 6px;
        border-radius: 3px;
        font-size: 13px;
    }

    .btn-action-group>* {
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
    }

    .error-raw-data pre {
        max-height: 260px;
        overflow: auto;
        background: transparent;
        border: none;
        padding: 0;
        font-size: 0.8rem;
    }
</style>