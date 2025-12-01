<!-- resources/views/suppliers/show.blade.php -->
@extends('suppliers.view')

@section('page_content')
<div class="card p-4 shadow-sm">
    <div class="row">
        <div class="col-md-6 form-group col-6">
            <label>Supplier Name:</label>
            <p>{{ $supplier->name }}</p>
        </div>
        <div class="col-md-6 form-group col-6">
            <label>Email:</label>
            <p>{{ $supplier->email }}</p>
        </div>
        <div class="col-md-6 form-group col-6">
            <label>Phone:</label>
            <p>{{ $supplier->phone }}</p>
        </div>
        <div class="col-md-6 form-group col-6">
            <label>Address:</label>
            <p>{{ $supplier->address }}</p>
        </div>
    </div>
    <div class="col-sm-6">
        <a href="{{ route('suppliers.index') }}" class="btn btn-primary float-right">Back</a>
    </div>
</div>
@endsection