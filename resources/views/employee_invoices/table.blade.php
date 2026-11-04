<table class="table" id="dataTableBuilder">
   <thead class="text-center">
      <tr id="rlHead" role="row">
         <th class="nosort" title="Select All" width="50px">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
         </th>
         <th class="sorting" data-k="invoice" title="Invoice Number">Invoice # <span class="ar">⇅</span></th>
         <th class="sorting" data-k="inv_date" title="Inv Date">Inv Date <span class="ar">⇅</span></th>
         <th class="sorting" data-k="billing_month" title="Billing Month">Billing Month <span class="ar">⇅</span></th>
         <th class="sorting" data-k="employee" title="Employee">Employee <span class="ar">⇅</span></th>
         <th class="sorting" data-k="branch" title="Branch">Branch <span class="ar">⇅</span></th>
         <th class="sorting" data-k="descriptions" title="Descriptions">Descriptions <span class="ar">⇅</span></th>
         <th class="sorting" data-k="subtotal" title="Subtotal">Subtotal (AED) <span class="ar">⇅</span></th>
         <th class="sorting" data-k="vat" title="VAT">VAT (AED) <span class="ar">⇅</span></th>
         <th class="sorting" data-k="total" title="Total">Total (AED) <span class="ar">⇅</span></th>
         <th class="sorting" data-k="status" title="Status">Status <span class="ar">⇅</span></th>
         <th class="nosort" title="Action" width="90px">Action</th>
      </tr>
   </thead>
   <tbody id="rlBody">
      @forelse($data as $r)
      @php
         $employee = $r->employee;
         $branch = $employee?->branch;
         $branchLabel = $branch
            ? trim(($branch->name ?? '') . (!empty($branch->code) ? ' (' . $branch->code . ')' : ''))
            : '';
         $statusKey = $r->paymentStatusKey();
         $balance = (float) $r->balance;
         $subtotal = (float) ($r->subtotal ?? 0);
         $vat = (float) ($r->vat ?? 0);
         $total = (float) ($r->total_amount ?? ($subtotal + $vat));
         $empName = $employee->name ?? '—';
         $empCode = $employee->employee_id ?? '';
         $showUrl = safe_route('employeeInvoices.show', $r->id, 'employeeInvoices/{id}');
         $hvInitials = $employee
            ? collect(explode(' ', (string) $employee->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('')
            : '';
         $hvPhoto = $employee?->profile_image_url ?? '';
      @endphp
      <tr class="text-center" data-id="{{ $r->id }}">
         <td>
            <input type="checkbox" class="invoice-checkbox" value="{{ $r->id }}" onchange="updateDeleteButton()">
         </td>
         <td style="white-space: nowrap;">
            <a href="javascript:void(0);" data-action="{{ $showUrl }}" data-size="xl" class="show-modal-right ei-inv-link">{{ $r->invoice_number }}</a>
         </td>
         <td>{{ $r->inv_date ? \Carbon\Carbon::parse($r->inv_date)->format('d M Y') : '—' }}</td>
         <td>{{ $r->billing_month ? \Carbon\Carbon::parse($r->billing_month)->format('M Y') : '—' }}</td>
         <td>
            <div class="ei-emp">
               @if($employee)
               <a href="javascript:void(0);"
                  class="ei-name emp-name-link show-modal-right"
                  data-action="{{ $showUrl }}"
                  data-size="xl"
                  data-hv-id="{{ $empCode }}"
                  data-hv-name="{{ $empName }}"
                  data-hv-photo="{{ $hvPhoto }}"
                  data-hv-initials="{{ strtoupper($hvInitials) }}">{{ $empName }}</a>
               @if($empCode !== '')
               <span class="ei-code">{{ $empCode }}</span>
               @endif
               @else
               <span>—</span>
               @endif
            </div>
         </td>
         <td>
            @if($branchLabel !== '')
            <span class="ei-branch-pill">{{ $branchLabel }}</span>
            @else
            <span class="ei-dash">—</span>
            @endif
         </td>
         <td class="text-start ei-desc">{{ $r->descriptions ?: '—' }}</td>
         <td class="ei-money">{{ number_format($subtotal, 2) }}</td>
         <td class="ei-money">{{ number_format($vat, 2) }}</td>
         <td class="ei-money ei-total">{{ number_format($total, 2) }}</td>
         <td>
            <div class="ei-status-cell">
               @include('invoices.partials.status_badge', ['status' => $statusKey])
               @if($statusKey === 'partial' && abs($balance) >= 0.005)
               <span class="ei-status-due">{{ number_format($balance, 2) }} due</span>
               @endif
            </div>
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Actions">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end">
                  @php
                     $eiStatus = (int) ($r->status ?? 0);
                     $eiLocked = in_array($eiStatus, [1, 3], true);
                     $eiPaymentId = $eiLocked
                        ? \App\Support\InvoicePaymentLink::latestId(
                            $r,
                            $employee?->account_id ? (int) $employee->account_id : null
                        )
                        : null;
                  @endphp
                  @if(! $eiLocked)
                  @can('employees_invoice_edit')
                  <a href="javascript:void(0);" data-action="{{ safe_route('employeeInvoices.edit', $r->id, 'employeeInvoices/{id}/edit') }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Update Invoice">
                     <i class="fa fa-edit mx-1"></i> Update
                  </a>
                  @endcan
                  @endif
                  @if($eiLocked && $eiPaymentId)
                  @canany(['employees_payments_edit', 'cash_&_banks_payments_edit'])
                  <a href="javascript:void(0);" data-action="{{ safe_route('payments.edit', $eiPaymentId, 'payments/{id}/edit') }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Edit Payment">
                     <i class="fa fa-money-bill mx-1 text-success"></i> Edit Payment
                  </a>
                  @endcanany
                  @endif
                  @if($eiStatus !== 1)
                  @can('employees_payments_create')
                  <a href="javascript:void(0);" data-action="{{ safe_route('payments.create', ['employee_payment' => 1, 'invoice_id' => $r->id], 'payments/create') }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Add Payment">
                     <i class="fa fa-money-bill mx-1"></i> Add Payment
                  </a>
                  @endcan
                  @can('employees_invoice_edit')
                  <a href="javascript:void(0);" data-action="{{ safe_route('employeeInvoices.markAsSettled', $r->id, 'employee/invoice-mark-settled/{id}') }}" class="dropdown-item waves-effect show-modal" data-size="lg" data-title="Mark Settled (no payment)">
                     <i class="fa fa-check-double mx-1 text-primary"></i> Mark Settled (no payment)
                  </a>
                  @endcan
                  @endif
                  @can('employees_invoice_view')
                  <a href="javascript:void(0);" data-action="{{ safe_route('employeeInvoices.sendEmail', $r->id, 'employeeInvoices/sendemail/{id}') }}" class="dropdown-item waves-effect show-modal" data-size="md" data-title="Send Invoice Email">
                     <i class="fa fa-envelope mx-1"></i> Send Email
                  </a>
                  @endcan
                  @can('employees_invoice_delete')
                  <a href="javascript:void(0);" onclick="confirmDelete('{{ safe_route('employeeInvoices.delete', $r->id, 'employeeInvoices/delete/{id}') }}')" class="dropdown-item waves-effect">
                     <i class="fa fa-trash mx-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="12" class="rl-empty text-center text-muted py-4">No employee invoices found.</td>
      </tr>
      @endforelse
   </tbody>
</table>
