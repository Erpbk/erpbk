<div class="table-responsive">
    <table id="historyTable">
        <thead>
            <tr>
                <th>Passport Holder</th>
                <th>Passport No.</th>
                <th>Issued</th>
                <th>Handed Over By</th>
                <th>Received By</th>
                <th>Returned</th>
                <th>Returned By</th>
                <th>Return Received By</th>
                <th>Status</th>
                <th>Remarks</th>
                <th>Documents</th>
            </tr>
        </thead>
        <tbody>
            @forelse($histories as $history)
            <tr>
                <td>{{ $history->holder_name ?: $history->personName() }}</td>
                <td>{{ $history->passport_number ?: '-' }}</td>
                <td>
                    @can('passport_handover_view')
                    <a href="{{ route('passportHandover.issueContract', $history->id) }}"
                        class="date-display" target="_blank" title="View Issue Acknowledgement">
                        {{ $history->note_date ? $history->note_date->format('d M Y H:i') : '-' }}
                    </a>
                    @else
                    {{ $history->note_date ? $history->note_date->format('d M Y H:i') : '-' }}
                    @endcan
                </td>
                <td>{{ $history->handed_over_by ?: '-' }}</td>
                <td>{{ $history->received_by ?: '-' }}</td>
                <td>
                    @if($history->return_date)
                    @can('passport_handover_view')
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
                </td>
                <td>{{ $history->returned_by ?: '-' }}</td>
                <td>{{ $history->return_received_by ?: '-' }}</td>
                <td>
                    @if($history->isOpen())
                    <span class="badge bg-warning">Issued</span>
                    @else
                    <span class="badge bg-success">Returned</span>
                    @endif
                </td>
                <td>
                    @if(!empty($history->remarks))
                    <span class="text-truncate d-inline-block" style="max-width:120px;" title="{{ $history->remarks }}">
                        {{ \Illuminate\Support\Str::limit($history->remarks, 40) }}
                    </span>
                    @else
                    <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex flex-column gap-1">
                        @can('passport_handover_view')
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
