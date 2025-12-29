@extends('layouts.app')

@section('title', 'Missing Salik Records')
@push('third_party_stylesheets')
<style> 
   .total-card {
        flex: 1 1 calc(15% - 8px) !important;
    }
    
    .missing-records h3 {
        color: #ffffff !important;
    }

    #missingTable thead tr th {
        color: white !important;
        text-align: center;
    }
</style>
@endpush

@section('content')
<div >
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="ti ti-alert-triangle text-warning me-2"></i>
                            Missing Salik Records
                        </h4>
                        <div class="d-flex gap-2">
                            <a href="{{ route('salik.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left me-1"></i>Back to Salik
                            </a>
                            <a href="{{ route('salik.import.form', 1) }}" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i>Import Salik
                            </a>
                            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#analyzeExcelModal">
                                <i class="ti ti-file-search me-1"></i>Analyze Excel
                            </button>
                            <a href="{{ route('salik.export.missing.records') }}" class="btn btn-success">
                                <i class="ti ti-download me-1"></i>Export to Excel
                            </a>
                            <button type="button" class="btn btn-danger" onclick="clearOldFailedImports()">
                                <i class="ti ti-trash me-1"></i>Clear Old Records
                            </button>
                        </div>
                    </div>
                </div>

                <div class="totals-cards">
                    <div class="total-card total-blue">
                        <div class="label"><i class="fa fa-ticket"></i>Total Missing Record</div>
                        <div class="value" id="total_orders">{{ isset($failedImports) ? $failedImports->total() : count($missingRecords) }}</div>
                    </div>
                    <div class="total-card total-1">
                        <div class="label"><i class="fa fa-times-circle"></i>Total Amount</div>
                        <div class="value" id="avg_ontime">AED {{ number_format($totalAmount ?? 0, 2) }}</div>
                    </div>
                    <div class="total-card total-red">
                        <div class="label"><i class="fas fa-stamp"></i>Missing Bikes</div>
                        <div class="value" id="total_rejected">{{ count(array_filter($missingRecords, fn($r) => $r['reason'] === 'No bike found with this plate number')) }}</div>
                    </div>
                    <div class="total-card total-green">
                        <div class="label"><i class="far fa-money-bill-alt"></i>Missing Riders</div>
                        <div class="value" id="total_hours">{{ count(array_filter($missingRecords, fn($r) => $r['reason'] === 'No rider assigned for this trip date')) }}</div>
                    </div>
                    <div class="total-card total-2">
                        <div class="label"><i class="far fa-money-bill-alt"></i>Missing Accounts</div>
                        <div class="value" id="total_hours">{{ count(array_filter($missingRecords, fn($r) => $r['reason'] === 'No account found for rider')) }}</div>
                    </div>
                    <div class="total-card total-3">
                        <div class="label"><i class="far fa-money-bill-alt"></i>Successful Imports</div>
                        <div class="value" id="total_hours">{{ $importStats['successful_imports'] ?? 0 }}</div>
                    </div>
                </div>

                <div class="card-body ">

                    <!-- Missing Records Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" id="missingTable">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Transaction ID</th>
                                    <th>Trip Date</th>
                                    <th>Plate Number</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                    <th>Details</th>
                                    <th>Row #</th>
                                    <th>Import Date</th>
                                    <th>Suggested Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($missingRecords as $index => $record)
                                <tr class="missing-record-row">
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <span class="badge bg-primary">{{ $record['transaction_id'] }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">
                                            {{ \App\Helpers\General::DateFormat($record['trip_date']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">{{ $record['plate_number'] }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success">
                                            AED {{ number_format($record['amount'], 2) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">{{ $record['status'] }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $record['reason'] }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $record['details'] }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $record['row_number'] ?? 'N/A' }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ isset($record['import_date']) ? \App\Helpers\General::DateTimeFormat($record['import_date']) : 'N/A' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-info">{{ $record['suggested_action'] }}</small>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-4">
                                        <i class="ti ti-check-circle text-success me-2"></i>
                                        No missing records found! All Salik records have been successfully imported.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if(count($missingRecords) > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total Amount:</td>
                                    <td class="fw-bold text-success">
                                        AED {{ number_format($totalAmount ?? 0, 2) }}
                                    </td>
                                    <td colspan="6"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>

                    </div>

                    <!-- Pagination -->
                    @if(isset($failedImports) && $failedImports->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-muted">
                                Showing {{ $failedImports->firstItem() }} to {{ $failedImports->lastItem() }} of {{ $failedImports->total() }} results
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <label for="per_page" class="form-label mb-0 text-muted">Per page:</label>
                                <select id="per_page" class="form-select form-select-sm" style="width: auto;" onchange="changePerPage(this.value)">
                                    <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                                    <option value="15" {{ request('per_page') == 15 || !request('per_page') ? 'selected' : '' }}>15</option>
                                    <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                                    <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                    <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            {{ $failedImports->links() }}
                        </div>
                    </div>
                    @endif

                    <!-- Export Options -->
                    @if(count($missingRecords) > 0)
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Export Missing Records</h6>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('salik.export.missing.records') }}" class="btn btn-success">
                                            <i class="ti ti-download me-1"></i>Export to Excel
                                        </a>
                                        <button type="button" class="btn btn-info" onclick="window.print()">
                                            <i class="ti ti-printer me-1"></i>Print Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Excel Analysis Modal -->
<div class="modal fade" id="analyzeExcelModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Analyze Excel File for Missing Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="analyzeExcelForm" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="excel_file" class="form-label">Select Excel File</label>
                        <input type="file" class="form-control" id="excel_file" name="excel_file" accept=".xlsx,.xls,.csv" required>
                        <div class="form-text">Upload your Salik Excel file to analyze for potential issues before import.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search me-1"></i>Analyze File
                        </button>
                    </div>
                </form>

                <!-- Analysis Results -->
                <div id="analysisResults" class="mt-4" style="display: none;">
                    <h6>Analysis Results</h6>
                    <div id="analysisContent"></div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    // Function to change per page count
    function changePerPage(perPage) {
        const url = new URL(window.location);
        url.searchParams.set('per_page', perPage);
        window.location.href = url.toString();
    }

    // Global function for clearing failed imports - Version 1.1
    function clearOldFailedImports() {
        const clearAll = confirm('Do you want to clear ALL failed import records?\n\nClick OK to clear ALL records\nClick Cancel to clear only old records (older than 30 days)');

        if (clearAll || confirm('Are you sure you want to clear old failed import records (older than 30 days)? This action cannot be undone.')) {
            $.ajax({
                url: '{{ route("salik.clear.failed.imports") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    clear_all: clearAll
                },
                success: function(response) {
                    if (response.success) {
                        alert('Success: ' + response.message);
                        location.reload(); // Reload the page to show updated data
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'An error occurred while clearing records';
                    alert('Error: ' + errorMsg);
                }
            });
        }
    }

    // Global function for displaying analysis results
    function displayAnalysisResults(response) {
        const resultsDiv = $('#analysisResults');
        const contentDiv = $('#analysisContent');

        let html = `
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h4>${response.summary.high_severity}</h4>
                        <small>High Severity Issues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <h4>${response.summary.medium_severity}</h4>
                        <small>Medium Severity Issues</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h4>${response.summary.low_severity}</h4>
                        <small>Low Severity Issues</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <strong>Total Rows Analyzed:</strong> ${response.total_rows}
        </div>
    `;

        if (response.potential_issues.length > 0) {
            html += `
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>Row</th>
                            <th>Transaction ID</th>
                            <th>Plate Number</th>
                            <th>Trip Date</th>
                            <th>Amount</th>
                            <th>Issue</th>
                            <th>Severity</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

            response.potential_issues.forEach(function(issue) {
                const severityClass = issue.severity === 'High' ? 'danger' :
                    issue.severity === 'Medium' ? 'warning' : 'info';

                html += `
                <tr>
                    <td>${issue.row}</td>
                    <td>${issue.transaction_id || 'N/A'}</td>
                    <td>${issue.plate_number || 'N/A'}</td>
                    <td>${issue.trip_date || 'N/A'}</td>
                    <td>${issue.amount || 'N/A'}</td>
                    <td>${issue.issue}</td>
                    <td><span class="badge bg-${severityClass}">${issue.severity}</span></td>
                </tr>
            `;
            });

            html += `
                    </tbody>
                                        </table>
                    </div>


        `;
        } else {
            html += `
            <div class="alert alert-success">
                <i class="ti ti-check-circle me-2"></i>
                <strong>Great!</strong> No issues found in the Excel file. It's ready for import.
            </div>
        `;
        }

        contentDiv.html(html);
        resultsDiv.show();
    }

    $(document).ready(function() {
        // Handle Excel analysis form submission
        $('#analyzeExcelForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();

            // Show loading state
            submitBtn.html('<i class="ti ti-loader me-1"></i>Analyzing...').prop('disabled', true);

            $.ajax({
                url: '{{ route("salik.analyze.excel") }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        displayAnalysisResults(response);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'An error occurred while analyzing the file';
                    alert('Error: ' + errorMsg);
                },
                complete: function() {
                    // Reset button state
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
@endsection

@push('styles')
<style>
    .missing-record-row:hover {
        background-color: #fff3cd !important;
    }

    .badge {
        font-size: 0.8em;
    }

    .table th {
        font-size: 0.9em;
        font-weight: 600;
    }

    .table td {
        font-size: 0.9em;
        vertical-align: middle;
    }

    @media print {

        .btn,
        .card-header {
            display: none !important;
        }
    }
</style>
@endpush