@extends('layouts.app')
@section('title', 'SIM Details')
@push('third_party_stylesheets')
<style>
    /* Minimal custom CSS only for special cases */

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
    }

    .notes-full {
        display: none;
        position: fixed;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        z-index: 9000;
        max-width: 300px;
        word-wrap: break-word;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    .table th {
        white-space: nowrap;
    }

    /* Hide scrollbar but keep functionality */
    .table-responsive::-webkit-scrollbar {
        display: none;
    }

    /* For Firefox */
    .table-responsive {
        scrollbar-width: none;
    }

    /* For IE/Edge */
    .table-responsive {
        -ms-overflow-style: none;
    }
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h3>Sim Details</h3>
        </div>
        <div class="col-sm-6 text-end">
            <a class="btn btn-primary" href="{{ route('sims.index') }}">
                <i class="fa fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</section>

<div class="content">
    <div class="row ">
        <!-- Left Column: SIM Information -->
        <div class="col-lg-4 col-md-5">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h4 class="text-primary my-0">SIM Information</h4>
                </div>
                <div class="card-body">
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

                    <!-- SIM Number and Status -->
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background: #dbeafe">
                        <span class="badge {{ $statusClass }} px-3 py-2">{{ $statusText }}</span>
                        <a href="https://wa.me/{{ $sims->number }}" class="text-decoration-none">
                            <span class="sim-number-value text-primary fs-5">
                                <i class="fab fa-whatsapp"></i> {{ $sims->number ?? 'N/A' }}
                            </span>
                        </a>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Company:</div>
                        <div class="col-7">
                            @if($sims->company)
                            <span class="badge bg-primary">{{ $sims->company }}</span>
                            @else
                            N/A
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Created By:</div>
                        <div class="col-7">
                            @php
                            $createdBy = App\Models\User::where('id', $sims->created_by)->first();
                            @endphp
                            {{ $createdBy ? $createdBy->name : 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Updated By:</div>
                        <div class="col-7">
                            @php
                            $updatedBy = App\Models\User::where('id', $sims->updated_by)->first();
                            @endphp
                            {{ $updatedBy ? $updatedBy->name : 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">EMI:</div>
                        <div class="col-7">{{ $sims->emi ?? 'N/A' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Vendor:</div>
                        <div class="col-7">{{ $sims->vendors->name ?? 'N/A' }}</div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Created At:</div>
                        <div class="col-7">
                            @if($sims->created_at)
                            {{ \Carbon\Carbon::parse($sims->created_at)->format('d M, Y h:i A') }}
                            @else
                            N/A
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Updated At:</div>
                        <div class="col-7">
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

        <!-- Right Column: SIM History -->
        <div class="col-lg-8 col-md-7">
            <div class="card h-100 shadow-sm">
                <div class="card-header">
                    <h4 class="text-primary mb-0">SIM History</h4>
                </div>
                <div class="card-body p-0">
                    @if(count($simHistories) === 0)
                    <div class="text-center py-5">
                        <h4 class="text-muted">No SIM history found</h4>
                        <p class="text-muted">There are no SIM assignment records to display.</p>
                    </div>
                    @else
                    <div class="table-responsive" style="max-height: calc(100vh - 250px);">
                        <table class="table table-hover mb-0 text-center">
                            <thead class="thead-light" style="background: #dde0e3; position: sticky; top: 0; z-index: 50;">
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
                                            class="text-decoration-none"
                                            target="_blank">
                                            {{ $rider ? $rider->name : '-' }}
                                        </a>
                                    </td>
                                    <td>
                                        <span data-toggle="tooltip" title="{{ $history->note_date }}">
                                            {{ \Carbon\Carbon::parse($history->note_date)->format('d M, Y') }}
                                        </span>
                                    </td>
                                    @php
                                    $assignedBy = App\Models\User::find($history->assigned_by);
                                    @endphp
                                    <td>{{ $assignedBy ? $assignedBy->name : '-' }}</td>
                                    <td>
                                        @if($history->return_date)
                                        <span data-toggle="tooltip" title="{{ $history->return_date }}">
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
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Bootstrap tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Track the currently opened notes element
        let currentOpenNotes = null;

        // Show full notes on click
        document.querySelectorAll('.notes-preview').forEach(function(preview) {
            preview.addEventListener('click', function(e) {
                e.stopPropagation();
                
                const container = preview.closest('.notes-container');
                const fullNotes = container.querySelector('.notes-full');
                
                // If clicking the same note, close it
                if (currentOpenNotes === fullNotes && fullNotes.style.display === 'block') {
                    fullNotes.style.display = 'none';
                    currentOpenNotes = null;
                    return;
                }
                
                // Close any previously opened notes
                if (currentOpenNotes) {
                    currentOpenNotes.style.display = 'none';
                }
                
                // Open the clicked notes
                fullNotes.style.display = 'block';
                currentOpenNotes = fullNotes;
                
                // Position the notes box relative to the preview
                const rect = preview.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
                
                // Calculate position - try to keep within viewport
                let left = rect.left + scrollLeft;
                let top = rect.bottom + scrollTop + 5;
                
                // Adjust if too far right
                const maxWidth = 300; // Same as CSS max-width
                if (left + maxWidth > window.innerWidth + scrollLeft) {
                    left = window.innerWidth + scrollLeft - maxWidth - 10;
                }
                
                // Adjust if too far bottom
                const notesHeight = fullNotes.offsetHeight;
                if (top + notesHeight > window.innerHeight + scrollTop) {
                    top = rect.top + scrollTop - notesHeight - 5;
                }
                
                fullNotes.style.top = top + 'px';
                fullNotes.style.left = left + 'px';
            });
        });

        // Close notes when clicking anywhere else in the document
        document.addEventListener('click', function(e) {
            // Check if click is inside a notes container
            const isClickInsideNotes = e.target.closest('.notes-container') || 
                                      e.target.closest('.notes-full');
            
            // If click is outside notes and there's an open note, close it
            if (!isClickInsideNotes && currentOpenNotes) {
                currentOpenNotes.style.display = 'none';
                currentOpenNotes = null;
            }
        });

        // Prevent notes from closing when clicking inside them
        document.querySelectorAll('.notes-full').forEach(function(notes) {
            notes.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
    });
</script>
@endsection