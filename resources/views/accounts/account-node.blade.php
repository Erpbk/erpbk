@php
  $depth = $depth ?? 0;
  $hasChildren = $account->children && $account->children->count() > 0;
  $isRoot = is_null($account->parent_id);
@endphp
<li class="tree-node" data-account-id="{{ $account->id }}">
  <div class="tree-row">
    @if($depth > 0)
      <span class="tree-indent" style="width: {{ $depth * 20 }}px;"></span>
      <span class="tree-lines"><span class="tree-joint"></span></span>
    @else
      <span class="tree-indent"></span>
    @endif
    <span class="tree-check-wrap">
      @if($hasChildren)
        <input type="checkbox" class="tree-expand-check" aria-expanded="false" title="Expand/collapse children">
      @else
        <span class="tree-check-placeholder"></span>
      @endif
    </span>
    @if($isRoot)
      <span class="tree-lock" title="{{ $account->is_locked ? 'Locked' : 'Unlocked' }}">
        <i class="fas {{ $account->is_locked ? 'fa-lock text-secondary' : 'fa-unlock text-success' }}"></i>
      </span>
    @else
      <span class="tree-lock"></span>
    @endif
    <span class="tree-name">
      <a href="javascript:void(0);" class="view-ledger" data-id="{{ $account->id }}">{{ $account->account_code }}{{ $account->account_code ? '-' : '' }}{{ $account->name }}</a>
      <span class="tree-meta"><small>({{ $account->account_type }})</small></span>
      {!! App\Helpers\Common::status($account->status) !!}
    </span>
    <span class="tree-actions">
      <button type="button" class="btn-settings" title="Actions"><i class="fa fa-cog"></i></button>
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
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger delete-account" href="javascript:void(0);" data-id="{{ $account->id }}" data-url="{{ route('accounts.destroy', $account->id) }}"><i class="fa fa-trash me-2"></i> Delete</a></li>
          @endif
        @endcan
      </ul>
    </span>
  </div>
  @if($hasChildren)
    <ul class="nested d-none">
      @foreach($account->children as $child)
        @include('accounts.account-node', ['account' => $child, 'depth' => $depth + 1])
      @endforeach
    </ul>
  @endif
</li>
