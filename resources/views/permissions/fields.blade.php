<!-- Name Field -->
<div class="form-group col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 255]) !!}
    <small class="text-warning">Automatically Created: <span class="text-primary">view, create, edit, delete</span></small>
</div>

<!-- Extra Permissions -->
<h5 class="mt-4">Custom Permissions</h5>
<div class="row" id="extra-permissions-container">
@if(isset($customPermissions))
    @foreach($customPermissions as $index => $custom)
        <div class="col-md-4 mb-2">
            <div class="input-group">
                <input type="text" name="extra[]" class="form-control" value="{{ $custom }}" required>
                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.col-md-4').remove()">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        </div>
    @endforeach
@endif
</div>

<button type="button" class="btn btn-sm btn-success mt-2" id="add-permission">
    <i class="ti ti-plus"></i> Add Custom Permission
</button>
<script>
$(document).ready(function() {
    let counter = 0;
    document.getElementById('add-permission').addEventListener('click', function() {
        counter++;
        const container = document.getElementById('extra-permissions-container');
        const div = document.createElement('div');
        div.className = 'col-md-4 mb-2';
        div.id = 'perm-' + counter;
        div.innerHTML = `
            <div class="input-group">
                <input type="text" name="extra[]" class="form-control" placeholder="Permission name" required>
                <button type="button" class="btn btn-outline-danger" onclick="this.closest('.col-md-4').remove()">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        `;
        container.appendChild(div);
    });
});

</script>
