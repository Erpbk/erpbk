@php
$depth = $depth ?? 0;
$hasChildren = $account->children && $account->children->count() > 0;
$isRoot = is_null($account->parent_id);
$parentName = $parentName ?? ($account->parent ? $account->parent->name : null);
$searchText = strtolower(($account->name ?? '') . ' ' . ($account->account_code ?? '') . ' ' . ($account->account_type ?? '') . ' ' . ($parentName ?? ''));
@endphp
<tr class="chart-account-row {{ $depth > 0 ? 'child-row' : '' }}" data-account-id="{{ $account->id }}" data-parent-id="{{ $account->parent_id ?? '' }}" data-depth="{{ $depth }}" data-search="{{ $searchText }}" {{ $depth > 0 ? 'style="display:none;"' : '' }}>
  <td class="align-middle" data-col="account-name">
    <div class="d-flex align-items-center">
      @if($depth > 0)
      <span class="tree-indent" style="width: {{ $depth * 20 }}px;"></span>
      <span class="tree-lines me-1"><span class="tree-joint"></span></span>
      @endif
      <span class="tree-check-wrap me-1">
        @if($hasChildren)
        <input type="checkbox" class="tree-expand-check form-check-input" aria-expanded="false" title="Expand/collapse children">
        @else
        <span class="tree-check-placeholder" style="width:18px;height:18px;display:inline-block;"></span>
        @endif
      </span>
      @if($isRoot)
      <span class="tree-lock me-1" title="{{ $account->is_locked ? 'Locked' : 'Unlocked' }}">
        <i class="fas {{ $account->is_locked ? 'fa-lock text-secondary' : 'fa-unlock text-success' }}"></i>
      </span>
      @else
      <span class="tree-lock me-1" style="width:20px;display:inline-block;"></span>
      @endif
      <a href="javascript:void(0);" class="view-ledger text-primary text-decoration-none fw-medium" data-id="{{ $account->id }}">{{ $account->name }}</a>
    </div>
  </td>
  <td class="align-middle text-nowrap" data-col="account-code">{{ $account->account_code ?: '—' }}</td>
  <td class="align-middle text-nowrap" data-col="account-type">{{ $account->account_type ?? '—' }}</td>
  <td class="align-middle" data-col="parent-account">{{ $parentName ?: '—' }}</td>
  <td class="align-middle" data-col="status">{!! App\Helpers\Common::status($account->status) !!}</td>
  <td class="align-middle text-end" data-col="actions">
    <div class="dropdown table-actions-dropdown">
      <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="dropdown" title="Actions"><i class="fa fa-cog"></i></button>
      <ul class="dropdown-menu dropdown-menu-end">
        @can('account_edit')
        @if(!$account->is_locked)
        <li><a class="dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('accounts.edit', $account->id) }}" data-size="lg" data-title="Edit Account"><i class="fa fa-edit me-2"></i> Edit</a></li>
        @endif
        <li><a class="dropdown-item lock-toggle" href="javascript:void(0);" data-account-id="{{ $account->id }}"><i class="fa fa-{{ $account->is_locked ? 'unlock' : 'lock' }} me-2"></i> {{ $account->is_locked ? 'Unlock' : 'Lock' }}</a></li>
        <li><a class="dropdown-item toggle-status" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleStatus', $account->id) }}" data-active="{{ $account->status == 1 ? '1' : '0' }}"><i class="fa fa-{{ $account->status == 1 ? 'pause-circle-o' : 'play-circle-o' }} me-2"></i> {{ $account->status == 1 ? 'Mark as Inactive' : 'Mark as Active' }}</a></li>
        @endcan
        <li><a class="dropdown-item view-ledger" href="javascript:void(0);" data-id="{{ $account->id }}"><i class="fa fa-book me-2"></i> Ledger</a></li>
        @can('account_delete')
        @if(!$isRoot)
        <li>
          <hr class="dropdown-divider">
        </li>
        <li><a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', $account->id) }}"><i class="fa fa-trash me-2"></i> Delete</a></li>
        @endif
        @endcan
      </ul>
    </div>
  </td>
</tr>
@if($hasChildren)
@foreach($account->children as $child)
@include('accounts.account_table_row', ['account' => $child, 'depth' => $depth + 1, 'parentName' => $account->name])
@endforeach
@endif