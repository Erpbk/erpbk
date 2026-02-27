@php $branchRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.branches' : 'branches'; @endphp
<form id="formajax" action="{{ route($branchRoute . '.store') }}" method="POST">
    @csrf
    @include('branches.fields')
</form>

@section('page-script')
<script>
    $()
</script>
@endsection

