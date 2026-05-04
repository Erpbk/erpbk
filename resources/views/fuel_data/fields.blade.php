<div class="row">

    {{-- trans_no --}}
    <div class="col-md-6 mb-3">
        <label for="trans_no" class="form-label">Transaction Number <span class="text-danger">*</span></label>
        <input type="text"
            name="trans_no"
            id="trans_no"
            class="form-control @error('trans_no') is-invalid @enderror"
            value="{{ old('trans_no', $data?->trans_no ?? '') }}"
            required>
        @error('trans_no')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- trans_date --}}
    <div class="col-md-6 mb-3">
        <label for="trans_date" class="form-label">Transaction Date <span class="text-danger">*</span></label>
        <input type="datetime-local"
            name="trans_date"
            id="trans_date"
            class="form-control @error('trans_date') is-invalid @enderror"
            value="{{ old('trans_date', isset($data) ? $data->trans_date->format('Y-m-d H:i:s') : '') }}"
            required>
        @error('trans_date')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- auth_code --}}
    <div class="col-md-6 mb-3">
        <label for="auth_code" class="form-label">Authorization Code <span class="text-danger">*</span></label>
        <input type="text"
            name="auth_code"
            id="auth_code"
            class="form-control @error('auth_code') is-invalid @enderror"
            value="{{ old('auth_code', $data?->auth_code ?? '') }}"
            required>
        @error('auth_code')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- site --}}
    <div class="col-md-6 mb-3">
        <label for="site" class="form-label">Site/Fuel Station <span class="text-danger">*</span></label>
        <input type="text"
            name="site"
            id="site"
            class="form-control @error('site') is-invalid @enderror"
            value="{{ old('site', $data?->site ?? '') }}"
            required>
        @error('site')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- billing_month --}}
    <div class="col-md-4 mb-3">
        <label for="billing_month" class="form-label">Billing Month <span class="text-danger">*</span></label>
        <input type="month"
            name="billing_month"
            id="billing_month"
            class="form-control @error('billing_month') is-invalid @enderror"
            value="{{ old('billing_month', isset($data) ? $data->billing_month->format('Y-m') : '') }}"
            required>
        @error('billing_month')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- bike_no --}}
    <div class="col-md-4 mb-3">
        <label for="bike_no" class="form-label">Bike Number <span class="text-danger">*</span></label>
        <select name="bike_no"
            id="bike_no"
            class="form-select @error('bike_no') is-invalid @enderror select2">
            <option value="">Select Bike Number</option>
            @foreach(\App\Models\Bikes::where('status',1)->get() as $bike)
            <option value="{{ $bike->plate }}"
                {{ old('bike_no', $data?->bike_no ?? '') == $bike->plate ? 'selected' : '' }}>
                {{ $bike->plate }}
            </option>
            @endforeach
        </select>
        @error('bike_no')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- product --}}
    <div class="col-md-4 mb-3">
        <label for="product" class="form-label">Product/Fuel Type <span class="text-danger">*</span></label>
        <input type="text" name="product" id="product" class="form-control @error('product') is-invalid @enderror" required value={{ old('product' , $data?->product ?? '')}}>
        @error('product')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- card_no --}}
    <div class="col-md-4 mb-3">
        <label for="card_no" class="form-label">Card Number <span class="text-danger">*</span></label>
        <select name="card_no"
            id="card_no"
            class="form-select @error('card_no') is-invalid @enderror select2"
            required>
            <option value="">Select Card Number</option>
            @foreach(\App\Models\FuelCards::all() as $fuelCard)
            <option value="{{ $fuelCard->card_number }}"
                {{ old('card_no', $data?->card_no ?? '') == $fuelCard->card_number ? 'selected' : '' }}>
                {{ $fuelCard->card_number }}
            </option>
            @endforeach
        </select>
        @error('card_no')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- qty --}}
    <div class="col-md-4 mb-3">
        <label for="qty" class="form-label">Quantity (Liters) <span class="text-danger">*</span></label>
        <input type="number"
            name="qty"
            id="qty"
            step="0.01"
            class="form-control @error('qty') is-invalid @enderror"
            value="{{ old('qty', $data->qty ?? '') }}"
            required>
        @error('qty')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- price --}}
    <div class="col-md-4 mb-3">
        <label for="price" class="form-label">Price per Liter (aed) <span class="text-danger">*</span></label>
        <input type="number"
            name="price"
            id="price"
            step="0.01"
            class="form-control @error('price') is-invalid @enderror"
            value="{{ old('price', $data->price ?? '') }}"
            required>
        @error('price')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    {{-- vat_amount --}}
    <div class="col-md-4 mb-3">
        <label for="vat_amount" class="form-label">VAT Amount (PKR) <span class="text-danger">*</span></label>
        <input type="number"
            name="vat_amount"
            id="vat_amount"
            step="0.01"
            class="form-control @error('vat_amount') is-invalid @enderror"
            value="{{ old('vat_amount', $data->vat_amount ?? '') }}"
            required>
        @error('vat_amount')
        <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>

    <div class="col-md-4 mb-3">
        <label for="total_display" class="form-label">Service Charges</label>
        <input class="form-control" type="number" step="0.01" name="service_charges" id="service_charges" value="{{ old('service_charges', $data->service_charges ?? 25) }}">
    </div>

    <div class="col-md-4 mb-3"></div>
    <div class="col-md-4 mb-3"></div>

    {{-- Note: subtotal and total are auto-calculated, but shown as read-only fields --}}
    <div class="col-md-4 mb-3">
        <label for="subtotal_display" class="form-label">Subtotal (Auto-calculated)</label>
        <input type="text"
            id="subtotal_display"
            class="form-control bg-light"
            readonly
            value="AED {{ number_format((old('qty', $data?->qty ?? 0) * old('price', $data?->price ?? 0)), 2) }}">
        <input type="hidden" name="subtotal" id="subtotal" value="{{ old('subtotal', $data->subtotal ?? '') }}">
    </div>

    <div class="col-md-4 mb-3">
        <label for="total_display" class="form-label">Total (Auto-calculated)</label>
        <input type="text"
            id="total_display"
            class="form-control bg-light"
            readonly
            value="AED {{ number_format((old('qty', $data?->qty ?? 0) * old('price', $data?->price ?? 0) + old('vat_amount', $data?->vat_amount ?? 0)), 2) }}">
        <input type="hidden" name="total" id="total" value="{{ old('total', $data->total ?? '') }}">
    </div>
</div>

<script>
    // Auto-calculate subtotal and total with jQuery
    console.log('Initializing auto-calculation script');
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTop')
        });

        function calculateTotals() {
            let qty = parseFloat($('#qty').val()) || 0;
            let price = parseFloat($('#price').val()) || 0;
            let vat = parseFloat($('#vat_amount').val()) || 0;

            let subtotal = qty * price;
            let total = subtotal + vat;

            $('#subtotal_display').val('AED ' + subtotal.toFixed(2));
            $('#total_display').val('AED ' + total.toFixed(2));
            $('#subtotal').val(subtotal.toFixed(2));
            $('#total').val(total.toFixed(2));
        }

        // Attach event listeners
        $('#qty, #price, #vat_amount').on('input', calculateTotals);

        // Initial calculation
        calculateTotals();
    });
</script>