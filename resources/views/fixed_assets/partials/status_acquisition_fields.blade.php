@php
    $statusValue = $statusValue ?? 'draft';
    $showAcquisitionOptions = $showAcquisitionOptions ?? true;
    $acquisitionPostingValue = $acquisitionPostingValue ?? 'already_posted';
    $assetAccountName = $assetAccountName ?? '';
    $assetAccountId = $assetAccountId ?? '';
    $lockStatusField = $lockStatusField ?? false;
    $lockedStatusValue = $lockedStatusValue ?? 'active';
    $lockedStatusLabel = \App\Models\FixedAsset::allStatuses()[$lockedStatusValue] ?? ucfirst($lockedStatusValue);
@endphp

<div class="form-group col-sm-6" id="asset-status-select-wrap" style="{{ $lockStatusField ? 'display:none;' : '' }}">
    {!! Form::label('status', 'Status:') !!}
    @if(!empty($isEdit))
        {!! Form::select('status', \App\Models\FixedAsset::allStatuses(), null, ['class' => 'form-control', 'id' => 'asset_status']) !!}
    @else
        {!! Form::select('status', \App\Models\FixedAsset::initialStatuses(), $statusValue, ['class' => 'form-control', 'id' => 'asset_status']) !!}
    @endif
</div>

<div class="form-group col-sm-6" id="asset-status-locked-wrap" style="{{ $lockStatusField ? '' : 'display:none;' }}">
    {!! Form::label('status_display', 'Status:') !!}
    <input type="text" class="form-control" id="asset_status_display" value="{{ $lockedStatusLabel }}" readonly disabled>
    <input type="hidden" name="status" id="asset_status_locked" value="{{ $lockedStatusValue }}">
</div>

<div class="form-group col-sm-6 pt-3" id="asset-status-help-wrap" style="{{ $lockStatusField ? 'display:none;' : '' }}">
    <small class="text-muted">Draft assets keep depreciation off the books until activated.</small>
</div>

<div class="form-group col-sm-6 pt-3" id="asset-status-opening-help-wrap" style="{{ $lockStatusField ? '' : 'display:none;' }}">
    <small class="text-muted">Opening balance assets are always active. Acquisition entries post automatically.</small>
</div>

@if($showAcquisitionOptions)
<div class="col-12" id="active-acquisition-section" style="{{ $statusValue === 'active' ? '' : 'display:none;' }}">
    <hr>
    <h6 class="text-muted mb-3">Acquisition Posting</h6>
    <div class="row">
        <div class="form-group col-sm-12">
            <label class="d-block mb-2">Has the acquisition already been posted to the asset account?</label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="acquisition_posting" id="acquisition_already_posted" value="already_posted" @checked($acquisitionPostingValue === 'already_posted')>
                <label class="form-check-label" for="acquisition_already_posted">Already posted</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="acquisition_posting" id="acquisition_post_now" value="post_now" @checked($acquisitionPostingValue === 'post_now')>
                <label class="form-check-label" for="acquisition_post_now">Post acquisition now</label>
            </div>
        </div>

        <div class="col-12" id="acquisition-voucher-section" style="{{ $acquisitionPostingValue === 'post_now' ? '' : 'display:none;' }}">
            @include('fixed_assets.partials.acquisition_voucher_fields', [
                'creditAccounts' => $creditAccounts ?? [],
                'assetAccountId' => $assetAccountId,
                'assetAccountName' => $assetAccountName,
            ])
        </div>
    </div>
</div>
@endif
