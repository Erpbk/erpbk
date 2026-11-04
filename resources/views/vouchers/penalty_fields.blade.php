@php
$party = $employee ?? $rider ?? null;
$partyLabel = isset($employee) ? 'Employee' : 'Rider';
$counterId = ga_id('PENALTY_REVENUE');
$counterLabel = $counterId . '-' . ga_name('PENALTY_REVENUE');
@endphp
@include('vouchers.partials.party_voucher_form', [
    'voucherCode' => 'PN',
    'title' => 'Penalty Voucher',
    'subtitle' => 'Record a penalty entry for this ' . strtolower($partyLabel),
    'partyLabel' => $partyLabel,
    'party' => $party,
    'counterMode' => 'locked',
    'counterAccountId' => $counterId,
    'counterAccountLabel' => $counterLabel,
    'defaultNarrationParty' => 'Penalty Amount Received',
    'defaultNarrationCounter' => 'Penalty Amount Given to ' . ($party->name ?? $partyLabel),
    'showReference' => true,
])
