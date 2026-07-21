@php
$fieldsByCategory = $fieldsByCategory ?? \App\Models\BikeCustomField::fieldsByCategoryForForm();
$hiddenFieldKeys = [
'company_id',
'current_km',
'maintanence_km',
'maintenance_km',
'previous_km',
'customer_id',
'rider_id',
];
$useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

<script src="{{ asset('js/modal_custom.js') }}"></script>

@if ($useDynamicFields)
{{-- One card per category, stacked (same pattern as riders) --}}
@foreach($fieldsByCategory as $group)
@php
    $rfpGroupVisible = collect($group->fields)->contains(function ($item) use ($hiddenFieldKeys) {
        if (($item->kind ?? '') === 'fixed' && in_array((string) ($item->field_key ?? ''), $hiddenFieldKeys, true)) {
            return false;
        }
        $fn = ($item->kind ?? '') === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
        return field_visible('bike', (string) $fn);
    });
@endphp
@if($rfpGroupVisible)
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
@endif
@endforeach
@else
<div class="alert alert-warning mb-0">
    No fields assigned in Bike Settings. Configure categories and fields under Settings Panel → Bike Settings.
</div>
@endif


<script>
    (function() {
        function getBikeFormRoot(scope) {
            if (scope && scope.nodeType === 1) {
                if (scope.id === 'formajax') {
                    return scope;
                }
                var nested = scope.querySelector('#formajax');
                if (nested) {
                    return nested;
                }
                if (scope.querySelector && scope.querySelector('select')) {
                    return scope;
                }
            }
            return document.getElementById('formajax') || document;
        }

        function initBikeFormSelect2(scope) {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
                return;
            }

            var $ = window.jQuery;
            var $root = $(getBikeFormRoot(scope));
            if (!$root.length) {
                return;
            }

            $root.find('select').each(function() {
                var $el = $(this);
                $el.addClass('select2');

                var $modalParent = $el.closest('.modal, .offcanvas');
                var options = {
                    width: '100%',
                    allowClear: true
                };

                if ($modalParent.length) {
                    options.dropdownParent = $modalParent;
                } else if ($root.is('#formajax')) {
                    options.dropdownParent = $root;
                }

                if ($el.data('select2')) {
                    $el.select2('destroy');
                }
                $el.select2(options);
            });
        }

        function toggleCyclistFields() {
            var vehicleTypeEl = document.getElementById('vehicle_type');
            if (!vehicleTypeEl) return;

            var selectedOption = vehicleTypeEl.options[vehicleTypeEl.selectedIndex];
            var selectedText = (selectedOption ? selectedOption.text : '').toLowerCase();
            var cyclistOnlyHiddenEls = document.querySelectorAll('.hide-if-cyclist');

            cyclistOnlyHiddenEls.forEach(function(el) {
                el.style.display = selectedText === 'cyclist' ? 'none' : '';
            });
        }

        function bindCyclistToggle() {
            var vehicleTypeEl = document.getElementById('vehicle_type');
            if (!vehicleTypeEl || vehicleTypeEl.dataset.cyclistToggleBound === '1') {
                return;
            }
            vehicleTypeEl.dataset.cyclistToggleBound = '1';
            vehicleTypeEl.addEventListener('change', toggleCyclistFields);
        }

        function toggleLeasedFields() {
            var bikeOwnerEl = document.getElementById('bike_owner');
            if (!bikeOwnerEl) return;

            var isLeased = (bikeOwnerEl.value || '').toLowerCase() === 'leased';
            document.querySelectorAll('.show-if-leased').forEach(function(el) {
                el.style.display = isLeased ? '' : 'none';

                // Hidden required inputs block form submission, so lift the
                // attribute while hidden and restore it when shown again.
                el.querySelectorAll('input, select, textarea').forEach(function(input) {
                    if (isLeased) {
                        if (input.dataset.leasedRequired === '1') {
                            input.required = true;
                        }
                    } else {
                        if (input.required) {
                            input.dataset.leasedRequired = '1';
                        }
                        input.required = false;
                    }
                });
            });
        }

        function bindLeasedToggle() {
            var bikeOwnerEl = document.getElementById('bike_owner');
            if (!bikeOwnerEl || bikeOwnerEl.dataset.leasedToggleBound === '1') {
                return;
            }
            bikeOwnerEl.dataset.leasedToggleBound = '1';
            bikeOwnerEl.addEventListener('change', toggleLeasedFields);
            // select2 fires change through jQuery only, which native listeners miss.
            if (window.jQuery) {
                window.jQuery(bikeOwnerEl).on('change', toggleLeasedFields);
            }
        }

        function bootBikeForm(scope) {
            toggleCyclistFields();
            bindCyclistToggle();
            toggleLeasedFields();
            bindLeasedToggle();
            initBikeFormSelect2(scope);
        }

        window.initBikeFormSelect2 = initBikeFormSelect2;

        bootBikeForm();

        document.addEventListener('DOMContentLoaded', function() {
            bootBikeForm();
        });

        document.addEventListener('shown.bs.modal', function(e) {
            if (e.target && e.target.querySelector('#formajax')) {
                bootBikeForm(e.target);
            }
        });
        document.addEventListener('shown.bs.offcanvas', function(e) {
            if (e.target && e.target.querySelector('#formajax')) {
                bootBikeForm(e.target);
            }
        });
    })();
</script>