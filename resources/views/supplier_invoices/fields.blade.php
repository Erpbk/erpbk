
            <div class="row">
                <input type="hidden" name="type" value="{{ $type }}">
                {{-- Company Information (Read-only) --}}
                <div class="form-group col-md-3">
                    {!! Form::label('company_info', 'Supplier') !!}
                    <select class="form-control select2" id="customer_id" name="supplier_id" required>
                        <option value="" selected>Select</option>
                        @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ isset($invoice) ? $invoice->supplier_id == $supplier->id ? 'selected' : '' : '' }} {{ isset($supplier_id) ? $supplier_id == $supplier->id ? 'selected' : '' : '' }}>
                            {{ $supplier->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @if($type=='invoice')
                    {{-- Invoice Date --}}
                    <div class="form-group col-md-2">
                        {!! Form::label('inv_date', 'Invoice Date') !!}
                        {!! Form::date('inv_date', isset($invoice)? $invoice->inv_date?->format('Y-m-d') ?? null :null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                @else
                    {{-- Order Date --}}
                    <div class="form-group col-md-2">
                        {!! Form::label('inv_date', 'Order Date') !!}
                        {!! Form::date('order_date', isset($invoice)? $invoice->order_date?->format('Y-m-d') ?? '' :null, ['class' => 'form-control', 'required' => true]) !!}
                    </div>
                @endif
                @if($type=='invoice')
                {{-- Billing Month --}}
                <div class="form-group col-md-2">
                    {!! Form::label('billing_month', 'Billing Month') !!}
                    {!! Form::month('billing_month', isset($invoice)? $invoice->billing_month?->format('Y-m') ?? null :null, ['class' => 'form-control', 'required' => true]) !!}
                </div>
                {{-- Attachment --}}
                <div class="form-group col-md-3">
                    {!! Form::label('attachment', 'Attachment') !!}
                    {!! Form::file('attachment', [
                        'class' => 'form-control',
                        'accept' => '.pdf,.jpg,.jpeg,.png,.doc,.docx'
                    ]) !!}
                    <small class="text-muted">Max: 5MB</small>
                </div>
                @endif
            </div>
            
            <div class="row">
                {{-- Description --}}
                <div class="form-group col-md-6">
                    {!! Form::label('descriptions', 'Description') !!}
                    {!! Form::textarea('descriptions', null, [
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'Enter invoice description...'
                    ]) !!}
                </div>

                {{-- Notes --}}
                <div class="form-group col-md-6">
                    {!! Form::label('notes', 'Notes') !!}
                    {!! Form::textarea('notes', null, [
                        'class' => 'form-control',
                        'rows' => 3,
                        'placeholder' => 'Additional notes or payment instructions...'
                    ]) !!}
                </div>
            </div>
            @php
                $items = \App\Models\Items::where('status', 1)->get();
            @endphp
            {{-- Invoice Items Section --}}
            <h5 class="my-3">Invoice Items</h5>
            <div class="scrollbar p-2 border rounded">
                <div id="row-container">
                    @if(isset($invoice))
                        @foreach($invoice->items as $index =>  $itm)
                            @if($index == 0)
                            <div class="row mb-2 item-row">
                            <div class="form-group col-md-3">
                                {!! Form::label('item', 'Item') !!}
                                <select name="item_ids[]" class="form-control select2 item-select" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}" 
                                            data-price="{{ $item->price ?? 0 }}"
                                            data-vat="{{ $item->vat ?? 0 }}"
                                            data-name="{{ $item->name }}"
                                            {{ $itm->item_id == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-1">
                                {!! Form::label('quantity', 'Qty') !!}
                                {!! Form::number('item_qty[]', $itm->qty, [
                                    'class' => 'form-control quantity',
                                    'step' => 'any',
                                    'min' => '0.01',
                                    'required' => true
                                ]) !!}
                            </div>
                            <div class="form-group col-md-2">
                                {!! Form::label('rate', 'Rate') !!}
                                {!! Form::number('item_rate[]', $itm->rate, [
                                    'class' => 'form-control rate',
                                    'step' => 'any',
                                    'min' => '0',
                                    'required' => true
                                ]) !!}
                            </div>
                            <div class="form-group col-md-1">
                                {!! Form::label('vat', 'VAT (%)') !!}
                                {!! Form::number('item_vat[]', $itm->tax, [
                                    'class' => 'form-control vat',
                                    'step' => 'any',
                                    'min' => '0',
                                    'max' => '100'
                                ]) !!}
                            </div>
                            <div class="form-group col-md-2">
                                {!! Form::label('vat', 'VAT Amount') !!}
                                {!! Form::number('item_vatAmount[]', $itm->tax_amount, [
                                    'class' => 'form-control vatAmount',
                                    'step' => 'any',
                                    'min' => '0',
                                'readonly' => true,
                                ]) !!}
                            </div>
                            <div class="form-group col-md-2">
                                {!! Form::label('total', 'Total Amount') !!}
                                {!! Form::number('items_total[]', $itm->total_amount, [
                                    'class' => 'form-control item-total',
                                    'step' => 'any',
                                    'readonly' => true
                                ]) !!}
                            </div>
                            <div class="form-group col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        @else
                        <div class="row mb-2 item-row">
                            <div class="form-group col-md-3">
                                <select name="item_ids[]" class="form-control select2 item-select" required>
                                    <option value="">Select Item</option>
                                    @foreach($items as $item)
                                    <option value="{{ $item->id }}" 
                                            data-price="{{ $item->price ?? 0 }}"
                                            data-vat="{{ $item->vat ?? 0 }}"
                                            data-name="{{ $item->name }}"
                                            {{ $itm->item_id === $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-1">
                                {!! Form::number('item_qty[]', $itm->qty, [
                                    'class' => 'form-control quantity',
                                    'step' => 'any',
                                    'min' => '0.01',
                                    'required' => true
                                ]) !!}
                            </div>
                            <div class="form-group col-md-2">
                                {!! Form::number('item_rate[]', $itm->rate, [
                                    'class' => 'form-control rate',
                                    'step' => 'any',
                                    'min' => '0',
                                    'required' => true
                                ]) !!}
                            </div>
                            <div class="form-group col-md-1">
                                {!! Form::number('item_vat[]', $itm->tax, [
                                    'class' => 'form-control vat',
                                    'step' => 'any',
                                    'min' => '0',
                                    'max' => '100'
                                ]) !!}
                            </div>
                            <div class="form-group col-md-2">
                                {!! Form::number('item_vatAmount[]', $itm->tax_amount, [
                                    'class' => 'form-control vatAmount',
                                    'step' => 'any',
                                    'min' => '0',
                                    'readonly' => true,
                                ]) !!}
                            </div>
                            <div class="form-group col-md-2">
                                {!! Form::number('items_total[]', $itm->total_amount, [
                                    'class' => 'form-control item-total',
                                    'step' => 'any',
                                    'readonly' => true
                                ]) !!}
                            </div>
                            <div class="form-group col-md-1 d-flex align-items-end">
                                <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        @endif
                        @endforeach
                    @else
                    <div class="row mb-2 item-row">
                        <div class="form-group col-md-3">
                            {!! Form::label('item', 'Item') !!}
                            <select name="item_ids[]" class="form-control select2 item-select" required>
                                <option value="">Select Item</option>
                                @foreach($items as $item)
                                <option value="{{ $item->id }}" 
                                        data-price="{{ $item->price ?? 0 }}"
                                        data-vat="{{ $item->vat ?? 0 }}"
                                        data-name="{{ $item->name }}">
                                    {{ $item->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-1">
                            {!! Form::label('quantity', 'Qty') !!}
                            {!! Form::number('item_qty[]', 1, [
                                'class' => 'form-control quantity',
                                'step' => 'any',
                                'min' => '0.01',
                                'required' => true
                            ]) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::label('rate', 'Rate') !!}
                            {!! Form::number('item_rate[]', 0, [
                                'class' => 'form-control rate',
                                'step' => 'any',
                                'min' => '0',
                                'required' => true
                            ]) !!}
                        </div>
                        <div class="form-group col-md-1">
                            {!! Form::label('vat', 'VAT (%)') !!}
                            {!! Form::number('item_vat[]', 0, [
                                'class' => 'form-control vat',
                                'step' => 'any',
                                'min' => '0',
                                'max' => '100'
                            ]) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::label('vat', 'VAT Amount') !!}
                            {!! Form::number('item_vatAmount[]', 0, [
                                'class' => 'form-control vatAmount',
                                'step' => 'any',
                                'min' => '0',
                                'readonly' => true,
                            ]) !!}
                        </div>
                        <div class="form-group col-md-2">
                            {!! Form::label('total', 'Total Amount') !!}
                            {!! Form::number('items_total[]', null, [
                                'class' => 'form-control item-total',
                                'step' => 'any',
                                'readonly' => true
                            ]) !!}
                        </div>
                        <div class="form-group col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm remove-row" style="display: none;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Buttons Section --}}
            <div class="d-flex justify-content-between align-items-center gap-3 mt-3">
                <div>
                    <button type="button" id="add-row" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light">Subtotal</span>
                        <input type="number" name="subtotal" class="form-control" id="subtotal" readonly style="min-width: 150px;">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light">VAT Amount</span>
                        <input type="number" name="vat_total" class="form-control" id="vat_total" readonly style="min-width: 150px;">
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-primary text-white">Total</span>
                        <input type="number" name="total" class="form-control" id="total_cost" readonly style="min-width: 150px; font-weight: bold;">
                    </div>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <div class="action-btn">
                {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
            </div>
        </div>

<script>
$(document).ready(function() {
    let rowIndex = 1;
    
    // Initialize select2
    $('.select2').select2({
        allowClear: true,
        dropdownParent: $('#modalTop')
    });
    
    // Calculate on load
    calculateAllTotals();
    
    // Add new row
    $('#add-row').click(function() {
        addNewRow();
    });
    
    // Remove row
    $(document).on('click', '.remove-row', function() {
        if ($('.item-row').length > 1) {
            $(this).closest('.item-row').remove();
            calculateAllTotals();
            updateRowIndices();
            
            // Hide remove buttons if only one row left
            if ($('.item-row').length === 1) {
                $('.remove-row').hide();
            }
        } else {
            alert('At least one item is required');
        }
    });
    
    // Calculate when any input changes
    $(document).on('input change', '.quantity, .rate, .vat', function() {
        const row = $(this).closest('.item-row');
        calculateRowTotal(row);
        calculateAllTotals();
    });
    
    // Auto-fill item details when item is selected
    $(document).on('change', '.item-select', function() {
        const row = $(this).closest('.item-row');
        const selectedOption = $(this).find('option:selected');
        const itemPrice = parseFloat(selectedOption.data('price')) || 0;
        const itemVat = parseFloat(selectedOption.data('vat')) || 0;
        
        if (selectedOption.val()) {
            row.find('.rate').val(itemPrice.toFixed(2));
            row.find('.vat').val(itemVat);
            calculateRowTotal(row);
            calculateAllTotals();
        } else {
            row.find('.rate').val(0);
            row.find('.vat').val(0);
            calculateRowTotal(row);
            calculateAllTotals();
        }
    });
    
    function addNewRow() {
        const newRow = $(`
            <div class="row mb-2 item-row">
                <div class="form-group col-md-3">
                    <select name="item_ids[]" class="form-control select2 item-select" required>
                        <option value="">Select Item</option>
                        @foreach(\App\Models\Items::where('status', 1)->get() as $item)
                        <option value="{{ $item->id }}" 
                                data-price="{{ $item->price ?? 0 }}"
                                data-vat="{{ $item->vat ?? 0 }}"
                                data-name="{{ $item->name }}">
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-1">
                    <input type="number" name="item_qty[]" class="form-control quantity" value="1" step="any" min="0.01" required>
                </div>
                <div class="form-group col-md-2">
                    <input type="number" name="item_rate[]" class="form-control rate" value="0" step="any" min="0" required>
                </div>
                <div class="form-group col-md-1">
                    <input type="number" name="item_vat[]" class="form-control vat" value="0" step="any" min="0" max="100">
                </div>
                <div class="form-group col-md-2">
                    <input type="number" name="item_vatAmount[]" class="form-control vatAmount" value="0" step="any" min="0" readonly>
                </div>
                <div class="form-group col-md-2">
                    <input type="number" name="items_total[]" class="form-control item-total" step="any" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <button type="button" class="btn btn-danger btn-sm remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `);
        
        $('#row-container').append(newRow);
        
        // Initialize select2 for new row
        newRow.find('.select2').select2({
            dropdownParent: $('#modalTop'),
            allowClear: true
        });
        
        rowIndex++;
        
        // Show remove buttons for all rows
        $('.remove-row').show();
    }
    
    function calculateRowTotal(row) {
        const quantity = parseFloat(row.find('.quantity').val()) || 0;
        const rate = parseFloat(row.find('.rate').val()) || 0;
        const vatPercent = parseFloat(row.find('.vat').val()) || 0;
        
        const subtotal = quantity * rate;
        const vatAmount = subtotal * (vatPercent / 100);
        const total = subtotal + vatAmount;
        
        row.find('.item-total').val(total.toFixed(2));
        row.find('.vatAmount').val(vatAmount.toFixed(2));
        
        return {
            subtotal: subtotal,
            vatAmount: vatAmount,
            total: total
        };
    }
    
    function calculateAllTotals() {
        let grandSubtotal = 0;
        let grandVat = 0;
        let grandTotal = 0;
        
        $('.item-row').each(function() {
            const rowTotals = calculateRowTotal($(this));
            
            grandSubtotal += rowTotals.subtotal;
            grandVat += rowTotals.vatAmount;
            grandTotal += rowTotals.total;
        });
        
        $('#subtotal').val(grandSubtotal.toFixed(2));
        $('#vat_total').val(grandVat.toFixed(2));
        $('#total_cost').val(grandTotal.toFixed(2));
    }
    
    function updateRowIndices() {
        // No need to update indices since we're using arrays with the same name
        // The backend will handle array processing regardless of indices
        $('.item-row').each(function(index) {
            // Just ensure all inputs have the same name pattern
            $(this).find('select[name="item_ids[]"], input[name="item_qty[]"], input[name="item_rate[]"], input[name="item_vat[]"], input[name="items_total[]"]');
        });
    }
    
    // Validate form before submission
    $('#formajax').on('submit', function(e) {
        let isValid = true;
        
        // Check if at least one item exists
        if ($('.item-row').length === 0) {
            alert('Please add at least one item to the invoice.');
            e.preventDefault();
            return false;
        }
        
        // Check customer selection
        if (!$('#customer_id').val()) {
            alert('Please select a Supplier.');
            e.preventDefault();
            return false;
        }
        
        // Check each item for required fields
        $('.item-row').each(function(index) {
            const itemSelect = $(this).find('.item-select').val();
            const quantity = $(this).find('.quantity').val();
            const rate = $(this).find('.rate').val();
            
            if (!itemSelect) {
                alert(`Item ${index + 1}: Please select an item.`);
                isValid = false;
                return false;
            }
            
            if (!quantity || parseFloat(quantity) <= 0) {
                alert(`Item ${index + 1}: Please enter a valid quantity.`);
                isValid = false;
                return false;
            }
            
            if (!rate || parseFloat(rate) <= 0) {
                alert(`Item ${index + 1}: Please enter a valid rate.`);
                isValid = false;
                return false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        return true;
    });
});
</script>