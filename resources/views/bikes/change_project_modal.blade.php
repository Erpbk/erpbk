<form action="{{ route('bikes.change_project', $bike->id) }}" method="post" id="formajax">
    @csrf
    <input type="hidden" name="bike_id" value="{{ $bike->id }}">
    <div class="row">
        <div class="col-md-12 form-group">
            <label for="change_project_customer_id">Project <span class="text-danger">*</span></label>
            {!! Form::select(
                'customer_id',
                $projects,
                $currentProjectId,
                ['class' => 'form-select select2', 'id' => 'change_project_customer_id', 'required' => true]
            ) !!}
            <small class="text-muted">Select the project this vehicle assignment should move to.</small>
        </div>
        <div class="col-md-12 form-group">
            <label for="change_project_date">Change Date <span class="text-danger">*</span></label>
            <input
                type="date"
                name="change_date"
                id="change_project_date"
                class="form-control"
                value="{{ old('change_date', $defaultChangeDate) }}"
                required
            >
        </div>
    </div>
    <div class="text-end mt-2">
        <button type="submit" class="btn btn-primary">Save</button>
    </div>
</form>

<script>
(function () {
    if (typeof window.initBikeFormSelect2 === 'function') {
        window.initBikeFormSelect2(document.getElementById('formajax'));
    }
})();
</script>
