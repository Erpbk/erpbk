@php
  $entryRows = $entryRows ?? [];
  $debit = (float) ($debit ?? 0);
  $credit = (float) ($credit ?? 0);
  $vatReturn = $vatReturn ?? null;
  $returnStatement = $returnStatement ?? null;
  $returnNo = $vatReturn?->invoice_number ?? 'VAT Return';
  $period = $vatReturn
    ? trim(($vatReturn->quarter_label ?? ('Q' . $vatReturn->quarter_slot)) . ' ' . $vatReturn->year)
    : '';
  $stmtId = 'vatReturnStatementJson-' . ($vatReturn->id ?? uniqid());
@endphp
<div class="al-card">
  <div class="al-head">
    <div class="al-left">
      <div class="al-icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg>
      </div>
      <div>
        <div class="al-title">Account Ledger</div>
        <div class="al-sub"><b>{{ $returnNo }}</b> · VAT Return {{ $period }}</div>
      </div>
    </div>
    <div class="al-btns">
      <button type="button" class="al-btn" data-vat-stmt data-vat-stmt-target="{{ $stmtId }}" title="Print statement">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Print
      </button>
      <button type="button" class="al-btn" data-vat-stmt data-vat-stmt-target="{{ $stmtId }}" data-vat-stmt-print="1" title="Download PDF">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M12 18v-6M9 15l3 3 3-3"/></svg>
        PDF
      </button>
    </div>
  </div>
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
            <td class="vat-nw">{{ $er['date'] }}</td>
            <td class="vat-nw">{{ $er['account'] }}</td>
            <td class="vat-nw">{{ $er['month'] }}</td>
            <td class="vat-ent-voucher vat-nw">{!! $er['voucher'] !!}</td>
            <td class="vat-nw">{{ $er['module'] }}</td>
            <td class="vat-nar" title="{{ strip_tags($er['narration'] ?? '') }}">
              <div class="tx">{!! $er['narration'] ?? '' !!}</div>
            </td>
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
</div>
<script type="application/json" id="{{ $stmtId }}">@json($returnStatement)</script>
