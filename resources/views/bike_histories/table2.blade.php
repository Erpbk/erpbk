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
    color: black;
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

.wa-forward {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background-color: #e4e6eb; /* WhatsApp light gray */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.2s ease, transform 0.15s ease;
}

.wa-forward i {
    color: #6b7280; /* WhatsApp icon gray */
    font-size: 18px;
}

.wa-forward:hover {
    background-color: #d1d5db;
    transform: scale(1.05);
}


</style>
@endpush

@section('page_content')
<div class="table-responsive">
    <table id="dataTableBuilder">
        <thead>
            <tr>
                <th>Bike</th>
                <th>Rider</th>
                <th>Assign</th>
                <th>By</th>
                <th>Return</th>
                <th>By</th>
                <th>Status</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bikeHistory as $r)
            <tr class="text-center">
                <td>
                    <span class="bike-plate">
                        {{ DB::table('bikes')->where('id', $r->bike_id)->first()->plate }}
                    </span>
                </td>
                <td>
                    @if($r->rider_id)
                    <a href="{{ route('riders.show', $r->rider_id) }}" 
                       class="table-link"
                       target="_blank">
                        {{ DB::Table('riders')->where('id', $r->rider_id)->first()->name }}
                    </a>
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @php
                        $contract = DB::table('bike_histories')->find($r->id);
                    @endphp

                    @isset($contract)
                        <div>
                            <a href="{{ route('bikes.assignContract', $contract->id) }}"
                               class="date-display"
                               data-toggle="tooltip"
                               title="View assignment details"
                               target="_blank">
                                {{ $r->note_date ? \Carbon\Carbon::parse($r->note_date)->format('d M Y') : '-' }}
                            </a>

                            <!-- Contract file button -->
                            @if($contract->contract)
                                <div class="mt-1">
                                    <a href="{{ Storage::url('app/contract/'.$contract->contract) }}"
                                       class="contract-btn btn btn-success btn-sm"
                                       data-toggle="tooltip"
                                       title="View contract"
                                       target="_blank">
                                        Contract
                                    </a>
                                </div>
                            @endif
                        </div>
                    @else
                        <span class="text-muted">-</span>
                    @endisset
                </td>
                <td>
                    <span class="user-name">
                        {{ $r->created_by ? \App\Models\User::find($r->created_by)->name : '-' }}
                    </span>
                </td>
                <td>
                    @php
                        $contract = DB::table('bike_histories')->find($r->id);
                    @endphp

                    @isset($contract)
                        <div>
                            <a href="{{ route('bikes.returnContract', $contract->id) }}"
                               class="date-display"
                               data-toggle="tooltip"
                               title="View assignment details"
                               target="_blank">
                                {{ $r->return_date ? \Carbon\Carbon::parse($r->return_date)->format('d M Y') : '-' }}
                            </a>
                        </div>
                    @else
                        <span class="text-muted">-</span>
                    @endisset
                </td>
                <td>
                    <span class="user-name">
                        {{ $r->updated_by ? \App\Models\User::find($r->updated_by)->name : '-' }}
                    </span>
                </td>
                <td class="text-center">
                    @if(strtolower(trim($r->warehouse)) === 'active')
                    <span class="badge bg-success">{{ 'On Road' ?? '-' }}</span>
                    @elseif(strtolower(trim($r->warehouse)) === 'absconded')
                    <span class="badge bg-danger">{{ 'Absconded' ?? '-' }}</span>
                    @elseif(strtolower(trim($r->warehouse)) === 'return')
                    <span class="badge bg-warning">{{ 'Off Road' ?? '-' }}</span>
                    @elseif(strtolower(trim($r->warehouse)) === 'vacation')
                    <span class="badge bg-warning">{{ 'Off Road' ?? '-' }}</span>
                    @endif
                </td>
                <td>
                    @if(!empty($r->notes))
                        <span class="notes-cell wa-forward"
                              data-notes="{{ e($r->notes) }}">
                            <i class="fab fa-whatsapp"></i>
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if(count($bikeHistory) === 0)
<div class="empty-state">
    <h4>No history found</h4>
    <p>There are no bike assignment records to display.</p>
</div>
@endif
@endsection

@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.notes-cell').forEach(function (cell) {
        const notes = cell.dataset.notes;
        if (!notes) return;

        cell.style.cursor = 'pointer';
        cell.title = 'Click to copy';

        cell.addEventListener('click', function () {
            navigator.clipboard.writeText(notes).then(() => {
                toastr.success('Notes copied to clipboard');
            }).catch(() => {
                toastr.error('Failed to copy');
            });
        });
    });
});
</script>

@endsection