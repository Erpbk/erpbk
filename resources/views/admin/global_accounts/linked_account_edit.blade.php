{!! Form::model($accounts, ['route' => ['admin.global-accounts.linked-account.update', $globalAccount], 'method' => 'patch', 'id' => 'formajax']) !!}
<input type="hidden" id="reload_page" value="1"/>
<div class="card-body">
    <div class="row">
        @php $hideBranch = true; $showAccountCode = true; @endphp
        @include('accounts.fields')
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
    {!! Form::submit(__('Save'), ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
