<div id="past-depreciation-section" class="col-12" style="display: none;">
    <div class="alert alert-warning py-3 mb-3">
        <strong>Past depreciation required</strong>
        <p class="mb-2 small">The in-service date is before {{ \App\Models\FixedAsset::lastMonthStartDate()->format('d M Y') }}. Choose how to handle depreciation for periods before the current month.</p>
        <div class="d-flex flex-column gap-2">
            @foreach(\App\Models\FixedAsset::pastDepreciationHandlingOptions() as $value => $label)
                <div class="form-check">
                    <input class="form-check-input past-depreciation-option" type="radio"
                        name="past_depreciation_handling"
                        id="past_depreciation_{{ $value }}"
                        value="{{ $value }}"
                        @checked(($pastDepreciationHandlingValue ?? '') === $value)>
                    <label class="form-check-label" for="past_depreciation_{{ $value }}">
                        {{ $label }}
                    </label>
                </div>
            @endforeach
        </div>
        <ul class="small text-muted mb-0 mt-2 ps-3">
            <li><strong>Backdated entries</strong> — one schedule line per past period; due entries post when the asset is active.</li>
            <li><strong>Catch-up entry</strong> — one combined entry through {{ \App\Models\FixedAsset::endOfLastMonthDate()->format('d M Y') }}, then normal monthly schedule.</li>
            <li><strong>Current period</strong> — skip past periods; depreciation starts from this month.</li>
        </ul>
    </div>
</div>
