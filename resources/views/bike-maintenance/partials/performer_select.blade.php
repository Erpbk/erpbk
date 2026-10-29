@php
    $garages = $garages ?? collect();
    $leasingCompanies = $leasingCompanies ?? collect();
    $selectedPerformer = old('performer');
    if ($selectedPerformer === null && isset($maintenance)) {
        if ($maintenance->leasing_company_id) {
            $selectedPerformer = 'leasing:'.$maintenance->leasing_company_id;
        } elseif ($maintenance->garage_id) {
            $selectedPerformer = 'garage:'.$maintenance->garage_id;
        }
    }
@endphp
<div class="form-group {{ $wrapperClass ?? 'col-md-3' }}">
    {!! Form::label('performer', $label ?? 'Performed by:') !!}
    <select name="performer" id="bm_performer_select" class="form-control select2" required>
        <option value="">Select</option>
        @if($garages->isNotEmpty())
            <optgroup label="Garages">
                @foreach ($garages as $garage)
                    <option
                        value="garage:{{ $garage->id }}"
                        data-garage-type="{{ $garage->garage_type }}"
                        @if($selectedPerformer === 'garage:'.$garage->id) selected @endif
                    >{{ $garage->name }}</option>
                @endforeach
            </optgroup>
        @endif
        @if($leasingCompanies->isNotEmpty())
            <optgroup label="Leasing Companies">
                @foreach ($leasingCompanies as $leasingCompany)
                    <option
                        value="leasing:{{ $leasingCompany->id }}"
                        data-garage-type="external"
                        @if($selectedPerformer === 'leasing:'.$leasingCompany->id) selected @endif
                    >{{ $leasingCompany->name }}</option>
                @endforeach
            </optgroup>
        @endif
    </select>
</div>
