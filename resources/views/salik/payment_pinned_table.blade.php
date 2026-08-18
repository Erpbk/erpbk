@if(isset($pinnedRecords) && $pinnedRecords->isNotEmpty())
<div class="mb-3" id="pinnedSalikWrap">
    <div class="alert alert-info py-2 mb-2">
        {{ $pinnedRecords->count() }} selected record(s) are outside the current page or date range and stay selected.
    </div>
    <div class="table-responsive pinned-salik-scroll">
        <table class="table table-sm table-bordered mb-0" id="pinnedSalikTable">
            <thead>
                <tr>
                    <th style="width: 40px;"></th>
                    <th>Transaction ID</th>
                    <th>Plate</th>
                    <th>Company</th>
                    <th>Rider</th>
                    <th>Trip Date</th>
                    <th>Salik Amount</th>
                    <th>Admin</th>
                    <th>VAT</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pinnedRecords as $record)
                    @include('salik._payment_record_row', ['record' => $record, 'pinned' => true])
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
