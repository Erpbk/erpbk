@php
  $entryRows = $entryRows ?? [];
  $debit = (float) ($debit ?? 0);
  $credit = (float) ($credit ?? 0);
@endphp
<div class="vat-ent-scroll">
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Account</th>
        <th>Month</th>
        <th>Voucher</th>
        <th>Module</th>
        <th>Narration</th>
        <th class="r">Debit</th>
        <th class="r">Credit</th>
      </tr>
    </thead>
    <tbody>
      @forelse($entryRows as $er)
        <tr>
          <td>{{ $er['date'] }}</td>
          <td>{{ $er['account'] }}</td>
          <td>{{ $er['month'] }}</td>
          <td class="vat-ent-voucher">{!! $er['voucher'] !!}</td>
          <td>{{ $er['module'] }}</td>
          <td>{{ $er['narration'] }}</td>
          <td class="r">{{ number_format($er['debit'], 2) }}</td>
          <td class="r">{{ number_format($er['credit'], 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="8" class="text-center text-muted">No entries in this return.</td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="6" class="r"><b>Total</b></td>
        <td class="r"><b>{{ number_format($debit, 2) }}</b></td>
        <td class="r"><b>{{ number_format($credit, 2) }}</b></td>
      </tr>
    </tfoot>
  </table>
</div>
