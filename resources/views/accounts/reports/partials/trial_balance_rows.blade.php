@foreach($nodes as $node)
<tr
  class="tree-row {{ !empty($node['children']) ? 'has-children is-collapsed' : '' }}"
  data-node-id="{{ $node['id'] }}"
  data-parent-id="{{ $parentId }}"
  data-level="{{ $node['level'] }}"
>
  <td>{{ $node['code'] }}</td>
  <td>
    <div class="tree-cell" style="padding-left: {{ $node['level'] * 22 }}px;">
      @if(!empty($node['children']))
      <button type="button" class="tree-toggle" aria-label="Toggle account children" aria-expanded="false">
        <i class="ti ti-chevron-down"></i>
      </button>
      <span class="fw-semibold">{{ $node['name'] }}</span>
      @else
      <span class="tree-leaf-spacer"></span>
      <a href="javascript:void(0);" class="view-ledger text-primary text-decoration-none" data-id="{{ $node['id'] }}">{{ $node['name'] }}</a>
      @endif
    </div>
  </td>
  <td class="text-end">
    <span class="amount-expanded">{{ $node['own_debit'] != 0 ? number_format($node['own_debit'], 2) : '-' }}</span>
    @if(!empty($node['children']))
    <span class="amount-collapsed">{{ $node['subtotal_debit'] != 0 ? number_format($node['subtotal_debit'], 2) : '-' }}</span>
    @endif
  </td>
  <td class="text-end">
    <span class="amount-expanded">{{ $node['own_credit'] != 0 ? number_format($node['own_credit'], 2) : '-' }}</span>
    @if(!empty($node['children']))
    <span class="amount-collapsed">{{ $node['subtotal_credit'] != 0 ? number_format($node['subtotal_credit'], 2) : '-' }}</span>
    @endif
  </td>
</tr>
@if(!empty($node['children']))
  @include('accounts.reports.partials.trial_balance_rows', ['nodes' => $node['children'], 'parentId' => $node['id']])
@endif
@endforeach
