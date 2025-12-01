<li style="list-style:none; cursor: pointer;border-bottom:1px solid #efefef;"
  class="text-primary py-2"
  data-account-id="{{ $account->id }}">

  <span class="toggle-btn plus-box" style="">+</span>
  {{ $account->account_code }}-{{ $account->name }}
  <span class="text-muted"><small>({{ $account->account_type }})</small></span>
  {!! App\Helpers\Common::status($account->status) !!}

  {{-- ✅ Show lock only if account is root (no parent_id) --}}
  @if (is_null($account->parent_id))
  <span class="lock-toggle"
    style="cursor:pointer;"
    title="{{ $account->is_locked ? 'Parent account is locked' : 'Unlocked' }}"
    data-account-id="{{ $account->id }}"
    data-locked="{{ $account->is_locked ? '1' : '0' }}">
    <i class="fas {{ $account->is_locked ? 'fa-lock text-secondary' : 'fa-unlock text-success' }}"></i>
  </span>
  @endif

  <span class="text-muted">{!! $account->notes !!}</span>

  <span style="float:right;">
    {!! Form::open(['route' => ['accounts.destroy', $account->id], 'method' => 'delete','id'=>'formajax']) !!}

    <a href="javascript:void(0);"
      data-size="lg"
      data-title="Edit Account"
      data-action="{{ route('accounts.edit', $account->id) }}"
      class="btn btn-info px-1 py2 edit-btn waves-effect waves-light
       {{ ($account->is_locked) ? 'locked-btn' : 'show-modal' }}"
      {{ ($account->is_locked) ? 'disabled' : '' }}>
      <i class="fa fa-edit fa-xs"></i>
    </a>

    <input type="hidden" id="reload_page" value="1" />
    {!! Form::button('<i class="fa fa-trash"></i>', [
    'type' => 'submit',
    'class' => 'btn btn-danger btn-xs p-1 delete-btn' . ($account->is_locked ? ' locked-btn' : ''),
    'onclick' => 'return confirm("Are you sure? You will not be able to revert this!")',
    ($account->is_locked ? 'disabled' : null)
    ]) !!}
    {!! Form::close() !!}
  </span>

  @if ($account->children->count() > 0)
  <ul class="nested d-none">
    @foreach ($account->children as $child)
    @include('accounts.account-node', ['account' => $child])
    @endforeach
  </ul>
  @endif
</li>