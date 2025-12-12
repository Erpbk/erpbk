@extends('riders.view')
@section('page_content')
<style>
  /* Totals cards */
  .totals-cards {
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
  }

  .total-card {
    flex: 1 1 0;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-left-width: 4px;
    border-radius: 8px;
    padding: 8px 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
  }

  .total-card .label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #6b7280;
    margin-bottom: 4px;
  }

  .total-card .label i {
    font-size: 11px;
  }

  .total-card .value {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
  }

  .total-card .sub {
    font-size: 11px;
    color: #9ca3af;
    margin-top: 2px;
  }

  .total-delivered {
    border-left-color: #10b981;
    background: linear-gradient(180deg, rgba(16, 185, 129, 0.06), rgba(16, 185, 129, 0.02));
  }

  .total-rejected {
    border-left-color: #ef4444;
    background: linear-gradient(180deg, rgba(239, 68, 68, 0.06), rgba(239, 68, 68, 0.02));
  }

  .total-hours {
    border-left-color: #3b82f6;
    background: linear-gradient(180deg, rgba(59, 130, 246, 0.06), rgba(59, 130, 246, 0.02));
  }

  .total-ontime {
    border-left-color: #8b5cf6;
    background: linear-gradient(180deg, rgba(139, 92, 246, 0.06), rgba(139, 92, 246, 0.02));
  }

  .total-valid-days {
    border-left-color: #f59e0b;
    background: linear-gradient(180deg, rgba(245, 158, 11, 0.06), rgba(245, 158, 11, 0.02));
  }

  /* Table header bold */
  #dataTableBuilder thead th,
  #dataTableBuilder tfoot th {
    font-weight: bold;
  }
