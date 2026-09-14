@php
$currSymbol = \App\Helpers\Currency::symbol();
$totalOutstanding = $installments->sum(fn ($i) => max(0, (float)$i->amount - (float)$i->paid_amount));
@endphp

<style>
  /* ── Payment Receiving Modal Styles ─────────────────────────── */
  .prm-header {
    background: linear-gradient(135deg, #1565c0 0%, #42a5f5 100%);
    padding: 1.1rem 1.5rem;
    border-radius: .5rem .5rem 0 0;
  }

  .prm-person-card {
    background: #f0f7ff;
    border: 1px solid #bee3f8;
    border-radius: .5rem;
    padding: .75rem 1rem;
  }

  .prm-table th {
    font-size: .75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6c757d;
    font-weight: 600;
  }

  .prm-table td {
    vertical-align: middle;
    font-size: .85rem;
  }

  .prm-table .prm-check-col {
    width: 36px;
    text-align: center;
  }

  .prm-amount-input {
    width: 110px;
    text-align: right;
    border: 1px solid #ced4da;
    border-radius: .35rem;
    padding: .3rem .5rem;
    font-size: .85rem;
  }

  .prm-amount-input:disabled {
    background: #f8f9fa;
    color: #adb5bd;
  }

  .prm-summary-bar {
    background: #f8f9fb;
    border-top: 1px solid #dee2e6;
    padding: .6rem 1.25rem;
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    align-items: center;
  }

  .prm-stat {
    display: flex;
    flex-direction: column;
  }

  .prm-stat .ps-label {
    font-size: .63rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #9aa0ac;
  }

  .prm-stat .ps-value {
    font-size: .95rem;
    font-weight: 800;
    color: #1a1e2d;
  }

  .prm-row-selected {
    background: #f0fff4 !important;
  }

  .badge-partial {
    background: #ffc107;
    color: #212529;
  }
</style>

<div class="modal-header prm-header p-0 border-0">
  <div class="p-0 w-100">
    <div class="d-flex align-items-center justify-content-between px-4 pt-3 pb-2">
      <div>
        <h5 class="text-white mb-0 fw-bold">
          <i class="fa fa-money-bill-wave me-2"></i>Payment Receiving
        </h5>
        <small class="text-white-50">{{ $voucherLabel }}</small>
      </div>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>

    {{-- Person info bar --}}
    <div class="px-4 pb-3">
      <div class="prm-person-card d-flex flex-wrap gap-3 align-items-center">
        <div>
          <div class="text-xs text-muted fw-semibold" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">Rider / Employee</div>
          <div class="fw-bold text-dark">{{ $person->name ?? '—' }}</div>
        </div>
        <div>
          <div class="text-xs text-muted fw-semibold" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">ID</div>
          <div class="fw-semibold">{{ $person->rider_id ?? $person->employee_id ?? '—' }}</div>
        </div>
        <div>
          <div class="text-xs text-muted fw-semibold" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.05em;">Total Outstanding</div>
          <div class="fw-bold text-danger">{{ $currSymbol }} {{ number_format($totalOutstanding, 2) }}</div>
        </div>
        <div class="ms-auto">
          <span class="badge bg-primary">{{ $installments->count() }} pending installment{{ $installments->count() !== 1 ? 's' : '' }}</span>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal-body p-0">
  @if($installments->isEmpty())
  <div class="text-center py-5 text-muted">
    <i class="fa fa-check-circle fa-2x mb-2 text-success d-block"></i>
    All installments are fully paid — no outstanding balance.
  </div>
  @else
  <form id="prmForm" method="POST" action="{{ route($processRoute) }}">
    @csrf
    {{-- Use raw riderId so processPaymentReceiving can do the same 3-tier lookup --}}
    <input type="hidden" name="rider_id" value="{{ $riderId }}">

    {{-- Payment details --}}
    <div class="px-4 pt-3 pb-2">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold small">Bank / Cash Account <span class="text-danger">*</span></label>
          <select name="bank_account_id" id="prm_bank_account" class="form-select form-select-sm" required>
            @foreach($bankCashAccounts as $id => $label)
            <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Payment Date <span class="text-danger">*</span></label>
          <input type="date" name="payment_date" id="prm_payment_date"
            class="form-control form-control-sm"
            value="{{ now()->format('Y-m-d') }}" required>
        </div>
        <div class="col-md-3">
          <label class="form-label fw-semibold small">Billing Month <span class="text-danger">*</span></label>
          <input type="month" name="billing_month" id="prm_billing_month"
            class="form-control form-control-sm"
            value="{{ now()->format('Y-m') }}" required>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <button type="button" class="btn btn-sm btn-outline-primary w-100" id="prm_select_all">
            <i class="fa fa-check-double me-1"></i>Select All
          </button>
        </div>
      </div>
      <div class="row g-3 mt-1">
        <div class="col-12">
          <label class="form-label fw-semibold small">Narration / Remarks</label>
          <input type="text" name="narration" class="form-control form-control-sm"
            placeholder="Optional payment note…" maxlength="1000">
        </div>
      </div>
    </div>

    {{-- Installments table --}}
    <div class="table-responsive px-1">
      <table class="table prm-table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th class="prm-check-col">
              <input type="checkbox" id="prm_check_all" class="form-check-input">
            </th>
            <th>#</th>
            <th>Due Date</th>
            <th>Billing Month</th>
            <th>Voucher</th>
            <th class="text-end">Amount</th>
            <th class="text-end">Paid</th>
            <th class="text-end">Remaining</th>
            <th>Status</th>
            <th class="text-end">Pay Now</th>
          </tr>
        </thead>
        <tbody>
          @foreach($installments as $idx => $inst)
          @php
          $remaining = max(0, (float)$inst->amount - (float)$inst->paid_amount);
          @endphp
          <tr class="prm-installment-row" data-remaining="{{ $remaining }}" data-id="{{ $inst->id }}">
            <td class="prm-check-col">
              <input type="checkbox" class="form-check-input prm-row-check" value="{{ $inst->id }}"
                id="prm_check_{{ $inst->id }}">
            </td>
            <td class="fw-semibold">{{ $idx + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($inst->date)->format('d M Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($inst->billing_month)->format('M Y') }}</td>
            <td>
              @foreach($inst->vouchers as $v)
              <a href="{{ route('vouchers.show', $v->id) }}" target="_blank" class="small">{{ $v->formatted_id }}</a>@if(!$loop->last), @endif
              @endforeach
            </td>
            <td class="text-end fw-semibold">{{ number_format((float)$inst->amount, 2) }}</td>
            <td class="text-end text-success">{{ number_format((float)$inst->paid_amount, 2) }}</td>
            <td class="text-end text-danger fw-bold prm-remaining-cell" id="prm_rem_{{ $inst->id }}">
              {{ number_format($remaining, 2) }}
            </td>
            <td>{!! $inst->status_badge !!}</td>
            <td class="text-end">
              {{-- Hidden array inputs (name/index set by JS on submit) --}}
              <input type="text"
                class="prm-amount-input prm-pay-input"
                id="prm_pay_{{ $inst->id }}"
                data-id="{{ $inst->id }}"
                data-remaining="{{ $remaining }}"
                value="{{ number_format($remaining, 2, '.', '') }}"
                disabled
                placeholder="0.00">
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    {{-- Summary bar --}}
    <div class="prm-summary-bar">
      <div class="prm-stat">
        <span class="ps-label">Selected</span>
        <span class="ps-value" id="prm_selected_count">0</span>
      </div>
      <div class="prm-stat">
        <span class="ps-label">Total Selected Outstanding</span>
        <span class="ps-value text-danger" id="prm_total_outstanding">{{ $currSymbol }} 0.00</span>
      </div>
      <div class="prm-stat">
        <span class="ps-label">Total to Pay Now</span>
        <span class="ps-value text-success" id="prm_total_pay">{{ $currSymbol }} 0.00</span>
      </div>
      <div class="ms-auto">
        <button type="button" class="btn btn-sm btn-outline-secondary me-2" data-bs-dismiss="modal">
          <i class="fa fa-times me-1"></i>Cancel
        </button>
        <button type="submit" class="btn btn-sm btn-success" id="prm_submit_btn" disabled>
          <i class="fa fa-check me-1"></i>Process Payment
        </button>
      </div>
    </div>

  </form>
  @endif
</div>

<script>
  (function() {
    'use strict';

    const currSymbol = @json($currSymbol);
    const form = document.getElementById('prmForm');
    if (!form) return;

    const checkAll = document.getElementById('prm_check_all');
    const selectAll = document.getElementById('prm_select_all');
    const submitBtn = document.getElementById('prm_submit_btn');
    const rows = document.querySelectorAll('.prm-installment-row');

    function fmt(val) {
      return currSymbol + ' ' + parseFloat(val || 0).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      });
    }

    function refreshSummary() {
      let count = 0,
        totalOut = 0,
        totalPay = 0;

      rows.forEach(function(row) {
        const cb = row.querySelector('.prm-row-check');
        const inp = row.querySelector('.prm-pay-input');
        if (!cb || !inp) return;

        if (cb.checked) {
          count++;
          totalOut += parseFloat(row.dataset.remaining || 0);
          totalPay += parseFloat(inp.value || 0);
          row.classList.add('prm-row-selected');
        } else {
          row.classList.remove('prm-row-selected');
        }
      });

      document.getElementById('prm_selected_count').textContent = count;
      document.getElementById('prm_total_outstanding').textContent = fmt(totalOut);
      document.getElementById('prm_total_pay').textContent = fmt(totalPay);
      submitBtn.disabled = (count === 0 || totalPay <= 0);

      if (checkAll) {
        const checkedCount = document.querySelectorAll('.prm-row-check:checked').length;
        checkAll.indeterminate = checkedCount > 0 && checkedCount < rows.length;
        checkAll.checked = checkedCount === rows.length;
      }
    }

    function toggleRow(checkbox) {
      const id = checkbox.value;
      const inp = document.getElementById('prm_pay_' + id);
      if (!inp) return;

      if (checkbox.checked) {
        inp.disabled = false;
        inp.focus();
        inp.select();
      } else {
        inp.disabled = true;
      }
      refreshSummary();
    }

    // Row checkbox click
    rows.forEach(function(row) {
      const cb = row.querySelector('.prm-row-check');
      const inp = row.querySelector('.prm-pay-input');

      if (cb) {
        cb.addEventListener('change', function() {
          toggleRow(this);
        });
      }

      // Also allow clicking the row to toggle
      row.addEventListener('click', function(e) {
        if (e.target === cb || e.target === inp || e.target.tagName === 'A') return;
        if (cb) {
          cb.checked = !cb.checked;
          toggleRow(cb);
        }
      });

      if (inp) {
        inp.addEventListener('input', refreshSummary);

        // Validate: cannot exceed remaining
        inp.addEventListener('blur', function() {
          const remaining = parseFloat(this.dataset.remaining || 0);
          let val = parseFloat(this.value || 0);
          if (val <= 0) val = remaining;
          if (val > remaining) val = remaining;
          this.value = val.toFixed(2);
          refreshSummary();
        });
      }
    });

    // Select all checkbox
    if (checkAll) {
      checkAll.addEventListener('change', function() {
        rows.forEach(function(row) {
          const cb = row.querySelector('.prm-row-check');
          if (cb) {
            cb.checked = checkAll.checked;
            toggleRow(cb);
          }
        });
      });
    }

    // "Select All" button
    if (selectAll) {
      selectAll.addEventListener('click', function() {
        rows.forEach(function(row) {
          const cb = row.querySelector('.prm-row-check');
          if (cb && !cb.checked) {
            cb.checked = true;
            toggleRow(cb);
          }
        });
      });
    }

    // Form submit — inject indexed array inputs
    form.addEventListener('submit', function(e) {
      e.preventDefault();

      const selected = [];
      rows.forEach(function(row) {
        const cb = row.querySelector('.prm-row-check');
        const inp = row.querySelector('.prm-pay-input');
        if (cb && cb.checked && inp && !inp.disabled) {
          const amt = parseFloat(inp.value || 0);
          if (amt > 0) {
            selected.push({
              id: cb.value,
              amount: amt
            });
          }
        }
      });

      if (selected.length === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'Nothing selected',
          text: 'Please select at least one installment and enter a payment amount.'
        });
        return;
      }

      // Build hidden inputs
      selected.forEach(function(item, idx) {
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'installment_ids[' + idx + ']';
        idInput.value = item.id;
        form.appendChild(idInput);

        const amtInput = document.createElement('input');
        amtInput.type = 'hidden';
        amtInput.name = 'pay_amounts[' + idx + ']';
        amtInput.value = item.amount.toFixed(2);
        form.appendChild(amtInput);
      });

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Processing…';
      form.submit();
    });

    refreshSummary();
  })();
</script>