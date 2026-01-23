{{-- <div class="card">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">Fuel Card Information</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <tbody class="text-start">
                <tr>
                    <th style="width: 30%;" class="ps-4">Card Number</th>
                    <td class="fw-bold text-primary">{{ $fuelCard->card_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th class="ps-4">Card Type</th>
                    <td>
                        <span class="badge bg-primary">
                            {{ $fuelCard->card_type ?? 'Not specified' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Status</th>
                    <td>
                        <span class="badge {{ $fuelCard->status == 'Active' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($fuelCard->status ?? 'inactive') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Assigned To</th>
                    <td>
                        <i class="fas fa-user me-1 text-muted"></i>
                        {{ $fuelCard->rider ? ($fuelCard->rider->rider_id.'-'.$fuelCard->rider->name) : 'Unassigned' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Created By</th>
                    <td>
                        <i class="fas fa-user-plus me-1 text-muted"></i>
                        {{ App\Models\User::find($fuelCard->created_by)->name ?? 'System' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Updated By</th>
                    <td>
                        <i class="fas fa-user-edit me-1 text-muted"></i>
                        {{ App\Models\User::find($fuelCard->updated_by)->name ?? 'System' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Created At</th>
                    <td>
                        <i class="fas fa-calendar-plus me-1 text-muted"></i>
                        {{ $fuelCard->created_at ? $fuelCard->created_at->format('M d, Y h:i A') : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Updated At</th>
                    <td>
                        <i class="fas fa-calendar-check me-1 text-muted"></i>
                        {{ $fuelCard->updated_at ? $fuelCard->updated_at->format('M d, Y h:i A') : 'N/A' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div> --}}


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
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="row mb-2">
        <div class="col-sm-6">
            <h3>Fuel Card Details</h3>
        </div>
        <div class="col-sm-6 text-end">
            <a class="btn btn-primary" href="{{ route('fuelCards.index') }}">
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
                    <h4 class="text-primary my-0">Card Information</h4>
                </div>
                <div class="card-body">
                    @php
                    $statusClass = 'bg-secondary';
                    if($card->status && $card->status == 'Active') {
                        $statusClass = 'bg-success';
                        $statusText = 'Active';
                    }
                    else {
                        $statusText = 'Inactive';
                        $statusClass = 'bg-danger';
                    }
                    @endphp

                    <!-- SIM Number and Status -->
                    <div class="d-flex justify-content-between align-items-center mb-4 p-3 rounded" style="background: #dbeafe">
                        <span class="badge {{ $statusClass }} px-3 py-2">{{ $statusText }}</span>
                        <span class="sim-number-value text-primary fs-5">
                            {{ $card->card_number ?? 'N/A' }}
                        </span>
                        {{-- <a href="https://wa.me/{{ $card->card_number }}" class="text-decoration-none">
                            <span class="sim-number-value text-primary fs-5">
                                <i class="fab fa-whatsapp"></i> {{ $card->card_number ?? 'N/A' }}
                            </span>
                        </a> --}}
                    </div>
                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Card Type:</div>
                        <div class="col-7">
                            @if($card->card_type)
                            <span class="badge bg-primary">{{ $card->card_type }}</span>
                            @else
                            N/A
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Created By:</div>
                        <div class="col-7">
                            @php
                            $createdBy = App\Models\User::where('id', $card->created_by)->first();
                            @endphp
                            {{ $createdBy ? $createdBy->name : 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Updated By:</div>
                        <div class="col-7">
                            @php
                            $updatedBy = App\Models\User::where('id', $card->updated_by)->first();
                            @endphp
                            {{ $updatedBy ? $updatedBy->name : 'N/A' }}
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Created At:</div>
                        <div class="col-7">
                            @if($card->created_at)
                            {{ \Carbon\Carbon::parse($card->created_at)->format('d M, Y h:i A') }}
                            @else
                            N/A
                            @endif
                        </div>
                    </div>

                    <div class="row mb-2">
                        <div class="col-5 font-weight-bold text-muted">Updated At:</div>
                        <div class="col-7">
                            @if($card->updated_at)
                            {{ \Carbon\Carbon::parse($card->updated_at)->format('d M, Y h:i A') }}
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
                    <h4 class="text-primary mb-0">Card History</h4>
                </div>
                <div class="card-body p-0">
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
                                @foreach($histories as $history)
                                <tr>
                                    <td>
                                        <a href="{{ route('riders.show', $history->rider->rider_id) }}"
                                            class="text-decoration-none"
                                            target="_blank">
                                            {{ $history->rider ? $history->rider->name : '-' }}
                                        </a>
                                    </td>
                                    <td>
                                        <span data-toggle="tooltip" title="{{ $history->assign_date }}">
                                            {{ \Carbon\Carbon::parse($history->assign_date)->format('d M, Y') }}
                                        </span>
                                    </td>
                                    <td>{{ $history->assignedBy ? $history->assignedBy->name : '-' }}</td>
                                    <td>
                                        @if($history->return_date)
                                        <span data-toggle="tooltip" title="{{ $history->return_date }}">
                                            {{ \Carbon\Carbon::parse($history->return_date)->format('d M, Y') }}
                                        </span>
                                        @else
                                        -
                                        @endif
                                    </td>
                                    <td>{{ $history->returnedBy ? $history->returnedBy->name : '-' }}</td>
                                    <td>
                                        @if($history->note)
                                        <div class="notes-container">
                                            <span title="Click to Expand" class="notes-preview">{{ Str::limit($history->note, 10) }}</span>
                                            <div class="notes-full">{{ $history->note }}</div>
                                        </div>
                                        @else
                                        -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if($histories->isEmpty())
                            <div class="text-center py-5">
                                <h4 class="text-muted">No Card history found</h4>
                                <p class="text-muted">There are no Card assignment records to display.</p>
                            </div>
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