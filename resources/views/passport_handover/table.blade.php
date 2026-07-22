<div class="table-responsive">
    @php $vf = static fn (string $f): bool => field_visible('passport_handover', $f); @endphp
    <table id="historyTable">
        <thead>
            <tr>
                @if($vf('holder_name'))<th>Passport Holder</th>@endif
                @if($vf('passport_number'))<th>Passport No.</th>@endif
                @if($vf('note_date'))<th>Issued</th>@endif
                @if($vf('handed_over_by'))<th>Handed Over By</th>@endif
                @if($vf('received_by'))<th>Received By</th>@endif
                @if($vf('return_date'))<th>Returned</th>@endif
                @if($vf('returned_by'))<th>Returned By</th>@endif
                @if($vf('return_received_by'))<th>Return Received By</th>@endif
                <th>Status</th>
                @if($vf('remarks'))<th>Remarks</th>@endif
                <th>Documents</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $history)
            <tr>
                @if($vf('holder_name'))<td>{{ $history->holder_name ?: $history->personName() }}</td>@endif
                @if($vf('passport_number'))<td>{{ $history->passport_number ?: '-' }}</td>@endif
                @if($vf('note_date'))<td>
                    @can('passport_handover_print')
                    <a href="{{ route('passportHandover.issueContract', $history->id) }}"
                        class="date-display" target="_blank" title="View Issue Acknowledgement">
                        {{ $history->note_date ? $history->note_date->format('d M Y H:i') : '-' }}
                    </a>
                    @else
                    {{ $history->note_date ? $history->note_date->format('d M Y H:i') : '-' }}
                    @endcan
                </td>@endif
                @if($vf('handed_over_by'))<td>{{ $history->handed_over_by ?: '-' }}</td>@endif
                @if($vf('received_by'))<td>{{ $history->received_by ?: '-' }}</td>@endif
                @if($vf('return_date'))<td>
                    @if($history->return_date)
                    @can('passport_handover_print')
                    <a href="{{ route('passportHandover.returnContract', $history->id) }}"
                        class="date-display" target="_blank" title="View Return Acknowledgement">
                        {{ $history->return_date->format('d M Y H:i') }}
                    </a>
                    @else
                    {{ $history->return_date->format('d M Y H:i') }}
                    @endcan
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>@endif
                @if($vf('returned_by'))<td>{{ $history->returned_by ?: '-' }}</td>@endif
                @if($vf('return_received_by'))<td>{{ $history->return_received_by ?: '-' }}</td>@endif
                <td>
                    @if($history->isOpen())
                    <span class="badge bg-warning">Issued</span>
                    @else
                    <span class="badge bg-success">Returned</span>
                    @endif
                </td>
                @if($vf('remarks'))<td>
                    @if(!empty($history->remarks))
                    <span class="text-truncate d-inline-block" style="max-width:120px;" title="{{ $history->remarks }}">
                        {{ \Illuminate\Support\Str::limit($history->remarks, 40) }}
                    </span>
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>@endif
                <td>
                    <div class="d-flex flex-column gap-1">
                        @can('passport_handover_print')
                        <a href="{{ route('passportHandover.issueContract', $history->id) }}"
                            class="btn btn-sm btn-outline-primary" target="_blank">
                            Issue Doc
                        </a>
                        @if($history->return_date)
                        <a href="{{ route('passportHandover.returnContract', $history->id) }}"
                            class="btn btn-sm btn-outline-success" target="_blank">
                            Return Doc
                        </a>
                        @endif
                        @endcan
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" class="text-center text-muted py-4">
                    No passport handover history found for this person.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
