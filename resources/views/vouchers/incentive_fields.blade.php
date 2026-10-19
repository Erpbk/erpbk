@php
$party = $employee ?? $rider ?? null;
$partyLabel = isset($employee) ? 'Employee' : 'Rider';
$counterId = ga_id('INCENTIVE_ACCOUNT');
$counterLabel = $counterId . '-' . ga_name('INCENTIVE_ACCOUNT');
@endphp
@include('vouchers.partials.party_voucher_form', [
    'voucherCode' => 'INC',
    'title' => 'Incentive Voucher',
    'subtitle' => 'Record an incentive entry for this ' . strtolower($partyLabel),
    'partyLabel' => $partyLabel,
    'party' => $party,
    'counterMode' => 'locked',
    'counterAccountId' => $counterId,
    'counterAccountLabel' => $counterLabel,
    'defaultNarrationParty' => 'Incentive Amount Received',
    'defaultNarrationCounter' => 'Incentive Amount Given to ' . ($party->name ?? $partyLabel),
    'showReference' => true,
])
