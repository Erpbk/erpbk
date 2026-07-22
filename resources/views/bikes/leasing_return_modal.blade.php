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

$assignFields = $assignFields ?? \App\Models\BikeCustomField::assignModalFields('change');
$notesField = $assignFields->firstWhere('field_key', 'notes');
$notesSpec = $notesField ? $notesField->resolvedInputSpec() : null;
$notesLabel = $notesField ? $notesField->resolvedLabel() : 'Notes';
$notesRequired = (bool) ($notesSpec['required'] ?? false);
$notesInputType = $notesSpec['type'] ?? 'text';
$notesOpts = ($notesField && in_array($notesInputType, ['select', 'dropdown'], true))
? $notesField->resolvedSelectOptions()
: [];
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
                    <label class="form-label" for="leased_return_date">Return date<span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="leased_return_date" name="leased_return_date"
                        value="{{ $leasedReturnDateValue }}" required>
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

                @if($notesField)
                <div class="col-md-6 mb-3">
                    <label class="form-label" for="leasing_return_note">{{ $notesLabel }}@if($notesRequired)<span class="text-danger">*</span>@endif</label>
                    @if(in_array($notesInputType, ['select', 'dropdown'], true))
                    {!! Form::select('note', $notesOpts, old('note'), ['class' => 'form-select select2', 'id' => 'leasing_return_note', 'required' => $notesRequired]) !!}
                    @elseif($notesInputType === 'textarea')
                    <textarea class="form-control" name="note" id="leasing_return_note" rows="3" placeholder="{{ $notesLabel }}" @if($notesRequired) required @endif>{{ old('note') }}</textarea>
                    @elseif($notesInputType === 'date')
                    <input type="date" name="note" id="leasing_return_note" class="form-control" value="{{ old('note') }}" @if($notesRequired) required @endif>
                    @elseif($notesInputType === 'datetime')
                    <input type="datetime-local" name="note" id="leasing_return_note" class="form-control" value="{{ old('note') }}" @if($notesRequired) required @endif>
                    @elseif($notesInputType === 'checkbox')
                    <div class="form-check mt-2">
                        <input type="hidden" name="note" value="0">
                        <input type="checkbox" name="note" value="1" class="form-check-input" id="leasing_return_note" @if(old('note')) checked @endif @if($notesRequired) required @endif>
                    </div>
                    @else
                    <input type="{{ in_array($notesInputType, ['number', 'decimal', 'email', 'url'], true) ? $notesInputType : 'text' }}" name="note" id="leasing_return_note" class="form-control" value="{{ old('note') }}" @if($notesRequired) required @endif placeholder="{{ $notesLabel }}">
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="text-end mt-2">
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#leasing_return_note.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
    });
</script>
