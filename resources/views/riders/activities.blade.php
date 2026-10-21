@extends('riders.view')
@section('page_content')
<style>
  .rider-activities-page .card-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: wrap;
    gap: 0.5rem;
    background: #e3e4f9 !important;
    border-bottom: 1px solid #d8daf0 !important;
    padding: 0.55rem 0.85rem !important;
  }

  .rider-activities-page .card-action-title {
    margin: 0 !important;
    font-size: 0.72rem !important;
    font-weight: 700 !important;
    letter-spacing: 0.45px;
    text-transform: uppercase;
    color: var(--company-brand-primary, #4338ca) !important;
  }

  .rider-activities-page .card-action-title i {
    font-size: 0.95rem !important;
    color: inherit !important;
  }

  .rider-activities-toolbar {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.35rem;
  }

  .rider-activities-toolbar .form-control {
    min-height: 1.7rem;
    padding: 0.15rem 0.45rem;
    font-size: 0.72rem;
    width: auto;
  }

  .rider-activities-toolbar .btn {
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.2rem 0.5rem !important;
    font-size: 0.68rem !important;
    font-weight: 600 !important;
    line-height: 1.2 !important;
    min-height: 1.7rem;
    border-radius: 0.35rem !important;
  }

  .totals-cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.45rem;
  }

  .total-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-left-width: 3px;
    border-radius: 0.5rem;
    padding: 0.45rem 0.55rem;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    min-width: 0;
  }

  .total-card .label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 0.62rem;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #6b7280;
    margin-bottom: 0.15rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .total-card .label i {
    font-size: 0.7rem;
  }

  .total-card .value {
    font-size: 0.95rem;
    font-weight: 700;
    color: #111827;
    line-height: 1.2;
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

  .rider-activities-page #dataTableBuilder {
    margin-bottom: 0;
  }

  .rider-activities-page #dataTableBuilder thead th,
  .rider-activities-page #dataTableBuilder tfoot th {
    font-weight: 700;
    font-size: 0.72rem;
    white-space: nowrap;
    padding: 0.45rem 0.35rem;
    vertical-align: middle;
  }

  .rider-activities-page #dataTableBuilder tbody td {
    padding: 0.4rem 0.35rem;
    vertical-align: middle;
    font-size: 0.78rem;
  }

  .rider-activities-page #dataTableBuilder tbody tr + tr td {
    border-top: 1px solid #eef0f3;
  }

  .rider-activities-page .badge {
    font-size: 0.65rem;
    padding: 0.2rem 0.4rem;
  }

  .rider-activities-page .status-cell {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: 0.1rem;
    line-height: 1.15;
  }

  .rider-activities-page .status-cell small {
    font-size: 0.62rem;
  }

  @media (max-width: 1199.98px) {
    .totals-cards {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }
  }

  @media (max-width: 767.98px) {
    .totals-cards {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
</style>
<div class="card card-action mb-0 rider-activities-page">
  <div class="card-header">
    <h5 class="card-action-title mb-0"><i class="ti ti-calendar-check me-1"></i>Activities</h5>
    <div class="rider-activities-toolbar">
      <form action="" method="get" class="mb-0">
        <input type="month" name="month" value="{{ request('month') ?? date('Y-m') }}" class="form-control" onchange="this.form.submit();" />
      </form>
      <a href="{{ route('riders.activities.pdf', ['id' => $filters['rider_id'], 'month' => request('month') ?? date('Y-m')]) }}"
        class="btn btn-sm btn-primary" target="_blank">
        <i class="fa fa-file-pdf"></i> PDF
      </a>
      <a href="{{ route('riders.activities.print', ['id' => $filters['rider_id'], 'month' => request('month') ?? date('Y-m')]) }}"
        class="btn btn-sm btn-info" target="_blank">
        <i class="fa fa-print"></i> Print
      </a>
    </div>
  </div>
  <div class="card-body pt-2 px-2">
    @push('third_party_stylesheets')
    @include('layouts.datatables_css')
    @endpush
    <div class="px-1 pt-0">
      <div id="totalsBar" class="mb-2">
        <div class="totals-cards">
          <div class="total-card total-delivered">
            <div class="label"><i class="fa fa-check-circle"></i> Working Days</div>
            <div class="value" id="working_days">{{ (int) ($totals['working_days'] ?? 0) }}</div>
          </div>
          <div class="total-card total-valid-days">
            <div class="label"><i class="fa fa-calendar-check"></i> Valid Days</div>
            <div class="value" id="valid_days">{{ (int) ($totals['valid_days'] ?? 0) }}</div>
          </div>
          <div class="total-card total-rejected">
            <div class="label"><i class="fa fa-times-circle"></i> Invalid Days</div>
            <div class="value" id="invalid_days">{{ (int) ($totals['invalid_days'] ?? 0) }}</div>
          </div>
          <div class="total-card total-ontime">
            <div class="label"><i class="fa fa-moon"></i> Off Days</div>
            <div class="value" id="off_days">{{ (int) ($totals['off_days'] ?? 0) }}</div>
          </div>
          <div class="total-card total-valid-days">
            <div class="label"><i class="fa fa-box"></i> Total Orders</div>
            <div class="value" id="total_orders">{{ number_format((float) ($totals['total_orders'] ?? 0)) }}</div>
          </div>
          <div class="total-card total-ontime">
            <div class="label"><i class="fa fa-percent"></i> OnTime%</div>
            <div class="value" id="avg_ontime">{{ number_format((float) ($totals['avg_ontime'] ?? 0), 2) }}%</div>
          </div>
          <div class="total-card total-rejected">
            <div class="label"><i class="fa fa-ban"></i> Rejection</div>
            <div class="value" id="total_rejected">{{ number_format((float) ($totals['total_rejected'] ?? 0)) }}</div>
          </div>
          <div class="total-card total-hours">
            <div class="label"><i class="fa fa-clock"></i> Total Hours</div>
            <div class="value" id="total_hours">{{ number_format((float) ($totals['total_hours'] ?? 0), 2) }}</div>
          </div>
        </div>
      </div>
      <div class="table-responsive">
      <table id="dataTableBuilder" class="table table-sm text-center mb-0" width="100%">
        <thead class="text-center">
          <tr role="row">
            <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Date: activate to sort column ascending">Date</th>
            <th title="Day" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Day: activate to sort column ascending">Day</th>
            <th title="ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="ID: activate to sort column ascending">ID</th>
            <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending">Name</th>
            <th title="Fleet Supr" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Fleet Supr">Fleet Supr</th>
            <th title="Project" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Project">Project</th>
            <th title="Status" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
            <th title="Delivered" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Delivered: activate to sort column ascending">Delivered</th>
            <th title="HR" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="HR: activate to sort column ascending">HR</th>
            <th title="Ontime%" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ontime%: activate to sort column ascending">Ontime%</th>
            <th title="Rejected" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rejected: activate to sort column ascending">Rejected</th>
            <th title="Rating" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rating: activate to sort column ascending">Valid Day</th>
          </tr>
        </thead>
        <tbody>
          @foreach($data as $r)
          @php
            $rowOrders = (float) ($r->delivered_orders ?? 0);
            $rowHours = (float) ($r->login_hr ?? 0);
            if ($rowHours == 0.0) {
              $rowDayType = 'off';
            } elseif (($rowOrders >= 5 && $rowHours >= 10) || ($rowOrders >= 10)) {
              $rowDayType = 'valid';
            } else {
              $rowDayType = 'invalid';
            }
          @endphp
          <tr class="text-center"
            data-delivered="{{ $r->delivered_orders ?? 0 }}"
            data-rejected="{{ $r->rejected_orders ?? 0 }}"
            data-hours="{{ $r->login_hr ?? 0 }}"
            data-ontime="{{ $r->ontime_orders_percentage ?? 0 }}"
            data-valid="{{ $rowDayType === 'valid' ? 1 : 0 }}"
            data-invalid="{{ $rowDayType === 'invalid' ? 1 : 0 }}"
            data-off="{{ $rowDayType === 'off' ? 1 : 0 }}">
            <td>{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($r->date)->format('l') }}</td>
            <td>{{ $r->d_rider_id }}</td>
            @php
            $rider = company_table('riders')->where('id' , $r->rider_id)->first();
            @endphp
            <td> <a href="{{route('rider.activities',$r->rider_id)}}">{{ $rider->name }}</a> </td>
            <td>{{ $rider->fleet_supervisor }}</td>
            <td>{{ company_table('customers')->where('id', $rider->customer_id)->first()->name ?? '-' }}</td>
            @php
            $hasActiveBike = company_table('bikes')->where('rider_id', $rider->id)->where('warehouse', 'Active')->exists();
            $isWalker = $rider->designation === 'Walker';
            $employmentStatus = \App\Models\Riders::employmentStatusDisplay($rider->status ?? null);
            $isEmploymentInactive = (int) ($rider->status ?? 1) !== 1;

            if ($isEmploymentInactive) {
            $statusText = $employmentStatus['label'];
            $badgeClass = $employmentStatus['badge'];
            } elseif ($isWalker) {
            $statusText = 'Active';
            $badgeClass = 'bg-label-success';
            } else {
            $statusText = $hasActiveBike ? 'Active' : 'Inactive';
            $badgeClass = $hasActiveBike ? 'bg-label-success' : 'bg-label-danger';
            }
            $statusDaysInfo = \App\Models\Riders::resolveEmploymentStatusDays($rider);
            $statusDaysTitle = !empty($statusDaysInfo['changed_at'])
            ? 'Status changed on ' . \Carbon\Carbon::parse($statusDaysInfo['changed_at'])->format('d M Y')
            : 'Days in current status';
            @endphp
            <td class="text-center">
              <div class="status-cell">
                <span class="badge {{ $badgeClass }}">{{ $statusText }}</span>
                @if($statusDaysInfo['days'] !== null)
                <small class="text-muted" title="{{ $statusDaysTitle }}">{{ (int) $statusDaysInfo['days'] }} {{ (int) $statusDaysInfo['days'] === 1 ? 'day' : 'days' }}</small>
                @endif
              </div>
            </td>
            <td>{{ $r->delivered_orders }}</td>
            <td>{{ $r->login_hr }}</td>
            <td>@if($r->ontime_orders_percentage){{ number_format($r->ontime_orders_percentage * 100, 2) }}% @else - @endif</td>
            <td>{{ $r->rejected_orders }}</td>
            <td>
              @php
              $orders = $r->delivered_orders ?? 0;
              $hours = $r->login_hr ?? 0;

              // Determine status based on new logic
              if ($hours == 0) {
              $status = 'Off';
              $badgeClass = 'bg-danger';
              } elseif (($orders >= 5 && $hours >= 10) || ($orders >= 10)) {
              $status = 'Valid';
              $badgeClass = 'bg-success';
              } else {
              $status = 'Invalid';
              $badgeClass = 'bg-warning';
              }
              @endphp
              <span class="badge {{ $badgeClass }}">{{ $status }}</span>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
      </div>
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