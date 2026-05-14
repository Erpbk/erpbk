@php
$bikeForLease = $bike;
$bikeForLease->loadMissing(['leasedReturnCompany', 'LeasingCompany']);

$leasedReturnDateValue = old(
'leased_return_date',
$bikeForLease->leased_return_date
? \Carbon\Carbon::parse($bikeForLease->leased_return_date)->format('Y-m-d')
: ''
);

$leasedReturnCompanyId = '';
$leasedCompanyDisplayName = '';
if (\Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_company_id')) {
$leasedReturnCompanyId = (string) old(
'leased_return_company_id',
$bikeForLease->leased_return_company_id ?? $bikeForLease->company ?? ''
);
if ($leasedReturnCompanyId !== '') {
$lc = \App\Models\LeasingCompanies::find($leasedReturnCompanyId);
$leasedCompanyDisplayName = $lc ? (string) $lc->name : '';
}
}
@endphp
<form action="{{ route('bikes.leasingReturn', $bike->id) }}" method="post" id="formajax">
    @csrf
    <div class="card border-0 shadow-none mb-0">
        <div class="card-header bg-transparent px-0 pt-0">
            <b>Return to leasing company</b>
        </div>
        <div class="card-body px-0 pb-0">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="leased_return_date">Return date</label>
                    <input type="date" class="form-control" id="leased_return_date" name="leased_return_date"
                        value="{{ $leasedReturnDateValue }}">
                    <small class="text-muted">Date the vehicle was actually returned.</small>
                </div>
                @if(\Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_company_id'))
                <div class="col-md-6 mb-3">
                    <input type="hidden" name="leased_return_company_id" value="{{ $leasedReturnCompanyId }}">
                    <label class="form-label" for="leased_return_company_display">Return to leasing company</label>
                    <input type="text" class="form-control" id="leased_return_company_display"
                        value="{{ $leasedCompanyDisplayName }}" readonly autocomplete="off">
                    <small class="text-muted">Leasing company this vehicle will be returned to.</small>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="text-end mt-2">
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>