@php
$party = $rider ?? null;
$counterId = ga_id('VENDOR_CHARGES_ACCOUNT');
$counterLabel = $counterId . '-' . ga_name('VENDOR_CHARGES_ACCOUNT');
@endphp
@include('vouchers.partials.party_voucher_form', [
    'voucherCode' => 'VC',
    'title' => 'Vendor Charges Voucher',
    'subtitle' => 'Record vendor charges for this rider',
    'partyLabel' => 'Rider',
    'party' => $party,
    'counterMode' => 'locked',
    'counterAccountId' => $counterId,
    'counterAccountLabel' => $counterLabel,
    'defaultNarrationParty' => 'Vendor Charges Applied',
    'defaultNarrationCounter' => 'Vendor Charges from ' . ($party->name ?? 'Rider'),
    'showReference' => true,
])
