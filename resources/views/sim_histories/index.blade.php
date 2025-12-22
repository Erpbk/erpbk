@push('third_party_stylesheets')
<link href="https://fonts.googleapis.com/css2?family=Rockwell:wght@400;700&display=swap" rel="stylesheet">
<style>
/* Design 1: Modern Minimalist */
#dataTableBuilder {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.03);
}

#dataTableBuilder thead {
    background: #d4d4d6;
    backdrop-filter: blur(10px);
}

#dataTableBuilder thead th {
    color: white;
    font-weight: 500;
    font-size: 0.85rem;
    padding: 18px 15px;
    border: none;
    letter-spacing: 0.5px;
    text-align: center;
}

#dataTableBuilder tbody tr {
    border-bottom: 1px solid rgba(0,0,0,0.04);
    transition: background-color 0.2s ease;
}

#dataTableBuilder tbody tr:hover {
    background-color: #f8f9ff;
}

#dataTableBuilder tbody td {
    padding: 16px 15px;
    color: #4a5568;
    font-size: 0.9rem;
    font-weight: 400;
}

/* Minimalist badges */
.badge {
    padding: 6px 12px;
    border-radius: 5px;
    font-size: 0.75rem;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.bg-success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
.bg-danger { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }
.bg-warning { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
.bg-info { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: white; }

.wa-forward {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: #e4e6eb;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.15s ease;
}

.wa-forward i {
    color: #6b7280;
    font-size: 18px;
}

.wa-forward:hover {
    background-color: #d1d5db;
    transform: scale(1.05);
}

/* SIM specific styles */
.sim-number {
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: #1e40af;
    background: #dbeafe;
    padding: 4px 12px;
    border-radius: 6px;
    display: inline-block;
}

.sim-company {
    font-size: 0.8rem;
    color: #6b7280;
    margin-top: 2px;
}

.sim-status-active {
    color: #059669;
    background: #d1fae5;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.sim-status-inactive {
    color: #dc2626;
    background: #fee2e2;
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
}

.user-name {
    color: #4b5563;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-state h4 {
    margin-bottom: 10px;
    color: #374151;
}

.table-link {
    color: #3b82f6;
    text-decoration: none;
    transition: color 0.2s ease;
}

.table-link:hover {
    color: #1d4ed8;
    text-decoration: underline;
}

.date-display {
    cursor: default;
}

.notes-preview {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
}

.notes-full {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    z-index: 1000;
    max-width: 300px;
    word-wrap: break-word;
}
</style>
@endpush

@section('content')
<div class="table-responsive">
    <table id="dataTableBuilder">
        <thead>
            <tr>
                <th>Rider</th>
                <th>Assigned Date</th>
                <th>Assigned By</th>
                <th>Return Date</th>
                <th>Returned By</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($simHistories as $history)
            <tr class="text-center">
                @php
                    $rider = APP\Models\Riders::Where('id', $history->rider_id)->first();
                @endphp
                <td>{{ $rider ? $rider->name : 'N/A' }}</td>
                <td>
                    <span class="date-display" data-toggle="tooltip" title="{{ $history->assigned_date }}">
                        {{ \Carbon\Carbon::parse($history->assigned_date)->format('d M, Y h:i A') }}
                    </span>
                </td>
                @php
                    $assignedBy = App\Models\User::where('id', $history->assigned_by)->first(); 
                @endphp
                <td>{{ $assignedBy ? $assignedBy->name : 'N/A' }}</td>
                <td>
                    @if($history->return_date)
                        <span class="date-display" data-toggle="tooltip" title="{{ $history->return_date }}">
                            {{ \Carbon\Carbon::parse($history->return_date)->format('d M, Y h:i A') }}
                        </span>
                    @else
                        N/A
                    @endif  
                </td>
                @php
                    $returnedBy = App\Models\User::where('id', $history->returned_by)->first();
                @endphp
                <td>{{ $returnedBy ? $returnedBy->name : 'N/A' }}</td>
                <td>
                    @if($history->notes)
                        <div class="notes-container">
                            <span class="notes-preview">{{ Str::limit($history->notes, 20) }}</span>
                            <div class="notes-full" data-notes="{{ $history->notes }}">{{ $history->notes }}</div>
                        </div>
                    @else
                        N/A
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(count($simHistories) === 0)
<div class="empty-state">
    <h4>No SIM history found</h4>
    <p>There are no SIM assignment records to display.</p>
</div>
@endif
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
                fullNotes.style.top = (rect.bottom + 5) + 'px';
                fullNotes.style.left = (rect.left - 100) + 'px';
            }
        });
        
        // Copy notes to clipboard from full notes
        fullNotes.addEventListener('click', function () {
            const notes = this.dataset.notes;
            navigator.clipboard.writeText(notes).then(() => {
                toastr.success('Notes copied to clipboard');
                this.style.display = 'none';
            }).catch(() => {
                // Fallback for older browsers
                const textArea = document.createElement('textarea');
                textArea.value = notes;
                document.body.appendChild(textArea);
                textArea.select();
                document.execCommand('copy');
                document.body.removeChild(textArea);
                toastr.success('Notes copied to clipboard');
                this.style.display = 'none';
            });
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
});
</script>
@endsection