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
