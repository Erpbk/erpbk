@php
    $bikeCategories = $bikeCategories ?? \App\Models\BikeCategory::orderBy('display_order')->orderBy('id')->get();
    $fieldsByCategory = $fieldsByCategory ?? \App\Models\BikeCustomField::fieldsByCategoryForForm();
    $hiddenFieldKeys = [
        'company_id',
        'current_km',
        'maintanence_km',
        'maintenance_km',
        'previous_km',
        'customer_id',
        'emirates',
        'rider_id',
    ];
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
                    @php
                        $isNoteOrDetailField = function ($item) {
                            $key = strtolower((string) ($item->field_key ?? ''));
                            $label = strtolower((string) ($item->kind === 'fixed' ? ($item->label ?? '') : ($item->field->label ?? '')));
                            return str_contains($key, 'note')
                                || str_contains($key, 'detail')
                                || str_contains($label, 'note')
                                || str_contains($label, 'detail');
                        };

                        $visibleGroupFields = collect($group->fields)->filter(function ($item) use ($hiddenFieldKeys) {
                            if (($item->kind ?? '') !== 'fixed') {
                                return true;
                            }
                            return !in_array((string) ($item->field_key ?? ''), $hiddenFieldKeys, true);
                        });
                        $regularFields = $visibleGroupFields->filter(fn ($item) => !$isNoteOrDetailField($item));
                        $noteFields = $visibleGroupFields->filter(fn ($item) => $isNoteOrDetailField($item));
                    @endphp

                    @foreach($regularFields as $item)
                        @include('bikes._form_field', ['item' => $item, 'fullWidth' => false])
                    @endforeach

                    @foreach($noteFields as $item)
                        @include('bikes._form_field', ['item' => $item, 'fullWidth' => true])
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
                    @continue(in_array($fieldKey, $hiddenFieldKeys, true))
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
        function initBikeFormSelect2(scope) {
            if (!window.jQuery || !$.fn || !$.fn.select2) {
                return;
            }

            var $scope = scope ? $(scope) : $(document);
            $scope.find('select.select2').each(function() {
                var $el = $(this);
                var $modalParent = $el.closest('.modal, .offcanvas');
                var options = {
                    width: '100%',
                };

                if ($modalParent.length) {
                    options.dropdownParent = $modalParent;
                } else {
                    var $formParent = $el.closest('#formajax');
                    if ($formParent.length) {
                        options.dropdownParent = $formParent;
                    }
                }

                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $el.select2(options);
            });
        }

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
        initBikeFormSelect2(document);

        // Run when vehicle type changes
        $("#vehicle_type").change(function() {
            toggleCyclistFields();
        });

        // Ensure Select2 works when bike forms are loaded inside modals via AJAX.
        $(document).on('shown.bs.modal shown.bs.offcanvas', function(e) {
            initBikeFormSelect2(e.target);
        });
    });
</script>