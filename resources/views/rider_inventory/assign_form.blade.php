<form id="formajax" class="form-ajax-submit" action="{{ route('RiderInventory.assignStore') }}" method="POST" data-reload-table="0">
    @csrf
    <input type="hidden" name="reload_page" id="reload_page" value="1">

    <div class="row">
        @if($showRiderSelect)
        <div class="col-md-4 form-group mb-3">
            <label for="rider_id" class="required">Rider</label>
            <select name="rider_id" id="rider_id" class="form-select form-select-sm select2" required>
                <option value="">Select Rider</option>
                @foreach($allRiders as $r)
                <option value="{{ $r->id }}" {{ (string) old('rider_id') === (string) $r->id ? 'selected' : '' }}>
                    {{ $r->rider_id }} — {{ $r->name }}
                </option>
                @endforeach
            </select>
        </div>
        @else
        <input type="hidden" name="rider_id" value="{{ $rider->id }}">
        <div class="col-md-4 form-group mb-3">
            <label>Rider</label>
            <input type="text" class="form-control" value="{{ $rider->name }} ({{ $rider->rider_id }})" readonly>
        </div>
        @endif

        <div class="col-md-4 form-group mb-3">
            <label for="customer_id" class="required">Customer</label>
            <select name="customer_id" id="customer_id" class="form-select form-select-sm select2" required>
                <option value="">Select Customer</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>
                    {{ $customer->name }}{{ $customer->company_name ? ' — ' . $customer->company_name : '' }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-4 form-group mb-3">
            <label for="assigned_date" class="required">Assigned Date</label>
            <input type="date" name="assigned_date" id="assigned_date" class="form-control"
                value="{{ old('assigned_date', date('Y-m-d')) }}" required>
        </div>
    </div>

    <div class="card-header bg-blue mt-2 px-2 py-2 rounded-top">
        <b class="card-title mb-0">Item Details</b>
    </div>
    <div class="scrollbar p-2 border border-top-0 rounded-bottom">
        <div class="row">
            <div class="col-md-5 form-group">
                <label>Item</label>
            </div>
            <div class="col-md-1 form-group">
                <label>Qty</label>
            </div>
            <div class="col-md-2 form-group">
                <label>Price</label>
            </div>
            <div class="col-md-3 form-group">
                <label>Total Amount</label>
            </div>
            <div class="col-md-1 form-group"></div>
        </div>
        <div id="rows-container">
            <div class="row mt-2 assign-item-row">
                <div class="col-md-5 form-group">
                    <select name="item_id[]" class="form-control select2 item">
                        <option value="">Select</option>
                        @foreach($availableItems as $item)
                        <option value="{{ $item->id }}" data-price="{{ $item->price }}">
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 form-group">
                    <input type="text" class="form-control qty" name="qty[]" placeholder="1" value="1">
                </div>
                <div class="col-md-2 form-group">
                    <input type="text" class="form-control rate" name="rate[]" placeholder="0.00" value="0">
                </div>
                <div class="col-md-3 form-group">
                    <input type="text" class="form-control amount" readonly name="line_total[]" placeholder="0.00" value="0.00">
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger btn-remove-row"><i class="fa fa-trash"></i></a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-12 form-group mt-2">
        <button type="button" id="add-new-row" class="btn btn-success btn-sm">Add New</button>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary save_rec">Assign Items</button>
        <span class="loader" style="display:none;"><i class="fa fa-spinner fa-spin"></i></span>
    </div>
</form>

<script>
$(document).ready(function () {
    $('.select2').select2({
        allowClear: true,
        dropdownParent: $('#modalTopbody'),
        width: '100%',
    });

    $('#rows-container .row').each(function () {
        if (typeof setItemTotal === 'function') {
            setItemTotal($(this));
        }
    });
});
</script>
