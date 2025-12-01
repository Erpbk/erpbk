<!-- Price Assignment Section -->
<div class="row pr-5 pl-5">
    <label>
        <h5>Assign Price</h5>
    </label>
    <span id="error_message_duplicate_id"></span>
    <div id="rows-container">
        @php
        $counter = 1;
        $sum = 1;
        @endphp
        @if(isset($riders['items']) && count($riders['items'])>0)
        @php $resultItems = $riders['items']; @endphp
        @foreach($resultItems as $rowItem)
        @php $sum = count($riders['items']); @endphp
        <div class="row mb-3 item-row">
            <div class="col-sm-4">
                <label>Select Items</label>
                <select name="items[id][]" class="form-select select2-items" required>
                    <option value="">Select Item</option>
                    @foreach(\App\Models\Items::all() as $item)
                    <option value="{{$item->id}}"
                        data-price="{{$item->price}}"
                        @if(isset($rowItem->item_id) && $rowItem->item_id == $item->id) selected @endif>
                        {{$item->name.' - '.$item->price}}
                    </option>
                    @endforeach
                </select>
                <span class="notification" style="font-size: 13px;color:red"></span>
            </div>
            <div class="col-sm-4">
                <label>Price</label>
                <input type="number" class="form-control item-price" step="any"
                    value="@if(isset($rowItem)){{$rowItem->price}}@endif"
                    name="items[price][]" placeholder="Items Price" required />
            </div>
            <div class="col-sm-2">
                <label>&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
            </div>
        </div>
        @php $counter++; @endphp
        @endforeach
        @else
        <div class="row mb-3 item-row">
            <div class="col-sm-4">
                <label>Select Items</label>
                <select name="items[id][]" class="form-select select2-items" required>
                    <option value="">Select Item</option>
                    @foreach(\App\Models\Items::all() as $item)
                    <option value="{{$item->id}}" data-price="{{$item->price}}">
                        {{$item->name.' - '.$item->price}}
                    </option>
                    @endforeach
                </select>
                <span class="notification" style="font-size: 13px;color:red"></span>
            </div>
            <div class="col-sm-4">
                <label>Price</label>
                <input type="number" class="form-control item-price" step="any"
                    name="items[price][]" placeholder="Items Price" required />
            </div>
            <div class="col-sm-2">
                <label>&nbsp;</label><br>
                <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
            </div>
        </div>
        @endif
    </div>
    <button type="button" class="btn btn-success btn-sm mt-3 mb-3 col-sm-2" id="add-new-row">
        <i class="fa fa-plus"></i> Add Row
    </button>
</div>

<script>
    $(document).ready(function() {
        // Initialize Select2 for existing and new dropdowns
        function initializeSelect2(element) {
            element.select2({
                width: '100%',
                dropdownParent: element.closest('.modal-content'),
                placeholder: 'Select Item'
            }).on('select2:select', function(e) {
                var price = $(e.params.data.element).data('price');
                $(this).closest('.item-row').find('.item-price').val(price);
            });
        }

        // Initialize Select2 for existing dropdowns
        initializeSelect2($('.select2-items'));

        // Add new row
        $('#add-new-row').click(function() {
            var firstRow = $('#rows-container .item-row:first');
            var newRow = firstRow.clone();

            // Reset values
            newRow.find('select').val('').removeClass('select2-hidden-accessible');
            newRow.find('.select2-container').remove();
            newRow.find('input[type="number"]').val('');
            newRow.find('.notification').text('');

            // Append and initialize
            $('#rows-container').append(newRow);
            initializeSelect2(newRow.find('.select2-items'));
        });

        // Remove row
        $(document).on('click', '.btn-remove-row', function() {
            if ($('#rows-container .item-row').length > 1) {
                $(this).closest('.item-row').remove();
            } else {
                alert('At least one item row is required.');
            }
        });

        // Form submission validation
        $('#formajax').on('submit', function(e) {
            var valid = true;
            var duplicates = {};

            // Check for empty values and duplicates
            $('.select2-items').each(function() {
                var itemId = $(this).val();
                if (!itemId) {
                    valid = false;
                    $(this).closest('.col-sm-4').find('.notification').text('Please select an item');
                } else {
                    if (duplicates[itemId]) {
                        valid = false;
                        $(this).closest('.col-sm-4').find('.notification').text('Duplicate item selected');
                    }
                    duplicates[itemId] = true;
                }
            });

            $('.item-price').each(function() {
                var price = $(this).val();
                if (!price || price <= 0) {
                    valid = false;
                    $(this).addClass('is-invalid');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!valid) {
                e.preventDefault();
                return false;
            }
        });
    });
</script>