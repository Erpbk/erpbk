<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Owner Type Field -->
<div class="form-group col-sm-6">
    {!! Form::label('owner_type', 'Owner Type:') !!}
    <select name="ref_name" id="owner_type" class="form-control select2" required>
        <option value="">Select Owner Type</option>
        <option value="customer" {{ old('ref_name', $items->ref_name ?? '') == 'customer' ? 'selected' : '' }}>Customer</option>
        <option value="leasingCompany" {{ old('ref_name', $items->ref_name ?? '') == 'leasingCompany' ? 'selected' : '' }}>Leasing Company</option>
        <option value="supplier" {{ old('ref_name', $items->ref_name ?? '') == 'supplier' ? 'selected' : '' }}>Supplier</option>
        <option value="garage" {{ old('ref_name', $items->ref_name ?? '') == 'garage' ? 'selected' : '' }}>Garage</option>
        <option value='' {{ old('ref_name', $items->ref_name ?? '') == null && isset($items)? 'selected' : '' }}>All</option>
    </select>
</div>

<!-- Owner Select Field (Dynamically populated) -->
<div class="form-group col-sm-6">
    {!! Form::label('owner_id', 'Owner:') !!}
    <select name="ref_id" id="owner_id" class="form-control select2" required>
        <option value="">All</option>
    </select>
</div>

<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<!-- Price Field -->
<div class="form-group col-sm-6">
    {!! Form::label('price', 'Price:') !!}
    {!! Form::number('price', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Cost Field -->
<div class="form-group col-sm-6">
    {!! Form::label('cost', 'Cost:') !!}
    {!! Form::number('cost', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Code Field -->
<div class="form-group col-sm-6">
    {!! Form::label('code', 'Code:') !!}
    {!! Form::text('code', null, ['class' => 'form-control']) !!}
</div>

<!-- Barcode Field -->
<div class="form-group col-sm-6">
    {!! Form::label('barcode', 'Barcode:') !!}
    {!! Form::text('barcode', null, ['class' => 'form-control']) !!}
</div>

<!-- Vat Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vat', 'VAT(%):') !!}
    {!! Form::number('vat', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2"/>
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" 
            @isset($items) @if($items->status == 1) checked @endif @else checked @endisset/>
        <label for="status" class="pt-0">Is Active</label>
    </div>
</div>

<!-- Detail Field -->
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    {!! Form::textarea('detail', null, ['class' => 'form-control','rows'=>3]) !!}
</div>

<!-- JavaScript for Dynamic Owner Selection -->
<script type="text/javascript">
$(document).ready(function() {
    // When owner type changes, load owners
    $('#owner_type').on('change', function() {
        var ownerType = $(this).val();
        var $ownerSelect = $('#owner_id');
        if(ownerType == '') {
          $ownerSelect.html('<option value="">All</option>').prop('disabled', true);
          return;
        }
        
        if (ownerType) {
            // Reset and disable owner select while loading
            $ownerSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            // Make AJAX request to get owners
            $.ajax({
                url: "{{ route('get-owners') }}",
                type: "GET",
                data: {
                    _token: "{{ csrf_token() }}",
                    owner_type: ownerType
                },
                dataType: "json",
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">Select Owner</option>';
                        $.each(response.data, function(key, owner) {
                            var name = owner.name || owner.company_name || owner.title || owner.full_name;
                            options += '<option value="' + owner.id + '">' + name + '</option>';
                        });
                        $ownerSelect.html(options).prop('disabled', false);
                    } else {
                        $ownerSelect.html('<option value="">No owners found</option>').prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error('Error loading owners:', xhr);
                    $ownerSelect.html('<option value="">Error loading owners. Please try again.</option>').prop('disabled', false);
                }
            });
        } else {
            $ownerSelect.html('<option value="">First select owner type</option>').prop('disabled', true);
        }
    });
    
    // Trigger change if owner_type is pre-selected (for edit form)
    if ($('#owner_type').val()) {
      $('#owner_type').trigger('change');
      
      // Wait for owners to load, then select the current owner
      setTimeout(function() {
          var currentOwnerId = "{{ isset($items) ? $items->ref_id : '' }}";
          if (currentOwnerId) {
              $('#owner_id').val(currentOwnerId).trigger('change');
          }
      }, 500);
    }
});
</script>