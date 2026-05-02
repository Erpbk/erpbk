@php
    $bikeCategories = $bikeCategories ?? \App\Models\BikeCategory::orderBy('display_order')->orderBy('id')->get();
    $fieldsByCategory = $fieldsByCategory ?? \App\Models\BikeCustomField::fieldsByCategoryForForm();
    $useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

<script src="{{ asset('js/modal_custom.js') }}"></script>

@if ($useDynamicFields)
    {{-- One card per category, stacked (same pattern as riders) --}}
    @foreach($fieldsByCategory as $group)
        <div class="card mb-4">
            <div class="card-header">
                <b>{{ $group->category->label }}</b>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($group->fields as $item)
                        @include('bikes._form_field', ['item' => $item])
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@else
    {{-- If there are no settings rows yet, fall back to default fixed input types --}}
    <div class="card border">
        <div class="card-header">
            <b>Bike Information</b>
        </div>
        <div class="card-body">
            <div class="row">
                @foreach(\App\Models\BikeCustomField::fixedFieldsSlugMap()['bike_info'] as $fieldKey)
                    @php
                        $spec = \App\Models\BikeCustomField::fixedFieldInputSpecs()[$fieldKey] ?? ['type' => 'text'];
                    @endphp
                    @include('bikes._form_field', ['item' => (object)['kind'=>'fixed','field_key'=>$fieldKey,'label'=>\App\Models\BikeCustomField::humanizeFieldKey($fieldKey),'spec'=>$spec]])
                @endforeach
            </div>
        </div>
    </div>
@endif

<script>
    // Bikes use this to hide/show cyclist-only fields.
    $(document).ready(function() {
        function toggleCyclistFields() {
            let selectedText = $("#vehicle_type option:selected").text().toLowerCase();

            if (selectedText === "cyclist") {
                $(".hide-if-cyclist").hide();
            } else {
                $(".hide-if-cyclist").show();
            }
        }

        // Run on page load
        toggleCyclistFields();

        // Run when vehicle type changes
        $("#vehicle_type").change(function() {
            toggleCyclistFields();
        });
    });
</script>