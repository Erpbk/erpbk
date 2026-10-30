<table class="table" id="dataTableBuilder">
   <thead class="text-center">
      <tr id="rlHead" role="row">
         <th class="nosort" title="Select All" width="50px">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
         </th>
         <th class="sorting" data-k="invoice" title="Invoice Number">Invoice <span class="ar">⇅</span></th>
         <th class="sorting" data-k="inv_date" title="Inv Date">Inv Date <span class="ar">⇅</span></th>
         <th class="sorting" data-k="billing_month" title="Billing Month">Billing Month <span class="ar">⇅</span></th>
         <th class="sorting" data-k="person_code" title="Person Code">Person Code <span class="ar">⇅</span></th>
         <th class="sorting" data-k="rider" title="Rider">Rider <span class="ar">⇅</span></th>
         <th class="sorting" data-k="rider_status" title="Rider Status">Rider Status <span class="ar">⇅</span></th>
         <th class="sorting" data-k="descriptions" title="Descriptions">Descriptions <span class="ar">⇅</span></th>
         <th class="sorting" data-k="project" title="Project">Project <span class="ar">⇅</span></th>
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
         $rider = $r->rider;
         $riderStatus = $rider
            ? \App\Models\Riders::employmentStatusDisplay($rider->status ?? null)
            : ['label' => '—', 'badge' => 'bg-label-secondary'];
         $paidAmount = (float) $r->paid_amount;
         $balance = (float) $r->balance;
         $balanceClass = abs($balance) < 0.005 ? 'text-success' : 'text-danger';
      @endphp
      <tr class="text-center" data-id="{{ $r->id }}">
         <td>
            <input type="checkbox" class="invoice-checkbox" value="{{ $r->id }}" onchange="updateDeleteButton()">
         </td>
         <td style="white-space: nowrap;"><a href="javascript:void(0);" data-action="{{ route('riderInvoices.show', $r->id) }}" data-size="xl" class="show-modal-right">{{ $r->invoice_number }}</a></td>
         <td>{{ $r->inv_date ? \Carbon\Carbon::parse($r->inv_date)->format('d M Y') : '—' }}</td>
         <td>{{ $r->billing_month ? \Carbon\Carbon::parse($r->billing_month)->format('M Y') : '—' }}</td>
         <td>{{ $rider?->person_code ?? '—' }}</td>
         <td class="name">
            @if($rider)
            <a href="{{ route('rider.ledger', $rider->id) }}" target="_blank">{{ $rider->rider_id . '-' . $rider->name }}</a>
            @else
            —
            @endif
         </td>
         <td>
            <span class="badge {{ $riderStatus['badge'] }}">{{ $riderStatus['label'] }}</span>
         </td>
         <td>{{ $r->descriptions }}</td>
         <td>{{ $invoice->customer?->name ?? $rider?->customer?->name ?? '—' }}</td>
         <td>{{ number_format((float) $r->total_amount, 2) }}</td>
         <td>{{ number_format((float) $r->final_amount, 2) }}</td>
         <td class="text-success">{{ number_format($paidAmount, 2) }}</td>
         <td class="{{ $balanceClass }}">{{ number_format($balance, 2) }}</td>
         <td>
            @include('invoices.partials.status_badge', ['status' => $r->paymentStatusKey()])
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown-{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown-{{ $r->id }}">
                  @php
                     $riStatus = (int) ($r->status ?? 0);
                     $riLocked = in_array($riStatus, [1, 3], true);
                     $riPaymentId = $riLocked
                        ? \App\Support\InvoicePaymentLink::latestId(
                            $r,
                            isset($rider->account_id) ? (int) $rider->account_id : null
                        )
                        : null;
                  @endphp
                  @if(! $riLocked)
                  @can('riders_invoices_edit')
                  <a href="javascript:void(0);" data-action="{{ route('riderInvoices.edit', $r->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Update Invoice">
                     <i class="fa fa-edit mx-1"></i> Update
                  </a>
                  @endcan
                  @endif
                  @if($riLocked && $riPaymentId)
                  @canany(['riders_payments_edit', 'cash_&_banks_payments_edit'])
                  <a href="javascript:void(0);" data-action="{{ route('payments.edit', $riPaymentId) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Payment">
                     <i class="fa fa-money-bill mx-1 text-success"></i> Edit Payment
                  </a>
                  @endcanany
                  @endif
                  @if($riStatus !== 1)
                  @can('riders_payments_create')
                  <a href="javascript:void(0);" data-action="{{ route('payments.create') }}?invoice_type=rider&invoice_id={{ $r->id }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Record Rider Payment">
                     <i class="fa fa-money-bill mx-1 text-success"></i> Record Payment
                  </a>
                  @endcan
                  @canany(['riders_invoices_edit', 'riders_invoice_edit'])
                  <a href="javascript:void(0);" data-action="{{ route('riderInvoices.markAsSettled', $r->id) }}" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Mark Settled (no payment)">
                     <i class="fa fa-check-double mx-1 text-primary"></i> Mark Settled (no payment)
                  </a>
                  @endcanany
                  @endif
                  @can('riders_invoices_delete')
                  <a href="javascript:void(0);" onclick="confirmDelete('{{route('riderInvoices.delete', $r->id)}}')" class='dropdown-item waves-effect'>
                     <i class="fa fa-trash mx-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="15" class="rl-empty text-center text-muted py-4">No rider invoices found.</td>
      </tr>
      @endforelse
   </tbody>
</table>
@include('delete_requests._pending_table_script', ['items' => $data])
