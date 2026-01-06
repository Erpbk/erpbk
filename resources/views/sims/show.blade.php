@extends('layouts.app')
@section('title', 'SIM Details')
@push('third_party_stylesheets')
<link href="https://fonts.googleapis.com/css2?family=Rockwell:wght@400;700&display=swap" rel="stylesheet">
<style>
/* Main layout */
.sim-detail-container {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    align-items: stretch; 
    height: calc(100vh - 200px); 
    min-height: 450px;
}

.left-container {
    flex: 0 0 35%; 
    max-width: 35%;
}

.right-container {
    flex: 0 0 64%; 
    max-width: 64%;
}

/* SIM info card */
.sim-info-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    height: 100%;
}

.sim-info-header {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e5e7eb;
}

.sim-info-header h3 {
    color: #1e40af;
    font-size: 1.4rem;
    margin-bottom: 5px;
}

.sim-number-display {
    margin-top: 15px;
}

.sim-number-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #dbeafe;
    padding: 12px 10px;
    border-radius: 10px;
    border: 1px solid #bfdbfe;
}

.sim-number-value {
    font-family: 'Courier New', monospace;
    font-size: 1.4rem;
    font-weight: 700;
    color: #1e40af;
    flex-grow: 1;
    text-align: right;
}

.sim-status-badge {
    font-size: 0.9rem;
    padding: 6px 14px;
    border-radius: 5px;
    font-weight: 600;
    color: white;
    margin-left: 1px;
    white-space: nowrap;
}

.info-label {
    font-weight: 600;
    color: #4b5563;
    font-size: 0.95rem;
    margin-right: 15px;
    white-space: nowrap;
}

.sim-info-body {
    margin-top: 20px;
}

.info-row {
    display: flex;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f3f4f6;
}

.info-label {
    flex: 0 0 40%;
    font-weight: 600;
    color: #4b5563;
    font-size: 0.95rem;
}

.info-value {
    flex: 0 0 50%;
    color: #111827;
    font-size: 0.95rem;
    font-weight: 100;
}

.info-value .badge {
    font-size: 0.8rem;
    padding: 4px 10px;
}

/* History table styles */
.history-card {
    background: white;
    border-radius: 12px;
    padding: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    border: 1px solid #e5e7eb;
    height: 100%;
    min-height: 0;
    display: flex;
    flex-direction: column;
}

.history-header {
    padding-top: 20px;
    padding-left: 15px;
    flex-shrink: 0;
}

.history-header h3 {
    color: #1e40af;
    font-size: 1.4rem;
}

/* Scrollable table container - FIXED HEIGHT */
.table-scroll-container {
    flex: 1;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 0;
    max-height: none;
}

.table-responsive {
    border-radius: 8px;
    overflow: hidden;
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow-x: hidden; /* Prevent horizontal scroll */
    overflow-y: auto; /* Only vertical scroll */
}

.left-container, .right-container {
    display: flex;
    flex-direction: column;
}

#simHistoryTable {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; 
    background: white;
    margin: 0;
}

#simHistoryTable thead {
    background: #dde0e3;
    position: sticky;
    top: 0;
    z-index: 50;
}

#simHistoryTable thead th {
    color: black;
    font-weight: 500;
    font-size: 0.85rem;
    padding: 16px 8px; /* Reduced padding */
    border: none;
    letter-spacing: 0.5px;
    text-align: center;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}


#simHistoryTable tbody tr {
    width: 100%;
    border-bottom: 1px solid #dddde2 !important;
}

#simHistoryTable tbody tr:hover {
    background-color: #f8f9ff;
}

#simHistoryTable tbody td {
    padding: 12px 8px; /* Reduced padding */
    color: #4a5568;
    font-size: 0.85rem; /* Slightly smaller font */
    font-weight: 400;
    text-align: center;
    vertical-align: middle;
    white-space: normal; /* Allow text wrapping */
    overflow: hidden;
    text-overflow: ellipsis;
    word-wrap: break-word;
}

/* Scrollbar styling - vertical only */
.table-responsive::-webkit-scrollbar {
    width: 6px; /* Thinner scrollbar */
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* Hide horizontal scrollbar */
.table-responsive::-webkit-scrollbar:horizontal {
    display: none;
}

/* Badges */
.bg-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.bg-info { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }
.bg-secondary { background: linear-gradient(135deg, #6b7280, #4b5563); color: white; }

/* Notes styles */
.notes-preview {
    max-width: 150px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    min-width: 100px;
}

.notes-preview:hover {
    background: #f3f4f6;
    tooltip: pointer;
}

.notes-full {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    z-index: 1000;
    max-width: 300px;
    word-wrap: break-word;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Empty state */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.empty-state h4 {
    margin-bottom: 10px;
    color: #374151;
}

/* Column widths for better distribution - ADJUSTED */
#simHistoryTable th:nth-child(1),
#simHistoryTable td:nth-child(1) {
    width: 15%; /* Rider */
    max-width: 120px;
}

#simHistoryTable th:nth-child(2),
#simHistoryTable td:nth-child(2) {
    width: 18%; /* Assigned Date */
    max-width: 150px;
}

#simHistoryTable th:nth-child(3),
#simHistoryTable td:nth-child(3) {
    width: 15%; /* Assigned By */
    max-width: 120px;
}

#simHistoryTable th:nth-child(4),
#simHistoryTable td:nth-child(4) {
    width: 18%; /* Return Date */
    max-width: 150px;
}

