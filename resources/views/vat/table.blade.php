@php
  $moduleClass = function ($module) {
    return match ($module) {
      'Salik' => 'salik',
      'RTA Fine' => 'rta',
      'Fuel' => 'fuel',
      'Customer Invoice' => 'customer',
      'Rental Billing' => 'rental',
      'Leasing' => 'leasing',
      'SIM' => 'sim',
      'Supplier / Garage' => 'supplier',
      'Visa Expense' => 'visa',
      'Expense' => 'expense',
      default => '',
    };
  };
@endphp
<table class="table table-hover mb-0" id="vatLedgerTable">
  <thead>
    <tr>
      <th style="width:40px"><input type="checkbox" class="form-check-input" id="vatSelectAll" title="Select all"></th>
      <th>Date</th>
      <th>Account</th>
      <th>Reference</th>
      <th>Month</th>
      <th>Voucher</th>
      <th>Module</th>
      <th>Narration</th>
      <th class="r">Debit</th>
      <th class="r">Credit</th>
      <th class="r">Balance</th>
    </tr>
  </thead>
  <tbody>
    @forelse($rows as $r)
      @if($r['is_balance_forward'] ?? false)
        <tr class="vat-bf">
          <td></td>
          <td colspan="6"></td>
          <td style="text-align:center">Balance Forward</td>
          <td></td>
          <td></td>
          <td class="r vat-bal">{{ $r['balance'] }}</td>
        </tr>
      @elseif($r['is_total'] ?? false)
        {{-- footer handles total --}}
      @else
        @php
          $dateIso = $r['date_iso'] ?? '';
          $dayLabel = $dateIso ? date('d M', strtotime($dateIso)) : $r['date'];
          $yearLabel = $dateIso ? date('Y', strtotime($dateIso)) : '';
        @endphp
        <tr class="vat-row"
            data-id="{{ $r['transaction_id'] }}"
            data-output="{{ !empty($r['is_output']) ? '1' : '0' }}"
            data-debit="{{ $r['debit_raw'] ?? 0 }}"
            data-credit="{{ $r['credit_raw'] ?? 0 }}">
          <td>
            @if(!empty($r['transaction_id']))
              <input type="checkbox"
                     class="form-check-input vat-entry-cb"
                     name="transaction_ids[]"
                     value="{{ $r['transaction_id'] }}"
                     form="vat-file-return-form"
                     data-output="{{ !empty($r['is_output']) ? '1' : '0' }}"
                     data-debit="{{ $r['debit_raw'] ?? 0 }}"
                     data-credit="{{ $r['credit_raw'] ?? 0 }}">
            @endif
          </td>
          <td class="vat-date">
            <b>{{ $dayLabel }}</b>
            @if($yearLabel)<span>{{ $yearLabel }}</span>@endif
          </td>
          <td style="white-space:nowrap">
            @if(!empty($r['is_output']))
              <span class="vat-pill out">Output · Sales <span class="code">{{ $r['account_code'] ?: '' }}</span></span>
            @else
              <span class="vat-pill in">Input · Purchase <span class="code">{{ $r['account_code'] ?: '' }}</span></span>
            @endif
          </td>
          <td class="{{ ($r['reference_number'] ?? '-') === '-' ? 'vat-z' : '' }}" style="font-size:12px">
            {{ $r['reference_number'] ?? '-' }}
          </td>
          <td>
            @if(!empty($r['billing_month']))
              <span class="vat-pill mo">{{ $r['billing_month'] }}</span>
            @endif
          </td>
          <td>{!! $r['voucher'] !!}</td>
          <td>
            @if(!empty($r['module']))
              <span class="vat-src {{ $moduleClass($r['module']) }}">{{ $r['module'] }}</span>
            @endif
          </td>
          <td class="vat-nar" title="{{ strip_tags($r['narration'] ?? '') }}">
            <div class="tx">{!! $r['narration'] !!}</div>
          </td>
          <td class="r {{ ((float)($r['debit_raw'] ?? 0)) > 0 ? 'vat-dr' : 'vat-z' }}">{{ $r['debit'] }}</td>
          <td class="r {{ ((float)($r['credit_raw'] ?? 0)) > 0 ? 'vat-cr' : 'vat-z' }}">{{ $r['credit'] }}</td>
          <td class="r vat-bal">{{ $r['balance'] }}</td>
        </tr>
      @endif
    @empty
      <tr>
        <td colspan="11" class="text-center text-muted py-4">No VAT entries found.</td>
      </tr>
    @endforelse
  </tbody>
  @php
    $totalRow = collect($rows)->firstWhere('is_total', true);
  @endphp
  @if($totalRow)
  <tfoot>
    <tr>
      <td colspan="7"></td>
      <td style="text-align:center">Total</td>
      <td class="r">{{ $totalRow['debit'] }}</td>
      <td class="r">{{ $totalRow['credit'] }}</td>
      <td class="r">{{ $totalRow['balance'] }}</td>
    </tr>
  </tfoot>
  @endif
</table>
