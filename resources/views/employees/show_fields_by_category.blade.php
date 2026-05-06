@foreach($fieldsByCategory as $group)
<div class="col-12">
    <div class="card border mb-4">
        <div class="card-header" style="background-color: #d5d8db63;">
            <b>{{ $group->category->label }}</b>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach($group->fields as $item)
                @include('employees._show_field', ['item' => $item, 'employee' => $employee])
                @endforeach
            </div>
        </div>
    </div>
</div>
@endforeach