@php
$depth = $depth ?? 0;
$includeChildren = $includeChildren ?? false;
$forceVisible = $forceVisible ?? false;
$hasChildren = isset($account->direct_children_count)
    ? (int) $account->direct_children_count > 0
    : ($account->relationLoaded('children') && $account->children->isNotEmpty());
$isRoot = empty($account->parent_id) || (int) $account->parent_id === 0;
$parentName = $parentName ?? null;
$searchText = strtolower(($account->name ?? '') . ' ' . ($account->account_code ?? '') . ' ' . ($account->account_type ?? '') . ' ' . ($parentName ?? '') . ' ' . $account->branch_name);
$__companySlug = \App\Support\CompanyRouteContext::slug();
$globalLinkedAccountIds = $globalLinkedAccountIds ?? [];
$isGlobalLinked = isset($globalLinkedAccountIds[$account->id]);
$childrenLoaded = $includeChildren && $account->relationLoaded('children');
$isExpanded = $hasChildren && ($includeChildren || ($forceVisible && $childrenLoaded));
@endphp
<tr class="chart-account-row {{ $depth > 0 ? 'child-row' : '' }} {{ $hasChildren ? 'is-parent' : 'is-leaf' }}{{ $forceVisible && $depth > 0 ? ' expanded' : '' }}{{ $isExpanded ? ' expanded' : '' }}{{ $hasChildren && !$isExpanded ? ' is-collapsed' : '' }}"
    data-account-id="{{ $account->id }}"
    data-parent-id="{{ $account->parent_id ?? '' }}"
    data-depth="{{ $depth }}"
    data-has-children="{{ $hasChildren ? '1' : '0' }}"
    data-children-loaded="{{ $childrenLoaded ? '1' : '0' }}"
    data-search="{{ $searchText }}"
    @if($depth > 0 && !$forceVisible) style="display:none;" @elseif($forceVisible && $depth > 0) style="display:table-row;" @endif>
  <td class="align-middle" data-col="account-name">
    <div class="tree-cell" style="padding-left: {{ $depth * 16 }}px;">
      @if($hasChildren)
      <button type="button" class="tree-toggle" aria-label="Toggle account children" aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
        <i class="ti ti-chevron-down"></i>
      </button>
      @else
      <span class="tree-leaf-spacer"></span>
      @endif
      @if($isRoot)
      <span class="tree-lock" title="{{ $account->is_locked ? 'Locked' : 'Unlocked' }}">
        <i class="fas {{ $account->is_locked ? 'fa-lock text-secondary' : 'fa-unlock text-success' }}"></i>
      </span>
      @endif
      @if($hasChildren)
      <span class="fw-semibold account-name-label">{{ $account->name }}</span>
      @else
      <a href="javascript:void(0);" class="view-ledger text-primary text-decoration-none fw-medium" data-id="{{ $account->id }}" title="View ledger">{{ $account->name }}</a>
      @endif
      @if($isGlobalLinked || $account->is_fixed)
      <span class="badge bg-label-primary">Fixed</span>
      @endif
    </div>
  </td>
  <td class="align-middle text-nowrap" data-col="account-code">{{ $account->account_code ?: '—' }}</td>
  <td class="align-middle text-nowrap" data-col="branch">{{ $account->branch_name }}</td>
  <td class="align-middle text-nowrap" data-col="account-type">{{ $account->account_type ?? '—' }}</td>
  <td class="align-middle" data-col="parent-account">{{ $parentName ?: '—' }}</td>
  <td class="align-middle" data-col="status">{!! App\Helpers\Common::status($account->status) !!}</td>
  <td class="align-middle text-end" data-col="actions">
    <div class="dropdown table-actions-dropdown">
      <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" data-bs-toggle="dropdown" title="Actions"><i class="fa fa-cog"></i></button>
      <ul class="dropdown-menu dropdown-menu-end">
        @can('accounts_coa_edit')
        @if(!$isGlobalLinked)
        @if(!$account->is_locked)
        <li><a class="dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('accounts.edit', ['company_slug' => $__companySlug, 'id' => $account->id]) }}" data-size="lg" data-title="Edit Account"><i class="fa fa-edit me-2"></i> Edit</a></li>
        @endif
        <li><a class="dropdown-item toggle-status" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.toggleStatus', ['company_slug' => $__companySlug, 'id' => $account->id]) }}" data-active="{{ $account->status == 1 ? '1' : '0' }}"><i class="fa fa-{{ $account->status == 1 ? 'pause-circle-o' : 'play-circle-o' }} me-2"></i> {{ $account->status == 1 ? 'Mark as Inactive' : 'Mark as Active' }}</a></li>
        @endif
        @endcan
        <li><a class="dropdown-item view-ledger" href="javascript:void(0);" data-id="{{ $account->id }}"><i class="fa fa-book me-2"></i> Ledger</a></li>
        @can('accounts_coa_delete')
        @if(!$isRoot && !$isGlobalLinked && !$account->is_locked)
        <li>
          <hr class="dropdown-divider">
        </li>
        <li><a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', ['company_slug' => $__companySlug, 'id' => $account->id]) }}"><i class="fa fa-trash me-2"></i> Delete</a></li>
        @endif
        @endcan
      </ul>
    </div>
  </td>
  <td class="coa-cc-pad" aria-hidden="true"></td>
  <td class="coa-cc-pad" aria-hidden="true"></td>
</tr>
@if($includeChildren && $account->relationLoaded('children') && $account->children->isNotEmpty())
@foreach($account->children as $child)
@include('accounts.account_table_row', [
    'account' => $child,
    'depth' => $depth + 1,
    'parentName' => $account->name,
    'globalLinkedAccountIds' => $globalLinkedAccountIds,
    'includeChildren' => true,
    'forceVisible' => true,
])
@endforeach
@endif
