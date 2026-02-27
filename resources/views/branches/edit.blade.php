@php $branchRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.branches' : 'branches'; @endphp
<form id="formajax" action="{{ route($branchRoute . '.update', $branch) }}" method="POST">
    @csrf
    @method('PUT')
    @include('branches.fields')
</form>

