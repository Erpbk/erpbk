@php
$amountEditVoucherTypes = ['AL', 'COD', 'PN', 'PAY', 'VC'];
@endphp
<div id="rows-container" style="width: 98%;">
    @isset($data)
    @foreach($data as $entry)
    <div class="row">
        <div class="form-group col-md-3">
            <label for="exampleInputEmail1">Select Account</label>
            @if (in_array($voucherType, $amountEditVoucherTypes))
            <input type="hidden" name="account_id[]" value="{{ $entry->account_id ?? '' }}" />
            {!! Form::select('account_id[]', $accounts, $entry->account_id??null, ['class' => 'form-control form-select select2', 'disabled' => true]) !!}
            @else
            {!! Form::select('account_id[]', $accounts, $entry->account_id ?? null, [
            'class' => 'form-control form-select select2',
            'required' => true
            ]) !!}

            @endif
        </div>
        <div class="form-group col-md-4">
            <label>Narration</label>
            @if (in_array($voucherType, $amountEditVoucherTypes))
            <input type="hidden" name="narration[]" value="{{ $entry->narration }}" />
            <textarea class="form-control" rows="10" style="height: 40px !important;" readonly>{{ $entry->narration }}</textarea>
            @else
            <textarea name="narration[]" class="form-control " rows="10" placeholder="Narration" style="height: 40px !important;">{{$entry->narration}}</textarea>
            @endif
        </div>
        <div class="form-group col-md-2">
            <label>Dr Amount</label>
            <input type="number" step="any" name="dr_amount[]" value="{{$entry->debit}}" class="form-control  dr_amount" onchange="getTotal();" placeholder="Paid Amount">
        </div>
        <div class="form-group col-md-2">
            <label>Cr Amount</label>
            <input type="number" step="any" name="cr_amount[]" value="{{$entry->credit}}" class="form-control  cr_amount" onchange="getTotal();" placeholder="Paid Amount">
        </div>
        @if (in_array($voucherType, $amountEditVoucherTypes))
        @else
        <div class="form-group col-md-1 d-flex align-items-end">
            <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
        </div>
        @endif
        {{-- <div class="form-group col-md-1">
              <label style="visibility: hidden">plus</label>
              <button type="button" class="btn btn-primary btn-xs new_line"><i class="fa fa-plus"></i> </button>
          </div> --}}
    </div>
    @endforeach
    @else

    <div class="row">
        <div class="form-group col-md-3">
            <label for="exampleInputEmail1">Select Account</label>
            {!! Form::select('account_id[]', $accounts, null, [
            'class' => 'form-control form-select select2',
            'required' => true
            ]) !!}
        </div>
        <div class="form-group col-md-4">
            <label>Narration</label>
            <textarea name="narration[]" class="form-control" rows="10" placeholder="Narration" style="height: 40px !important;"></textarea>
        </div>
        <div class="form-group col-md-2">
            <label>Dr Amount</label>
            <input type="number" step="any" name="dr_amount[]" class="form-control dr_amount" placeholder="Paid Amount" onchange="getTotal();">
        </div>
        <div class="form-group col-md-2">
            <label>Cr Amount</label>
            <input type="number" step="any" name="cr_amount[]" class="form-control cr_amount" placeholder="Paid Amount" onchange="getTotal();">
        </div>
        <div class="form-group col-md-1 d-flex align-items-end">
            <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
        </div>
    </div>


    @endisset

</div>
@if (in_array($voucherType, $amountEditVoucherTypes))
@else
<button type="button" id="add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>
@endif