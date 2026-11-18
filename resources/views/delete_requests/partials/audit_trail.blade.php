@php
    $req = $deleteRequest;
    $statusLabel = ucfirst((string) $req->status);
    if ($req->wasPermanentlyDeleted()) {
        $finalStatus = 'Purged';
    } elseif ($req->isInRecycleBin()) {
        $finalStatus = 'In Recycle Bin';
    } elseif ($req->wasRestoredFromBin()) {
        $finalStatus = 'Restored';
    } elseif ($req->bin_outcome === 'payment_reversed') {
        $finalStatus = 'Payment reversed';
    } else {
        $finalStatus = $statusLabel;
    }
@endphp

<div class="sec">Delete Request #{{ $req->id }}</div>
<div class="doc" style="padding:20px 26px">
    <div class="meta2">
        <div><b>Reason</b>{{ $req->reason ?: '—' }}</div>
        <div><b>Admin Remarks</b>{{ $req->admin_remarks ?: '—' }}</div>
    </div>
    <div class="xt" style="margin-top:0">
        <h6>Audit Trail</h6>
        <table>
            <thead>
                <tr>
                    <th style="width:42%">Event</th>
                    <th>By</th>
                    <th>Date</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><span class="ev">Deletion requested</span></td>
                    <td>{{ $req->requester->name ?? '—' }}</td>
                    <td>{{ $req->created_at?->format('d-m-Y') ?? '—' }}</td>
                    <td>{{ $req->created_at?->format('H:i:s') ?? '—' }}</td>
                </tr>

                @if($req->isPending())
                    <tr>
                        <td>
                            <span class="ev warn">Waiting for approval</span>
                            <small>An administrator needs to approve or reject this request.</small>
                        </td>
                        <td>—</td>
                        <td>—</td>
                        <td>—</td>
                    </tr>
                @endif

                @if($req->isRejected())
                    <tr>
                        <td>
                            <span class="ev bad">Request rejected</span>
                            <small>The record stays in its original module.</small>
                        </td>
                        <td>{{ $req->reviewer->name ?? '—' }}</td>
                        <td>{{ $req->reviewed_at?->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ $req->reviewed_at?->format('H:i:s') ?? '—' }}</td>
                    </tr>
                @endif

                @if($req->isApproved())
                    <tr>
                        <td>
                            <span class="ev ok">Approved — moved to Recycle Bin</span>
                            <small>Record left the original module and is recoverable from the Recycle Bin until permanently deleted.</small>
                        </td>
                        <td>{{ $req->reviewer->name ?? '—' }}</td>
                        <td>{{ ($req->moved_to_bin_at ?? $req->reviewed_at)?->format('d-m-Y') ?? '—' }}</td>
                        <td>{{ ($req->moved_to_bin_at ?? $req->reviewed_at)?->format('H:i:s') ?? '—' }}</td>
                    </tr>

                    @if($req->wasRestoredFromBin())
                        <tr>
                            <td><span class="ev ok">Restored to original module</span></td>
                            <td>{{ $req->restoredByUser->name ?? '—' }}</td>
                            <td>{{ $req->restored_at?->format('d-m-Y') ?? '—' }}</td>
                            <td>{{ $req->restored_at?->format('H:i:s') ?? '—' }}</td>
                        </tr>
                    @elseif($req->wasPermanentlyDeleted())
                        <tr>
                            <td><span class="ev bad">Permanently deleted from Recycle Bin</span></td>
                            <td>{{ $req->permanentlyDeletedByUser->name ?? '—' }}</td>
                            <td>{{ $req->permanently_deleted_at?->format('d-m-Y') ?? '—' }}</td>
                            <td>{{ $req->permanently_deleted_at?->format('H:i:s') ?? '—' }}</td>
                        </tr>
                    @elseif($req->isInRecycleBin())
                        <tr>
                            <td>
                                <span class="ev warn">Currently in Recycle Bin</span>
                                <small>Waiting for restore or permanent deletion by an authorized administrator.</small>
                            </td>
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>
                        </tr>
                    @endif
                @endif
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="r">Final Status</td>
                    <td>{{ $finalStatus }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
