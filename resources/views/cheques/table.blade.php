@php
  $tableCols = $tableColumns ?? [];
  if ($tableCols === []) {
      $tableCols = [
          ['data' => 'reference', 'title' => 'Reference'],
          ['data' => 'cheque_number', 'title' => 'Cheque No.'],
          ['data' => 'cheque_date', 'title' => 'Cheque Date'],
          ['data' => 'type', 'title' => 'Type'],
          ['data' => 'party', 'title' => 'Sender/Receiver'],
          ['data' => 'amount', 'title' => 'Amount'],
          ['data' => 'cleared_amount', 'title' => 'Cleared Amount'],
          ['data' => 'voucher_id', 'title' => 'Voucher'],
          ['data' => 'attachment', 'title' => 'Attachment'],
          ['data' => 'description', 'title' => 'Description'],
          ['data' => 'status', 'title' => 'Status'],
      ];
  }
  $dataColumns = array_values(array_filter($tableCols, function ($c) {
      $k = $c['data'] ?? ($c['key'] ?? null);
      if (in_array($k, ['search', 'control', 'action'], true)) {
          return false;
      }
      if ($k === 'party') {
          return field_visible('cheques', 'payee_name') || field_visible('cheques', 'payer_name');
      }

      return field_visible('cheques', $k);
  }));
  $listTable = $listTable ?? false;
  $nextDueId = isset($nextDueId) ? (int) $nextDueId : null;
  $today = now()->startOfDay();
  $colCount = max(1, count($dataColumns) + 1);
