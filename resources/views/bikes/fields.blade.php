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
'bike_owner',
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
            if ($root.is('[data-bike-assign-modal]')) {
                return;
            }

            $root.find('select').each(function() {
                var $el = $(this);
                if ($el.prop('disabled') || $el.closest('.hidden-field').length) {
                    return;
                }
                $el.addClass('select2');

                var $modalParent = $el.closest('.modal, .offcanvas');
                var emptyOptionText = $.trim($el.find('option[value=""]').first().text());
                var options = {
                    width: '100%',
                    allowClear: true,
                    placeholder: emptyOptionText || $el.data('placeholder') || 'Select'
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

        function bootBikeForm(scope) {
            toggleCyclistFields();
            bindCyclistToggle();
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