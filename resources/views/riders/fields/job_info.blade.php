<div class="row">
    <div class="form-group col-sm-4">
        {!! Form::label('emirate_id', 'Emirate ID:',['class'=>'required']) !!}
        {!! Form::text('emirate_id', null, ['class' => 'form-control', 'required', 'id' => 'emirate_id', 'placeholder' => '784-2000-6871718-8', 'oninput' => 'formatEmirateId(this)', 'maxlength' => '18']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('emirate_exp', 'Emirate Expiry:',['class'=>'required']) !!}
        {!! Form::date('emirate_exp', null, ['class' => 'form-control','id'=>'emirate_exp','required']) !!}
    </div>
    <div class="form-group col-sm-4">
        {!! Form::label('fleet_supervisor', 'Fleet Supervisor:') !!}
        @php
            $fleetSupervisorOptions = Common::Dropdowns('fleet-supervisor');
            if ($fleetSupervisorOptions instanceof \Illuminate\Support\Collection) {
                $fleetSupervisorOptions = $fleetSupervisorOptions->toArray();
            }
            if (!is_array($fleetSupervisorOptions)) {
                $fleetSupervisorOptions = [];
            }

            $fleetSupervisorAssignment = \App\Models\RiderFieldCategoryAssignment::where('field_key', 'fleet_supervisor')->first();
            $configured = $fleetSupervisorAssignment?->input_config['options'] ?? null;
            $configuredMap = [];
            if ($configured !== null) {
                $configuredItems = is_array($configured) ? $configured : preg_split("/\r\n|\n|\r/", (string) $configured);
                $configuredItems = collect($configuredItems)
                    ->map(fn($v) => trim((string) $v))
                    ->filter(fn($v) => $v !== '')
                    ->unique()
                    ->values()
                    ->all();
                if (!empty($configuredItems)) {
                    $configuredMap = array_combine($configuredItems, $configuredItems);
                }
            }

            $dbValues = \App\Models\Riders::query()
                ->whereNotNull('fleet_supervisor')
                ->where('fleet_supervisor', '!=', '')
                ->distinct()
                ->orderBy('fleet_supervisor')
                ->pluck('fleet_supervisor')
                ->map(fn($v) => trim((string) $v))
                ->filter(fn($v) => $v !== '')
                ->unique()
                ->values()
                ->all();
            $dbMap = !empty($dbValues) ? array_combine($dbValues, $dbValues) : [];

            // Priority: configured options, then existing dropdown options, then distinct DB values.
            $fleetSupervisorOptions = $configuredMap + $fleetSupervisorOptions + $dbMap;
        @endphp
        {!! Form::select('fleet_supervisor', $fleetSupervisorOptions, null, ['class' => 'form-select', 'placeholder' => 'Select Fleet Supervisor', 'required']) !!}
    </div>
    <div class="form-group col-sm-4">
        <label>Recruiter</label>
        <select name="recruiter_id" class="form-select">
            <option value="">Select Recruiter</option>
            @foreach(DB::table('recruiters')->where('status', 1)->get() as $key => $value)
            <option value="{{ $value->id }}" {{ isset($riders) && $riders->recruiter_id == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
            @endforeach
        </select>
    </div>
</div>