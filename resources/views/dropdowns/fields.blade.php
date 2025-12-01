<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<!-- Key Field -->
<div class="form-group col-sm-6">
  @isset($dropdowns)
  @else
  {!! Form::label('key', 'Key:') !!}
  {!! Form::text('key', null, ['class' => 'form-control', 'maxlength' => 200, 'maxlength' => 200]) !!}
  @endisset
</div>
<!-- Label Field -->
<div class="form-group col-sm-6">
    {!! Form::label('label', 'Label:') !!}
    {!! Form::text('label', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<div class="form-group col-sm-6">
</div>

<div class="form-group col-sm-6">
<div id="options-container">
  <label for="values" class="">Dropdown Options</label>
  @isset($dropdowns)
  @foreach(json_decode($dropdowns->values) as $value)
  <div class="input-group mb-2">
    <input type="text" name="values[]" value="{{$value}}" class="form-control" placeholder="New Option" required>
    <button type="button" class="btn btn-danger btn-remove-option"><i class="fa fa-trash"></i></button>
</div>
@endforeach
@endisset
  <div class="input-group mb-2">
      <input type="text" name="values[]" class="form-control" placeholder="New Option" required>
      <button type="button" class="btn btn-danger btn-remove-option"><i class="fa fa-trash"></i></button>
  </div>
</div>

<button type="button" id="add-option-btn" class="btn btn-success">Add Option</button>
</div>


<script>

  $(document).ready(function () {
    // Add new option row
    $('#add-option-btn').on('click', function () {
        let newOption = `
            <div class="input-group mb-2">
                <input type="text" name="values[]" class="form-control" placeholder="New Option" required>
                <button type="button" class="btn btn-danger btn-remove-option"><i class="fa fa-trash"></i></button>
            </div>`;
        $('#options-container').append(newOption);

        // Focus on the newly added input field
        $('#options-container .input-group:last-child input').focus();
    });

    // Remove an option row
    $(document).on('click', '.btn-remove-option', function () {
        // Ensure at least one option remains
        if ($('#options-container .input-group').length > 1) {
            $(this).closest('.input-group').remove();
        } else {
            alert('At least one option is required.');
        }
    });
});
</script>
