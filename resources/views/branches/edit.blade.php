
<form id="formajax" action="{{ route('branches.update', $branch) }}" method="POST">
    @csrf
    @method('PUT')
    @include('branches.fields')
    
</form>

