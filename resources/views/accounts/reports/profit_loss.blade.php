@extends('layouts.app')
@section('title', 'Profit & Loss Statement')
@php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp

@push('page-styles')
<style>
  .report-filter-card .form-group { margin-bottom: 0; }
  .pl-table th, .pl-table td { vertical-align: middle; }
  .pl-table .section-header td {
    background-color: #f0f2ff;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .03em;
  }
  .pl-table .subtotal-row td {
    background-color: #f8f9fa;
    font-weight: 600;
  }
  .pl-table .net-row td {
    font-weight: 700;
    border-top: 2px solid #000;
  }
  .net-profit, .net-profit td { background-color: #e6f7ec; }
  .net-loss, .net-loss td { background-color: #fdecec; }
  .text-end { text-align: right !important; }

  /* Tree UI (Revenue/Expense accounts) */
  .tree-cell {
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .tree-toggle {
    width: 22px;
    height: 22px;
    border: 1px solid #d0d7de;
    border-radius: 4px;
    background: #fff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    cursor: pointer;
    flex: 0 0 22px;
  }
  .tree-toggle i {
    transition: transform 0.2s ease;
  }
  .tree-row.is-collapsed .tree-toggle i {
    transform: rotate(-90deg);
  }
  .tree-leaf-spacer {
    width: 22px;
    height: 22px;
    display: inline-block;
    flex: 0 0 22px;
  }
  .tree-row .amount-collapsed {
    display: none;
  }
  .tree-row.is-collapsed .amount-expanded {
    display: none;
  }
  .tree-row.is-collapsed .amount-collapsed {
    display: inline;
    font-weight: 600;
  }
  .tree-row.is-hidden-row {
    display: none;
  }

  .report-scroll {
    max-height: calc(100vh - 320px);
    overflow-y: auto;
  }
  .report-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background-color: #fff;
    box-shadow: inset 0 -1px 0 #dee2e6;
  }
  .report-scroll tfoot td {
    position: sticky;
    bottom: 0;
    z-index: 2;
  }

  @media print {
    /* Let all layout ancestors expand so nothing is clipped / scrolled in print */
    html, body,
    .layout-wrapper, .layout-container, .layout-page,
    .content-wrapper, .content, .container-fluid, .container-xxl, .container-p-y,
    .card, .card-body {
      height: auto !important;
      min-height: 0 !important;
      max-height: none !important;
      overflow: visible !important;
    }
    .report-scroll,
    .table-responsive.report-scroll {
      max-height: none !important;
      overflow: visible !important;
    }
    .report-scroll thead th,
    .report-scroll tfoot td { position: static !important; box-shadow: none !important; }
    /* Render footer once at the end of the document instead of repeating per page */
    .pl-table tfoot { display: table-row-group !important; }
    .pl-table tfoot tr { page-break-inside: avoid; }

    .layout-navbar,
    .content-footer,
    footer,
    .module-top-bar-slider,
    section.content-header,
    .report-filter-card,
    .no-print,
    .app-brand,
    .layout-menu,
    .layout-navbar,
    .menu,
    #layout-menu {
      display: none !important;
    }
    .content-wrapper, .content, .card { padding: 0 !important; margin: 0 !important; border: none !important; box-shadow: none !important; }
    .print-title { display: block !important; }
    .pl-table { font-size: 11px; width: 100% !important; }
    .pl-table th, .pl-table td { padding: 4px !important; border: 1px solid #000 !important; }
    .pl-table thead th { background-color: #f0f0f0 !important; }
    @page { margin: 1cm; }
  }
  .print-title { display: none; }
</style>
@endpush

@section('content')
<div class="content-wrapper">
  <section class="content-header">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Profit &amp; Loss Statement</h3>
      <div>
        <a href="{{ route('accounts.reports.profit_loss', ['company_slug' => $__companySlug]) }}" class="btn btn-outline-secondary btn-sm no-print">
          <i class="ti ti-refresh"></i> Reset
        </a>
        <button type="button" class="btn btn-primary btn-sm no-print" onclick="printReport()">
          <i class="ti ti-printer"></i> Print
        </button>
      </div>
    </div>
  </section>

  <div class="content">
    <div class="card report-filter-card mb-3 no-print">
      <div class="card-body">
        <form action="{{ route('accounts.reports.profit_loss', ['company_slug' => $__companySlug]) }}" method="GET" class="row g-3 align-items-end">
          <div class="col-md-3 form-group">
            <label class="form-label" for="from_date">From Date</label>
            <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
          </div>
          <div class="col-md-3 form-group">
            <label class="form-label" for="to_date">To Date</label>
            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
          </div>
          <div class="col-md-3 form-group">
            <label class="form-label" for="month">Or Billing Month</label>
            <input type="month" name="month" id="month" class="form-control" value="{{ request('month') }}">
          </div>
          <div class="col-md-3 form-group">
            <button type="submit" class="btn btn-primary w-100"><i class="ti ti-filter"></i> Generate</button>
          </div>
        </form>
        <small class="text-muted d-block mt-2">A date range takes precedence over billing month. When no filter is set, the current month is used.</small>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">Profit &amp; Loss <span class="text-muted fs-6">for {{ $period['label'] }}</span></h5>
      </div>
      <div class="card-body px-2 py-0">
        <div class="print-title text-center mb-2">
          <h4 class="mb-0">Profit &amp; Loss Statement</h4>
          <div>for {{ $period['label'] }}</div>
        </div>
        <div class="table-responsive report-scroll">
          <table id="reportTable" class="table table-bordered table-sm pl-table mb-0">
            <thead>
              <tr>
                <th style="width:120px;">Account Code</th>
                <th>Account Name</th>
                <th class="text-end" style="width:200px;">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr class="section-header">
                <td colspan="3">Revenue</td>
              </tr>
              @if(empty($revenue))
                <tr><td colspan="3" class="text-center text-muted">No revenue recorded for this period.</td></tr>
              @else
                @include('accounts.reports.partials.profit_loss_rows', ['nodes' => $revenue, 'parentId' => ''])
              @endif
              <tr class="subtotal-row">
                <td colspan="2" class="text-end">Total Revenue</td>
                <td class="text-end">{{ number_format($totalRevenue, 2) }}</td>
              </tr>

              <tr class="section-header">
                <td colspan="3">Expenses</td>
              </tr>
              @if(empty($expense))
                <tr><td colspan="3" class="text-center text-muted">No expenses recorded for this period.</td></tr>
              @else
                @include('accounts.reports.partials.profit_loss_rows', ['nodes' => $expense, 'parentId' => ''])
              @endif
              <tr class="subtotal-row">
                <td colspan="2" class="text-end">Total Expenses</td>
                <td class="text-end">{{ number_format($totalExpense, 2) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="net-row {{ $netProfit >= 0 ? 'net-profit' : 'net-loss' }}">
                <td colspan="2" class="text-end">{{ $netProfit >= 0 ? 'Net Profit' : 'Net Loss' }}</td>
                <td class="text-end">{{ number_format(abs($netProfit), 2) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@include('accounts.reports.partials.ledger_slide_panel')
@endsection

@section('page-script')
<script type="text/javascript">
  function updateTreeVisibility() {
    var rows = Array.from(document.querySelectorAll('#reportTable .tree-row'));
    var rowMap = {};

    rows.forEach(function(row) {
      rowMap[row.dataset.nodeId] = row;
      row.classList.remove('is-hidden-row');
    });

    rows.forEach(function(row) {
      var parentId = row.dataset.parentId;
      while (parentId) {
        var parentRow = rowMap[parentId];
        if (!parentRow) {
          break;
        }
        if (parentRow.classList.contains('is-collapsed')) {
          row.classList.add('is-hidden-row');
          break;
        }
        parentId = parentRow.dataset.parentId;
      }

      var toggle = row.querySelector('.tree-toggle');
      if (toggle) {
        toggle.setAttribute('aria-expanded', row.classList.contains('is-collapsed') ? 'false' : 'true');
      }
    });
  }

  document.addEventListener('click', function(event) {
    var toggle = event.target.closest('.tree-toggle');
    if (!toggle) {
      return;
    }

    var row = toggle.closest('.tree-row');
    if (!row) {
      return;
    }

    row.classList.toggle('is-collapsed');
    updateTreeVisibility();
  });

  document.addEventListener('DOMContentLoaded', function() {
    updateTreeVisibility();
  });

  function printReport() {
    var table = document.getElementById('reportTable');
    if (!table) { return; }

    // Print always shows expanded rows (own amounts), not collapsed subtotals.
    var printTable = table.cloneNode(true);
    printTable.querySelectorAll('.tree-row').forEach(function(row) {
      row.classList.remove('is-collapsed', 'is-hidden-row');
    });
    printTable.querySelectorAll('.tree-toggle').forEach(function(toggle) {
      toggle.remove();
    });
    printTable.querySelectorAll('.view-ledger').forEach(function(link) {
      var span = document.createElement('span');
      span.textContent = link.textContent;
      link.parentNode.replaceChild(span, link);
    });
    printTable.querySelectorAll('.amount-expanded').forEach(function(el) {
      el.style.display = 'inline';
    });
    printTable.querySelectorAll('.amount-collapsed').forEach(function(el) {
      el.remove();
    });

    var title = 'Profit & Loss Statement';
    var subtitle = 'for {{ $period['label'] }}';
    var printedOn = new Date().toLocaleString();

    var css = ''
      + '@page { margin: 1cm; }'
      + 'body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; color: #000; }'
      + '.print-header { text-align: center; margin-bottom: 16px; }'
      + '.print-header h2 { margin: 0 0 4px 0; font-size: 20px; }'
      + '.print-header .sub { font-size: 13px; }'
      + 'table { width: 100%; border-collapse: collapse; }'
      + 'th, td { border: 1px solid #000; padding: 5px 8px; }'
      + 'thead th { background-color: #f0f0f0; font-weight: bold; }'
      + '.text-end { text-align: right; }'
      + '.section-header td { background-color: #eceefb; font-weight: bold; text-transform: uppercase; text-align: center; }'
      + '.subtotal-row td { background-color: #f5f5f5; font-weight: 600; }'
      + '.net-row td { font-weight: bold; border-top: 2px solid #000; }'
      + '.net-profit td { background-color: #e6f7ec; }'
      + '.net-loss td { background-color: #fdecec; }'
      + 'tfoot { display: table-row-group; }'
      + 'tr { page-break-inside: avoid; }'
      + 'thead { display: table-header-group; }'
      + '.print-footer { margin-top: 16px; text-align: center; font-size: 10px; color: #555; }';

    var html = '<!DOCTYPE html><html><head><title>' + title + '</title><style>' + css + '</style></head><body>'
      + '<div class="print-header"><h2>' + title + '</h2><div class="sub">' + subtitle + '</div></div>'
      + printTable.outerHTML
      + '<div class="print-footer">Generated on ' + printedOn + '</div>'
      + '<scr' + 'ipt>window.onload=function(){window.print();window.onafterprint=function(){window.close();};};</scr' + 'ipt>'
      + '</body></html>';

    var w = window.open('', '_blank', 'width=1000,height=800');
    w.document.write(html);
    w.document.close();
  }
</script>
@endsection