#simHistoryTable th:nth-child(5),
#simHistoryTable td:nth-child(5) {
    width: 15%; /* Returned By */
    max-width: 120px;
}

#simHistoryTable th:nth-child(6),
#simHistoryTable td:nth-child(6) {
    width: 19%; /* Notes */
    min-width: 100px;
    max-width: 150px;
}

/* Responsive */
@media (max-width: 992px) {
    .sim-detail-container {
        flex-direction: column;
    }
    
    .left-container,
    .right-container {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .sim-number-wrapper {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .sim-number-value {
        text-align: left;
        width: 100%;
    }
    
    .sim-status-badge {
        margin-left: 0;
        align-self: flex-end;
    }
    
    .table-scroll-container {
        max-height: 500px;
    }
}

/* Make the table responsive on smaller screens */
@media (max-width: 768px) {
    .table-scroll-container {
        max-height: 450px;
    }
    
    /* Adjust column widths for mobile */
    #simHistoryTable th:nth-child(n),
    #simHistoryTable td:nth-child(n) {
        width: auto !important;
        max-width: none;
        font-size: 0.8rem;
        padding: 10px 5px;
    }
}
</style>
@endpush

@section('content')
    <section class="content-header">
        <div class="px-3">
            <div class="row mb-2 align-items-center">
                <div class="col">
                    <h2 class="m-0">Sim Details</h2>
                </div>
                <div class="col-auto">
                    <a class="btn btn-primary" href="{{ route('sims.index') }}">
                        <i class="fa fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content">
        <div class="sim-detail-container">
            <!-- Left Container: SIM Information -->
            <div class="left-container">
                <div class="sim-info-card">
                    <div class="sim-info-header">
                        <h3>SIM Information</h3>
                        <div class="sim-number-display">
                            <div class="sim-number-wrapper">
                                @php
                                    $statusClass = 'bg-secondary';
                                    if($sims->status && $sims->status == 1){
                                        $statusClass = 'bg-success';
                                        $statusText = 'Active';
                                    } 
                                    elseif($sims->status && $sims->status == 0) {
                                        $statusText = 'Inactive';
                                        $statusClass = 'bg-danger';
                                    }
                                    else {
                                        $statusText = 'Unknown';
                                        $statusClass = 'bg-secondary';  
                                    }
                                @endphp
                                <span class="sim-status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                <a href="https://wa.me/{{ $sims->number }}"><span class="sim-number-value"><i class="fab fa-whatsapp"></i>{{ $sims->number ?? 'N/A' }}</span></a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="sim-info-body">
                        <!-- Company -->
                        <div class="info-row">
                            <div class="info-label">Company:</div>
                            <div class="info-value">
                                @if($sims->company)
                                    <span class="badge bg-info">{{ $sims->company }}</span>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        
                        <!-- Created By -->
                        <div class="info-row">
                            <div class="info-label">Created By:</div>
                            <div class="info-value">
                                @php
                                    $createdBy = App\Models\User::where('id', $sims->created_by)->first();
                                @endphp
                                {{ $createdBy ? $createdBy->name : 'N/A' }}
                            </div>
                        </div>
                        
                        <!-- Updated By -->
                        <div class="info-row">
                            <div class="info-label">Updated By:</div>
                            <div class="info-value">
                                @php
                                    $updatedBy = App\Models\User::where('id', $sims->updated_by)->first();
                                @endphp
                                {{ $updatedBy ? $updatedBy->name : 'N/A' }}
                            </div>
                        </div>
                        
                        <!-- EMI -->
                        <div class="info-row">
                            <div class="info-label">EMI:</div>
                            <div class="info-value">
                                {{ $sims->emi ?? 'N/A' }}
                            </div>
                        </div>
                        
                        <!-- Vendor -->
                        <div class="info-row">
                            <div class="info-label">Vendor:</div>
                            <div class="info-value">
                                {{ $sims->vendors->name ?? 'N/A' }}
                            </div>
                        </div>
                        
                        <!-- Created At -->
                        <div class="info-row">
                            <div class="info-label">Created At:</div>
                            <div class="info-value">
                                @if($sims->created_at)
                                    {{ \Carbon\Carbon::parse($sims->created_at)->format('d M, Y h:i A') }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        
                        <!-- Updated At -->
                        <div class="info-row">
                            <div class="info-label">Updated At:</div>
                            <div class="info-value">
                                @if($sims->updated_at)
                                    {{ \Carbon\Carbon::parse($sims->updated_at)->format('d M, Y h:i A') }}
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Container: SIM History -->
            <div class="right-container">
                <div class="history-card">
                    <div class="history-header">
                        <h3>SIM History</h3>
                    </div>
                    
                    <div class="table-scroll-container">
                        <div class="table-responsive">                            
                            @if(count($simHistories) === 0)
                            <div class="empty-state">
                                <h4>No SIM history found</h4>
                                <p>There are no SIM assignment records to display.</p>
                            </div>
                            @else
                                <table id="simHistoryTable">
                                    <thead>
                                        <tr>
                                            <th>Rider</th>
                                            <th>Assign Date</th>
                                            <th>Assign By</th>
                                            <th>Return Date</th>
                                            <th>Return By</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($simHistories as $history)
                                        <tr>
                                            @php
                                                $rider = App\Models\Riders::find($history->rider_id);
                                            @endphp
                                            <td>
                                                <a href="{{ route('riders.show', $rider->id) }}" 
                                                    class="table-link"
                                                    target="_blank">
                                                    {{ $rider ? $rider->name : '-' }}
                                                </a>
                                            </td>
                                            <td>
                                                <span class="date-display" data-toggle="tooltip" title="{{ $history->note_date }}">
                                                    {{ \Carbon\Carbon::parse($history->note_date)->format('d M, Y') }}
                                                </span>
                                            </td>
                                            @php
                                                $assignedBy = App\Models\User::find($history->assigned_by);
                                            @endphp
                                            <td>{{ $assignedBy ? $assignedBy->name : '-' }}</td>
                                            <td>
                                                @if($history->return_date)
                                                    <span class="date-display" data-toggle="tooltip" title="{{ $history->return_date }}">
                                                        {{ \Carbon\Carbon::parse($history->return_date)->format('d M, Y') }}
                                                    </span>
                                                @else
                                                    -
                                                @endif  
                                            </td>
                                            @php
                                                $returnedBy = App\Models\User::find($history->returned_by);
                                            @endphp
                                            <td>{{ $returnedBy ? $returnedBy->name : '-' }}</td>
                                            <td>
                                                @if($history->notes)
                                                    <div class="notes-container">
                                                        <span title="Click to Expand" class="notes-preview">{{ Str::limit($history->notes, 10) }}</span>
                                                        <div class="notes-full">{{ $history->notes }}</div>
                                                    </div>
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Show full notes on click
    document.querySelectorAll('.notes-preview').forEach(function (preview) {
        const container = preview.closest('.notes-container');
        const fullNotes = container.querySelector('.notes-full');
        
        preview.addEventListener('click', function (e) {
            e.stopPropagation();
            
            // Hide any other open notes
            document.querySelectorAll('.notes-full').forEach(function (notes) {
                if (notes !== fullNotes) {
                    notes.style.display = 'none';
                }
            });
            
            // Toggle current notes
            if (fullNotes.style.display === 'block') {
                fullNotes.style.display = 'none';
            } else {
                fullNotes.style.display = 'block';
                
                // Position the notes box
                const rect = preview.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                fullNotes.style.top = (rect.bottom + scrollTop + 5) + 'px';
                fullNotes.style.left = (rect.left - 100) + 'px';
            }
        });
    });
    
    // Close notes when clicking elsewhere
    document.addEventListener('click', function () {
        document.querySelectorAll('.notes-full').forEach(function (notes) {
            notes.style.display = 'none';
        });
    });
    
    // Prevent notes from closing when clicking inside them
    document.querySelectorAll('.notes-full').forEach(function (notes) {
        notes.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Adjust table height dynamically to fit container
    function adjustTableHeight() {
        const tableContainer = document.querySelector('.table-scroll-container');
        const historyCard = document.querySelector('.history-card');
        
        if (tableContainer && historyCard) {
            // Calculate available space in the card
            const cardRect = historyCard.getBoundingClientRect();
            const headerHeight = document.querySelector('.history-header').offsetHeight;
            const cardPadding = 20; // Reduced padding
            
            // Set height to fill the card with a small buffer
            const availableHeight = cardRect.height - headerHeight - cardPadding;
            tableContainer.style.height = Math.max(300, availableHeight - 10) + 'px';
        }
    }
    
    // Adjust on load and resize
    adjustTableHeight();
    window.addEventListener('resize', adjustTableHeight);
    
    // Also adjust after content loads
    setTimeout(adjustTableHeight, 100);
});
</script>
@endsection