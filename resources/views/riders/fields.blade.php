@php
  $riderCategories = $riderCategories ?? \App\Models\RiderCategory::orderBy('display_order')->orderBy('id')->get();
  $fieldsByCategory = $fieldsByCategory ?? \App\Models\RiderCustomField::fieldsByCategoryForForm();
  $useDynamicFields = is_array($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

@if ($useDynamicFields)
  {{-- One card per category, stacked (no tabs) --}}
  @foreach($fieldsByCategory as $group)
    <div class="card mb-4">
      <div class="card-header">
        <b>{{ $group->category->label }}</b>
      </div>
      <div class="card-body">
        <div class="row">
          @foreach($group->fields as $item)
            @include('riders._form_field', ['item' => $item])
          @endforeach
        </div>
      </div>
    </div>
  @endforeach
@else
  {{-- Fallback: slug-based, one card per category (no tabs) --}}
  @foreach($riderCategories as $cat)
    @php
      $catCustomFields = \App\Models\RiderCustomField::where('category_id', $cat->id)->orderBy('display_order')->orderBy('id')->get();
    @endphp
    <div class="card mb-4">
      <div class="card-header">
        <b>{{ $cat->label }}</b>
      </div>
      <div class="card-body">
        @if($cat->slug === 'rider_info')
          @include('riders.fields.rider_info')
        @elseif($cat->slug === 'visa_info')
          @include('riders.fields.visa_info')
        @elseif($cat->slug === 'job_info')
          @include('riders.fields.job_info')
        @elseif($cat->slug === 'labor_info')
          @include('riders.fields.labor_info')
        @elseif($cat->slug === 'additional_info')
          @include('riders.fields.additional_info')
        @else
          @include('riders.fields.other')
        @endif
        @if($catCustomFields->isNotEmpty())
          <div class="row mt-3">
            @foreach($catCustomFields as $cf)
              @include('riders._form_field', ['item' => (object)['kind' => 'custom', 'field' => $cf]])
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endforeach
@endif

@once
  <style>
    .select2-results__option.select2-add-new-option {
      color: #ff6f00;
      font-weight: 600;
    }
  </style>

  <script>
    (function() {
      var openedSelectEl = null;

      function openAddOptionModalFromSelect(sel) {
        if (!sel) return;
        sel.value = '';
        sel.blur();
        var params = new URLSearchParams();
        var fieldKey = sel.getAttribute('data-field-key') || '';
        var customFieldId = sel.getAttribute('data-custom-field-id') || '';
        var label = sel.getAttribute('data-label') || 'Field';
        if (fieldKey) params.set('field_key', fieldKey);
        if (customFieldId) params.set('custom_field_id', customFieldId);
        params.set('label', label);

        var action = "{{ route('riders.dropdown-options.modal', ['company_slug' => request()->route('company_slug')]) }}" + "?" + params.toString();
        var trigger = document.createElement('a');
        trigger.href = 'javascript:void(0);';
        trigger.className = 'show-modal';
        trigger.setAttribute('data-action', action);
        trigger.setAttribute('data-size', 'md');
        trigger.setAttribute('data-title', 'Add New Option');
        document.body.appendChild(trigger);
        trigger.click();
        document.body.removeChild(trigger);
      }

      if (window.jQuery) {
        jQuery(document).on('select2:opening', '.js-dropdown-with-add-option', function() {
          openedSelectEl = this;
        });

        jQuery(document).on('select2:open', function() {
          if (!openedSelectEl || !jQuery(openedSelectEl).hasClass('js-dropdown-with-add-option')) return;

          window.setTimeout(function() {
            var $dropdown = jQuery('.select2-container--open .select2-results__options').last();
            if (!$dropdown.length || $dropdown.find('.select2-add-new-option').length) return;
            $dropdown.append('<li class="select2-results__option select2-add-new-option" role="option" aria-selected="false">+ Add New</li>');
          }, 0);
        });

        jQuery(document).on('mousedown', '.select2-add-new-option', function(evt) {
          evt.preventDefault();
          evt.stopPropagation();
          if (!openedSelectEl) return;
          openAddOptionModalFromSelect(openedSelectEl);
          jQuery(openedSelectEl).select2('close');
        });
      }
    })();
  </script>
@endonce
