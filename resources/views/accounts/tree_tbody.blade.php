@php
    $includeChildren = $includeChildren ?? false;
    $globalLinkedAccountIds = $globalLinkedAccountIds ?? [];
    $typesInData = $accounts->pluck('account_type')->unique()->filter()->sort()->values();
@endphp
@forelse ($typesInData as $typeValue)
    @php $rootsOfType = $accounts->where('account_type', $typeValue)->values(); @endphp
    @if ($rootsOfType->count() > 0)
    <tr class="table-light type-header-row" data-account-type="{{ $typeValue }}">
        <td class="fw-bold py-2">{{ $typeValue }}</td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td class="coa-cc-pad" aria-hidden="true"></td>
        <td class="coa-cc-pad" aria-hidden="true"></td>
    </tr>
    @foreach ($rootsOfType as $account)
        @include('accounts.account_table_row', [
            'account' => $account,
            'depth' => 0,
            'globalLinkedAccountIds' => $globalLinkedAccountIds,
            'includeChildren' => $includeChildren,
            'forceVisible' => (bool) $includeChildren,
        ])
    @endforeach
    @endif
@empty
    <tr>
        <td colspan="9" class="text-center text-muted py-4">No accounts found.</td>
    </tr>
@endforelse
