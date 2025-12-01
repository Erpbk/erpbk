<!-- Account Code Field -->
<div class="col-sm-12">
    {!! Form::label('account_code', 'Account Code:') !!}
    <p>{{ $accounts->account_code }}</p>
</div>

<!-- Account Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Account Name:') !!}
    <p>{{ $accounts->name }}</p>
</div>

<!-- Account Type Field -->
<div class="col-sm-12">
    {!! Form::label('account_type', 'Account Type:') !!}
    <p>{{ $accounts->account_type }}</p>
</div>

<!-- Parent Account Id Field -->
<div class="col-sm-12">
    {!! Form::label('parent_id', 'Parent Account Id:') !!}
    <p>{{ $accounts->parent_id }}</p>
</div>

<!-- Opening Balance Field -->
<div class="col-sm-12">
    {!! Form::label('opening_balance', 'Opening Balance:') !!}
    <p>{{ $accounts->opening_balance }}</p>
</div>

