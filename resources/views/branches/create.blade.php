
<form id="formajax" action="{{ route('branches.store') }}" method="POST">
    @csrf
    @include('branches.fields')
    
</form>

@section('page-script')
<script>
    $()
</script>
@endsection