@endphp
<table class="table{{ $listTable ? '' : ' dataTable' }}" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row" id="rlHead">
            @foreach($dataColumns as $col)
            @php
              $title = $col['title'] ?? ($col['name'] ?? ($col['data'] ?? ''));
              $key = $col['data'] ?? ($col['key'] ?? '');
              $sortable = ! in_array($key, ['attachment', 'party'], true);
            @endphp
            <th title="{{ $title }}" class="{{ $sortable ? 'sorting' : 'nosort' }}" data-k="{{ $key }}" tabindex="0">{{ $title }}@if($sortable)<span class="ar">⇅</span>@endif</th>
            @endforeach
            <th title="Actions" width="120px" class="nosort" tabindex="0">Actions</th>
        </tr>
    </thead>
    <tbody id="rlBody">
        @php
          $__companySlug = \App\Support\CompanyRouteContext::slug();
          $voucherShowParams = static function ($voucherId) use ($__companySlug): array {
            $params = ['voucher' => $voucherId];
            if (!empty($__companySlug)) {
              $params['company_slug'] = $__companySlug;
            }
            return $params;
          };
        @endphp
        @forelse($data as $cheque)
        @php
          $chequePendingDeletion = record_is_pending_deletion($cheque);
          $statusSlug = strtolower(str_replace(' ', '-', (string) $cheque->status));
          $dueTag = null;
          $isOverdue = false;
          if ($cheque->status === 'Issued' && ! $cheque->is_security && $cheque->cheque_date) {
            $days = (int) $today->diffInDays($cheque->cheque_date->copy()->startOfDay(), false);
            if ($days < 0) {
              $dueTag = ['od', 'Overdue'];
              $isOverdue = true;
            } elseif ($days === 0) {
              $dueTag = ['td', 'Today'];
            } elseif ($days === 1) {
              $dueTag = ['td', 'Tomorrow'];
            } elseif ($days <= 30) {
              $dueTag = ['up', 'In '.$days.' days'];
            }
          }
        @endphp
        <tr data-id="{{ $cheque->id }}" @class([
            'table-warning' => $chequePendingDeletion,
            'chq-blink' => $isOverdue,
            'text-center' => true,
        ])>
            @foreach($dataColumns as $col)
            @php $key = $col['data'] ?? ($col['key'] ?? null); @endphp
            @switch($key)
            @case('reference')
            <td>{{ $cheque->reference ?: '-' }}</td>
            @break
            @case('cheque_number')
            <td>
                <a href="javascript:void(0);" class="show-modal text-primary" data-size="xl"
                data-action="{{ route('cheques.show', $cheque->id) }}"
                data-title="Cheque Details">
                    {{ $cheque->cheque_number }}
                </a>
                @include('delete_requests._pending_badge', ['model' => $cheque])
            </td>
            @break
            @case('cheque_date')
            <td>
                {{ $cheque->cheque_date?->format('d M Y') ?? '-' }}
                @if($dueTag)
                    <br><span class="chq-due {{ $dueTag[0] }}">{{ $dueTag[1] }}</span>
                @endif
            </td>
            @break
            @case('type')
            <td>
                @if($cheque->is_security)
                    <span class="chq-bdg sec">Security Cheque</span>
                @endif
                @if($cheque->type == 'payable')
                    <span class="chq-bdg pay">Payable</span>
                @else
                    <span class="chq-bdg rec">Receivable</span>
                @endif
            </td>
            @break
            @case('party')
            <td class="who">
                @if($cheque->type == 'payable')
                    <a href="{{ route('accounts.ledger', ['company_slug' => \App\Support\CompanyRouteContext::slug()]) }}?account={{ $cheque->payee_account }}" target="_blank">
                        {{ $cheque->payee_name ?? '-' }}
                    </a>
                @else
                    @if($cheque->payer?->ref_name == 'Rider')
                        <a href="{{ route('rider.ledger',  $cheque->payer->ref_id)}}" target="_blank">
                            {{ $cheque->payer_name ?? '-' }}
                        </a>
                    @else
                        <a href="{{ route('accounts.ledger', ['company_slug' => \App\Support\CompanyRouteContext::slug()]) }}?account={{ $cheque->payer_account }}" target="_blank">
                            {{ $cheque->payer_name ?? '-' }}
                        </a>
                    @endif
                @endif
            </td>
            @break
            @case('amount')
            <td class="am">{!! \App\Helpers\Currency::format($cheque->amount, 2) !!}</td>
            @break
            @case('cleared_amount')
            <td class="am">{!! \App\Helpers\Currency::format($cheque->cleared_amount ?? 0, 2) !!}</td>
            @break
            @case('voucher_id')
            <td>
                @php
                    $clearVouchers = $cheque->relationLoaded('clear_vouchers')
                        ? $cheque->getRelation('clear_vouchers')
                        : $cheque->linkedClearVouchers();
                @endphp
                @if($clearVouchers->isNotEmpty())
                    <div class="d-flex flex-column gap-1 align-items-center">
                        @foreach($clearVouchers as $clearVoucher)
                            <a href="javascript:void(0);" data-action="{{ route('vouchers.show', $voucherShowParams($clearVoucher->id)) }}" class="text-primary show-voucher-panel" data-title="Voucher Against Cheque#{{ $cheque->cheque_number }}" data-collapse-sidebar="1">
                                {{ ($clearVoucher->voucher_type ?? 'V') . '-' . $clearVoucher->id }}
                            </a>
                        @endforeach
                    </div>
                @else
                    -
                @endif
            </td>
            @break
            @case('attachment')
            <td>
                @if($cheque->attachment)
                    <a href="{{ storage_url('vouchers/' . $cheque->attachment) }}" class="chq-vbtn" target="_blank" rel="noopener">
                        <i class="fa fa-file"></i> View
                    </a>
                @else
                    -
                @endif
            </td>
            @break
            @case('description')
            <td class="desc text-start">{{ $cheque->description }}</td>
            @break
            @case('status')
            <td>
                <span class="chq-st {{ $cheque->status }} status-{{ $statusSlug }}">{{ $cheque->status }}</span>
            </td>
            @break
            @default
            <td>-</td>
            @endswitch
            @endforeach
            <td style="position: relative;">
                @if($chequePendingDeletion)
                    <span class="text-muted small pending-deletion-lock"><i class="ti ti-lock me-1"></i>Locked</span>
                @else
                <div class="dropdown">
                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $cheque->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $cheque->id }}" style="z-index: 1050;">
                    @include('layouts.partials.module_contract_action', [
                        'module' => 'cheques',
                        'recordId' => $cheque->id,
                    ])
                    @can('cash_&_banks_cheques_edit')
                        @if(!in_array($cheque->status, ['Cleared', 'Partially Cleared'], true))
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Edit {{ ucwords($cheque->type) }} Cheque" data-action="{{ route('cheques.edit', $cheque->id) }}">
                            <i class="fa fa-edit my-1"></i> Edit
                        </a>
                        @endif
                        @if(in_array($cheque->status, ['Cleared', 'Partially Cleared'], true))
                            @php
                                $clearDocs = $cheque->linkedClearDocumentsQuery()->get();
                                $isPayableCheque = $cheque->isPayable();
                            @endphp
                            @foreach($clearDocs as $clearDoc)
                                @php
                                    $clearLabel = $clearDoc->voucher_id
                                        ? (($isPayableCheque ? 'PV' : 'RV') . '-' . $clearDoc->voucher_id)
                                        : (($isPayableCheque ? 'Payment' : 'Receipt') . ' #' . $clearDoc->id);
                                    $clearEditRoute = $isPayableCheque
                                        ? route('payments.edit', ['payment' => $clearDoc->id, 'from_cheques' => 1])
                                        : route('receipts.edit', ['receipt' => $clearDoc->id, 'from_cheques' => 1]);
                                @endphp
                                <a href="javascript:void(0);" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Edit {{ $clearLabel }}" data-action="{{ $clearEditRoute }}">
                                    <i class="fa fa-edit my-1"></i> Edit {{ $clearLabel }}
                                </a>
                            @endforeach
                        @endif
                        @if(in_array($cheque->status, ['Issued', 'Partially Cleared', 'Returned', 'Stop Payment', 'Lost'], true))
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Change Status" data-action="{{ route('cheques.status-form', $cheque->id) }}">
                            <i class="fa fa-exchange-alt my-1"></i> Change Status
                        </a>
                        @endif
                    @endcan
                    @can('cash_&_banks_cheques_delete')
                        @if(!in_array($cheque->status, ['Cleared', 'Partially Cleared'], true))
                        <a href="javascript:void(0);" class='dropdown-item waves-effect delete-cheque'
                            data-url="{{ route('cheques.destroy', $cheque->id) }}">
                            <i class="fa fa-trash my-1"></i> Delete
                        </a>
                        @endif
                    @endcan
                </div>
                </div>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="{{ $colCount }}" style="padding:30px;color:#94a3b8">No cheques found</td>
        </tr>
        @endforelse
    </tbody>
