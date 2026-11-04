@php
$amountEditVoucherTypes = ['AL', 'COD', 'PN', 'INC', 'PAY', 'VC'];
$isAmountEditType = in_array($voucherType ?? '', $amountEditVoucherTypes);
@endphp

<div class="table-responsive rpf-invoice-scroll mb-2">
    <table class="table table-bordered uvf-lines-table rpf-invoice-table mb-0" id="uvf-lines-table">
        <thead>
            <tr>
                <th>Account</th>
                <th>Narration</th>
                <th class="text-end" style="width:120px">Dr</th>
                <th class="text-end" style="width:120px">Cr</th>
                @unless($isAmountEditType)
                <th style="width:44px"></th>
                @endunless
            </tr>
        </thead>
        <tbody id="rows-container">
            @isset($data)
                @foreach($data as $entry)
                <tr class="uvf-line-row">
                    <td>
                        @if ($isAmountEditType)
                        <input type="hidden" name="account_id[]" value="{{ $entry->account_id ?? '' }}" />
                        {!! Form::select('account_id_display[]', $accounts, $entry->account_id ?? null, ['class' => 'form-control form-select select2 rpf-input', 'disabled' => true]) !!}
                        @else
                        {!! Form::select('account_id[]', $accounts, $entry->account_id ?? null, [
                            'class' => 'form-control form-select select2 rpf-input',
                            'required' => true,
                        ]) !!}
                        @endif
                    </td>
                    <td>
                        <textarea name="narration[]" class="form-control rpf-input" rows="1" placeholder="Narration">{{ $entry->narration }}</textarea>
                    </td>
                    <td>
                        <input type="number" step="any" name="dr_amount[]" value="{{ $entry->debit }}" class="form-control rpf-input dr_amount text-end" onchange="getTotal();" placeholder="0.00">
                    </td>
                    <td>
                        <input type="number" step="any" name="cr_amount[]" value="{{ $entry->credit }}" class="form-control rpf-input cr_amount text-end" onchange="getTotal();" placeholder="0.00">
                    </td>
                    @unless($isAmountEditType)
                    <td class="text-center">
                        <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                    </td>
                    @endunless
                </tr>
                @endforeach
            @else
                <tr class="uvf-line-row">
                    <td>
                        {!! Form::select('account_id[]', $accounts, null, [
                            'class' => 'form-control form-select select2 rpf-input',
                            'required' => true,
                        ]) !!}
                    </td>
                    <td>
                        <textarea name="narration[]" class="form-control rpf-input" rows="1" placeholder="Narration"></textarea>
                    </td>
                    <td>
                        <input type="number" step="any" name="dr_amount[]" class="form-control rpf-input dr_amount text-end" placeholder="0.00" onchange="getTotal();">
                    </td>
                    <td>
                        <input type="number" step="any" name="cr_amount[]" class="form-control rpf-input cr_amount text-end" placeholder="0.00" onchange="getTotal();">
                    </td>
                    <td class="text-center">
                        <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
            @endisset
        </tbody>
    </table>
</div>

@unless($isAmountEditType)
<button type="button" id="add-new-row" class="btn btn-outline-primary btn-sm mt-1 mb-2">
    <i class="fa fa-plus"></i> Add line
</button>
@endunless
