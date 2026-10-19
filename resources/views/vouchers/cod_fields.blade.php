@php
$party = $rider ?? null;
$codParentId = ga_id('COD_PARENT');

// Prefer child accounts under COD_PARENT; if none exist (parent is a leaf),
// fall back to the parent itself so Counter Account is never empty.
$codAccountRows = \App\Models\Accounts::withoutGlobalScopes(['company', 'branch'])
    ->where('parent_id', $codParentId)
    ->orderBy('account_code')
    ->orderBy('name')
    ->get(['id', 'name', 'account_code']);

if ($codAccountRows->isEmpty()) {
    $parentRow = \App\Models\Accounts::withoutGlobalScopes(['company', 'branch'])
        ->find($codParentId, ['id', 'name', 'account_code']);
    if ($parentRow) {
        $codAccountRows = collect([$parentRow]);
    }
}

$codAccounts = $codAccountRows
    ->mapWithKeys(function ($row) {
        $label = trim(($row->account_code ? $row->account_code . '-' : '') . ($row->name ?? ''));

        return [(int) $row->id => $label !== '' ? $label : (string) $row->id];
    })
    ->toArray();

$useLockedCounter = count($codAccounts) === 1;
$lockedCounterId = $useLockedCounter ? (int) array_key_first($codAccounts) : null;
$lockedCounterLabel = $useLockedCounter ? ($codAccounts[$lockedCounterId] ?? '') : '';
@endphp
<input type="hidden" name="payment_from" value="{{ $codParentId }}" />
@include('vouchers.partials.party_voucher_form', [
    'voucherCode' => 'COD',
    'title' => 'COD Voucher',
    'subtitle' => 'Record a COD entry for this rider',
    'partyLabel' => 'Rider',
    'party' => $party,
    'counterMode' => $useLockedCounter ? 'locked' : 'select',
    'counterAccounts' => $codAccounts,
    'counterAccountId' => $lockedCounterId,
    'counterAccountLabel' => $lockedCounterLabel,
    'defaultNarrationParty' => 'COD Amount Received',
    'defaultNarrationCounter' => 'COD Amount Given to ' . ($party->name ?? 'Rider'),
    'showReference' => true,
])