</style>
<div class="card card-action mb-0">
  <div class="card-header align-items-center d-flex justify-content-between">
    <h5 class="card-action-title mb-0"><i class="ti ti-calendar-check ti-lg text-body me-2"></i>Activities</h5>
    <div class="d-flex align-items-center gap-2">
      <form action="" method="get" class="mb-0">
        <input type="month" name="month" value="{{request('month')??date('Y-m')}}" class="form-control" onchange="form.submit();" />
      </form>
      <a href="{{ route('riders.activities.pdf', ['id' => $filters['rider_id'], 'month' => request('month') ?? date('Y-m')]) }}"
        class="btn btn-sm btn-primary" style="padding: 1px 12px 1px 0px;" target="_blank">
        <i class="fa fa-file-pdf"></i> Download PDF
      </a>
      <a href="{{ route('riders.activities.print', ['id' => $filters['rider_id'], 'month' => request('month') ?? date('Y-m')]) }}"
        class="btn btn-sm btn-info" style="padding: 1px 12px 1px 0px;" target="_blank">
        <i class="fa fa-print"></i> Print
      </a>
    </div>
  </div>
  <div class="card-body pt-0 px-2">
    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
    @endpush
    <div class="card-body px-0 pt-0">
      <div id="totalsBar" class="mb-2">
        <div class="totals-cards">
          <div class="total-card total-delivered">
            <div class="label"><i class="fa fa-check-circle"></i> Working Days</div>
            <div class="value" id="working_days">{{ $totals['working_days'] ?? 0 }}</div>
          </div>
          <div class="total-card total-rejected">
            <div class="label"><i class="fa fa-times-circle"></i> Valid Days</div>
            <div class="value" id="valid_days">{{ $totals['valid_days'] ?? 0 }}</div>
          </div>
          <div class="total-card total-hours">
            <div class="label"><i class="fa fa-clock"></i> Invalid Days</div>
            <div class="value" id="invalid_days">{{ $totals['invalid_days'] ?? 0 }}</div>
          </div>
          <div class="total-card total-ontime">
            <div class="label"><i class="fa fa-percent"></i> Off Days</div>
            <div class="value" id="off_days">{{ $totals['off_days'] ?? 0 }}</div>
          </div>
          <div class="total-card total-valid-days">
            <div class="label"><i class="fa fa-calendar-check"></i>Total Orders</div>
            <div class="value" id="total_orders">{{ number_format($totals['total_orders'] ?? 0) }}</div>
          </div>
          <div class="total-card total-ontime">
            <div class="label"><i class="fa fa-calendar-check"></i>OnTime%</div>
            <div class="value" id="avg_ontime">{{ number_format($totals['avg_ontime'] ?? 0, 2) }}%</div>
          </div>
          <div class="total-card total-rejected">
            <div class="label"><i class="fa fa-calendar-check"></i>Rejection</div>
            <div class="value" id="total_rejected">{{ number_format($totals['total_rejected'] ?? 0) }}</div>
          </div>
          <div class="total-card total-hours">
            <div class="label"><i class="fa fa-calendar-check"></i>Total Hours</div>
            <div class="value" id="total_hours">{{ number_format($totals['total_hours'] ?? 0, 2) }}</div>
          </div>
        </div>
      </div>
      <table id="dataTableBuilder" class="table table-striped dataTable text-center" width="100%">
        <thead>
          <tr>
            <th>Date</th>
            <th>ID</th>
            <th>Name</th>
            <th>Designation</th>
            <th>Delivered</th>
            <th>Ontime%</th>
            <th>Rejected</th>
            <th>HR</th>
            <th>Valid Day</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data as $r)
          <tr data-delivered="{{ $r->delivered_orders ?? 0 }}"
            data-rejected="{{ $r->rejected_orders ?? 0 }}"
            data-hours="{{ $r->login_hr ?? 0 }}"
            data-ontime="{{ $r->ontime_orders_percentage ?? 0 }}"
            data-valid="{{ $r->delivery_rating == 'Yes' ? 1 : 0 }}"
            data-invalid="{{ $r->delivery_rating == 'No' ? 1 : 0 }}"
            data-off="{{ ($r->delivery_rating != 'Yes' && $r->delivery_rating != 'No') ? 1 : 0 }}">
            <td>{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
            <td>{{ $r->d_rider_id }}</td>
            @php
            $rider = DB::Table('riders')->where('id' , $r->rider_id)->first();
            @endphp
            <td>{{ $rider->name }}</td>
            <td>{{ $rider->designation }}</td>
            <td>{{ $r->delivered_orders }}</td>
            <td>
              @if($r->ontime_orders_percentage){{ $r->ontime_orders_percentage * 100 }}% @else - @endif
            </td>
            <td>{{ $r->rejected_orders }}</td>
            <td>{{ $r->login_hr }}</td>
            <td>
              @if ($r->delivery_rating == 'Yes')
              <span class="badge bg-success">Valid</span>
              @elseif($r->delivery_rating == 'No')
              <span class="badge bg-warning">Invalid</span>
              @else
              <span class="badge bg-danger">Off</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>


    </div>
  </div>
</div>

@push('scripts')
<script>
  $(document).ready(function() {
    // Only recalculate totals when DataTable is filtered/redrawn (if using DataTables)
    // Initial values come from database, so we don't need to calculate on page load
    if ($.fn.DataTable) {
      $('#dataTableBuilder').on('draw.dt', function() {
        calculateTotals();
      });
    }
  });

  function calculateTotals() {
    let workingDays = 0;
    let validDays = 0;
    let invalidDays = 0;
    let offDays = 0;
    let totalOrders = 0;
    let totalRejected = 0;
    let totalHours = 0;
    let totalOntime = 0;
    let ontimeCount = 0;

    $('#dataTableBuilder tbody tr').each(function() {
      const delivered = parseFloat($(this).data('delivered')) || 0;
      const rejected = parseFloat($(this).data('rejected')) || 0;
      const hours = parseFloat($(this).data('hours')) || 0;
      const ontime = parseFloat($(this).data('ontime')) || 0;
      const valid = parseInt($(this).data('valid')) || 0;
      const invalid = parseInt($(this).data('invalid')) || 0;
      const off = parseInt($(this).data('off')) || 0;

      // Count working days (all rows)
      workingDays++;

      // Count day types
      if (valid === 1) {
        validDays++;
      }
      if (invalid === 1) {
        invalidDays++;
      }
      if (off === 1) {
        offDays++;
      }

      // Sum orders and hours
      totalOrders += delivered;
      totalRejected += rejected;
      totalHours += hours;

      // Calculate ontime percentage
      if (ontime > 0) {
        totalOntime += ontime;
        ontimeCount++;
      }
    });

    // Calculate average ontime percentage
    const avgOntime = ontimeCount > 0 ? (totalOntime / ontimeCount) * 100 : 0;

    // Update the totals display
    $('#working_days').text(workingDays);
    $('#valid_days').text(validDays);
    $('#invalid_days').text(invalidDays);
    $('#off_days').text(offDays);
    $('#total_orders').text(totalOrders.toLocaleString());
    $('#avg_ontime').text(avgOntime.toFixed(2) + '%');
    $('#total_rejected').text(totalRejected.toLocaleString());
    $('#total_hours').text(totalHours.toFixed(2));
  }
</script>
@endpush

@endsection