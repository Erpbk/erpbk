<table class="table" id="dataTableBuilder">
   <thead class="text-center">
      <tr id="rlHead" role="row">
         <th class="nosort" title="Select All" width="50px">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
         </th>
         <th class="sorting" data-k="invoice" title="Invoice Number">Invoice <span class="ar">⇅</span></th>
         <th class="sorting" data-k="inv_date" title="Inv Date">Inv Date <span class="ar">⇅</span></th>
         <th class="sorting" data-k="billing_month" title="Billing Month">Billing Month <span class="ar">⇅</span></th>
         <th class="sorting" data-k="employee" title="Employee">Employee <span class="ar">⇅</span></th>
         <th class="sorting" data-k="zone" title="Zone">Zone <span class="ar">⇅</span></th>
         <th class="sorting" data-k="descriptions" title="Descriptions">Descriptions <span class="ar">⇅</span></th>
         <th class="sorting" data-k="invoice_amount" title="Invoice Amount">Invoice Amount <span class="ar">⇅</span></th>
         <th class="sorting" data-k="payable_salary" title="Payable Salary">Payable Salary <span class="ar">⇅</span></th>
         <th class="sorting" data-k="paid_amount" title="Paid Amount">Paid Amount <span class="ar">⇅</span></th>
         <th class="sorting" data-k="balance" title="Balance">Balance <span class="ar">⇅</span></th>
         <th class="sorting" data-k="status" title="Status">Status <span class="ar">⇅</span></th>
         <th class="nosort" title="Action" width="120px">Actions</th>
      </tr>
   </thead>
   <tbody id="rlBody">
      @forelse($data as $r)
      @php
         $employee = $r->employee;
         $paidAmount = (float) $r->paid_amount;
         $balance = (float) $r->balance;
         $balanceClass = abs($balance) < 0.005 ? 'text-success' : 'text-danger';
      @endphp
      <tr class="text-center" data-id="{{ $r->id }}">
         <td>
            <input type="checkbox" class="invoice-checkbox" value="{{ $r->id }}" onchange="updateDeleteButton()">
         </td>
         <td style="white-space: nowrap;"><a href="javascript:void(0);" data-action="{{ route('employeeInvoices.show', $r->id) }}" data-size="xl" class="show-modal-right">{{ $r->invoice_number }}</a></td>
         <td>{{ $r->inv_date ? \Carbon\Carbon::parse($r->inv_date)->format('d M Y') : '—' }}</td>
         <td>{{ $r->billing_month ? \Carbon\Carbon::parse($r->billing_month)->format('M Y') : '—' }}</td>
         <td class="name">{{ $employee ? trim(($employee->employee_id ?? '').'-'.($employee->name ?? ''), '-') : '—' }}</td>
         <td>{{ $r->zone ?: '—' }}</td>
         <td>{{ $r->descriptions }}</td>
         <td>{{ number_format((float) $r->total_amount, 2) }}</td>
         <td>{{ number_format((float) $r->final_amount, 2) }}</td>
         <td class="text-success">{{ number_format($paidAmount, 2) }}</td>
         <td class="{{ $balanceClass }}">{{ number_format($balance, 2) }}</td>
         <td>
            @include('invoices.partials.status_badge', ['status' => $r->paymentStatusKey()])
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                  <a href="javascript:void(0);" data-action="{{ route('employeeInvoices.edit', $r->id) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Update Invoice">
                     <i class="fa fa-edit mx-1"></i> Update
                  </a>
                  @endcan
                  @endif
                  @if($eiLocked && $eiPaymentId)
                  @canany(['employees_payments_edit', 'cash_&_banks_payments_edit'])
                  <a href="javascript:void(0);" data-action="{{ route('payments.edit', $eiPaymentId) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Edit Payment">
                     <i class="fa fa-money-bill mx-1 text-success"></i> Edit Payment
                  </a>
                  @endcanany
                  @endif
                  @if($eiStatus !== 1)
                  @can('employees_payments_create')
                  <a href="javascript:void(0);" data-action="{{ route('payments.create', ['employee_payment' => 1, 'invoice_id' => $r->id]) }}" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Add Payment">
                     <i class="fa fa-money-bill mx-1"></i> Add Payment
                  </a>
                  @endcan
                  @can('employees_invoice_edit')
                  <a href="javascript:void(0);" data-action="{{ route('employeeInvoices.markAsSettled', $r->id) }}" class="dropdown-item waves-effect show-modal" data-size="lg" data-title="Mark Settled (no payment)">
                     <i class="fa fa-check-double mx-1 text-primary"></i> Mark Settled (no payment)
                  </a>
                  @endcan
                  @endif
                  @can('employees_invoice_view')
                  <a href="javascript:void(0);" data-action="{{ route('employeeInvoices.sendEmail', $r->id) }}" class="dropdown-item waves-effect show-modal" data-size="md" data-title="Send Invoice Email">
                     <i class="fa fa-envelope mx-1"></i> Send Email
                  </a>
                  @endcan
                  @can('employees_invoice_delete')
                  <a href="javascript:void(0);" onclick="confirmDelete('{{ route('employeeInvoices.delete', $r->id) }}')" class="dropdown-item waves-effect">
                     <i class="fa fa-trash mx-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="13" class="rl-empty text-center text-muted py-4">No employee invoices found.</td>
      </tr>
      @endforelse
   </tbody>
</table>
