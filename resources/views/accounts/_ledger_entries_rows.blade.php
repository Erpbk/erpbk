@forelse ($transactions as $t)
@php
  $voucherNo = '—';
  if ($t->voucher) {
    $voucherNo = ($t->voucher->voucher_type ?? 'V') . '-' . str_pad($t->voucher->id, 4, '0', STR_PAD_LEFT);
  } elseif (!empty($t->trans_code)) {
    $voucherNo = $t->trans_code;
  }
@endphp
<tr>
  <td class="text-nowrap">{{ \App\Helpers\Common::DateFormat($t->trans_date) }}</td>
  <td class="text-nowrap">{{ $voucherNo }}</td>
  <td>{{ $t->narration ?: '--' }}</td>
  <td>{{ $t->reference_type ?? 'Journal' }}</td>
  <td class="text-end">{{ $t->debit > 0 ? number_format($t->debit, 2) : '—' }}</td>
  <td class="text-end">{{ $t->credit > 0 ? number_format($t->credit, 2) : '—' }}</td>
</tr>
@empty
<tr><td colspan="6" class="text-center text-muted py-3">No entries for this page.</td></tr>
@endforelse
