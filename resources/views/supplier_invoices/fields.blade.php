<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="row">
  <div class="col-md-2 form-group">
    <label>Invoice Date</label>
    <input type="date" class="form-control" value="{{ date('Y-m-d') }}" name="inv_date" placeholder="Invoice Date">
  </div>

  <div class="col-md-4 form-group">
    <label>Supplier</label>
    {!! Form::select('supplier_id', $supplier, null, ['class' => 'form-select form-select-sm select2','id'=>'item_id']) !!}
  </div>

  <div class="form-group col-md-2">
    <label>Billing Month</label>
    <input type="month" name="billing_month" class="form-control" value="@isset($invoice->billing_month){{ date('Y-m', strtotime($invoice->billing_month)) }}@endisset" id="billing_month" />
  </div>

  <div class="col-md-6 form-group">
    <label>Descriptions</label>
    {!! Form::textarea('descriptions', null, ['class' => 'form-control','placeholder'=>'Descriptions','rows'=>2]) !!}
  </div>

  <div class="col-md-6 form-group">
    <label>Notes</label>
    {!! Form::textarea('notes', null, ['class' => 'form-control','placeholder'=>'Notes','rows'=>2]) !!}
  </div>
</div>

<div class="">
  <div class="card-header bg-blue mt-3">
    <b class="card-title">Item Details</b>
  </div>

  <div id="rows-container">
    @isset($invoice)
    @foreach($invoice->items as $item)
    <div class="row mb-2 invoice-item-row">
      <div class="col-md-3 form-group">
        <label>Item</label>
        {!! Form::select('items['.$loop->index.'][item_id]', $items, $item->item_id, ['class' => 'form-select form-select-sm select2', 'onchange'=>'rider_price(this);']) !!}
      </div>

      <div class="col-md-1 form-group">
        <label>Qty</label>
        <input type="number" name="items[{{ $loop->index }}][qty]" value="{{ $item->qty }}" class="form-control qty" min="1" step="any" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-1 form-group">
        <label>Rate</label>
        <input type="number" name="items[{{ $loop->index }}][rate]" value="{{ $item->rate }}" class="form-control rate" step="any" onkeyup="calculate_price(this);" placeholder="AED">
      </div>

      <div class="col-md-1 form-group">
        <label>Discount</label>
        <input type="number" name="items[{{ $loop->index }}][discount]" value="{{ $item->discount }}" class="form-control discount" step="any" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-1 form-group">
        <label>Tax</label>
        <input type="number" name="items[{{ $loop->index }}][tax]" value="{{ $item->tax }}" class="form-control tax" step="any" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-2 form-group">
        <label>Amount</label>
        <input type="text" name="items[{{ $loop->index }}][amount]" value="AED {{ number_format($item->amount, 2) }}" class="form-control amount" readonly>
      </div>

      <div class="form-group col-md-1 d-flex align-items-end">
        <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
      </div>
    </div>
    @endforeach
    @endisset


    <div class="row">
      <div class="col-md-3 form-group">
        <label>Item Description</label>
        {!! Form::select('item_id[]', $items, null, ['class' => 'form-select form-select-sm select2','onchange'=>'supplier_price(this);']) !!}
      </div>

      <div class="col-md-1 form-group">
        <label>Qty</label>
        <input type="text" class="form-control qty" name="qty[]" value="1" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-2 form-group">
        <label>Rate</label>
        <input type="text" class="form-control rate" name="rate[]" value="0" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-2 form-group">
        <label>Discount</label>
        <input type="text" class="form-control discount" name="discount[]" value="0" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-1 form-group">
        <label>Tax</label>
        <input type="text" class="form-control tax" name="tax[]" value="0" onkeyup="calculate_price(this);">
      </div>

      <div class="col-md-2 form-group">
        <label>Amount</label>
        <input type="text" class="form-control amount" name="amount[]" value="0" readonly>
      </div>

      <div class="form-group col-md-1 d-flex align-items-end">
        <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
      </div>
    </div>
  </div>

  <div class="append-line"></div>
  <div class="col-md-1 form-group">
    <label style="visibility: hidden">Assign Price</label>
    {{-- <button type="button" class="btn btn-sm btn-primary new_line_item"><i class="fa fa-plus"></i> </button>
 --}} <button type="button" id="add-new-row" class="btn btn-success btn-sm mt-3 mb-3">Add New</button>

  </div>
  <div class="row" style="justify-content: flex-end;">
    <div class="col-md-2 form-group">
      <label><strong>Sub Total</strong>:</label>
    </div>
    <div class="col-md-2 form-group">

      <input type="text" name="total_amount" class="form-control" id="sub_total" value="@isset($invoice->total_amount)AED {{ number_format($invoice->total_amount, 2) }}@else AED 0.00 @endisset" readonly>
    </div>
  </div>
</div>
<script>
  function supplier_price(selectElement) {
    const row = selectElement.closest('.row');
    const itemId = selectElement.value;
    const rateInput = row.querySelector('.rate');

    if (itemPrices[itemId]) {
      rateInput.value = itemPrices[itemId];
    } else {
      rateInput.value = 0;
    }

    calculate_price(rateInput); // If you have calculation logic
  }
</script>
<script>
  const itemPrices = @json($itemsWithPrices); // item ID => price
</script>