</table>
@if(!$listTable)
@if($data->isEmpty())
    <div class="text-center mt-5">
        <h3>No Cheques found</h3>
    </div>
@endif
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
@include('delete_requests._confirm_delete_script', [
    'entityName' => 'Cheque',
    'confirmText' => 'This will submit a delete request or move the cheque to the Recycle Bin.',
])
@push('page-scripts')
<script>
    (function () {
        var $table = $('#dataTableBuilder');
        if (!$table.length || !$.fn.DataTable) return;

        // Avoid double-init when this partial is rendered more than once.
        if ($.fn.DataTable.isDataTable($table)) {
            $table.DataTable().destroy();
        }

        $table.DataTable({
            "paging": true,
            "pageLength": 50,
            "searching": true,
            "ordering": false,
            "info": true,
            "autoWidth": true,
            "dom": "<'row'<'col-md-12'tr>>" +
                "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
        });
        $('#quickSearch').on('keyup change', function() {
            $table.DataTable().search(this.value).draw();
        });
        var initialSearch = ($('#quickSearch').val() || '').trim();
        if (initialSearch) {
            $table.DataTable().search(initialSearch).draw();
        }
        $(document).on('click', '.delete-cheque', function(e) {
            e.preventDefault();
            confirmDelete($(this).data('url'));
        });
    })();
</script>
@endpush
@include('delete_requests._pending_table_script', ['items' => $data])
@endif
