@php
  $riderCategories = $riderCategories ?? \App\Models\RiderCategory::orderBy('display_order')->orderBy('id')->get();
  $useDynamicFields = isset($fieldsByCategory) && count($fieldsByCategory) > 0;
@endphp

@if ($useDynamicFields)
  <ul class="nav nav-tabs nav-tabs-rider mb-3" id="riderCategoryTabs" role="tablist">
    @foreach($fieldsByCategory as $i => $group)
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $i === 0 ? 'active' : '' }}" id="tab-{{ $group->category->id }}-tab" data-bs-toggle="tab" data-bs-target="#rider-tab-{{ $group->category->id }}" type="button" role="tab">{{ $group->category->label }}</button>
      </li>
    @endforeach
  </ul>

  <div class="tab-content" id="riderCategoryTabContent">
    @foreach($fieldsByCategory as $i => $group)
      <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="rider-tab-{{ $group->category->id }}" role="tabpanel">
        <div class="card mb-4">
          <div class="card-body">
            <div class="row">
              @foreach($group->fields as $item)
                @include('riders._form_field', ['item' => $item])
              @endforeach
            </div>
          </div>
        </div>
      </div>
    @endforeach
  </div>
@else
  {{-- Fallback: slug-based category tabs (legacy) --}}
  <ul class="nav nav-tabs nav-tabs-rider mb-3" id="riderCategoryTabs" role="tablist">
    @foreach($riderCategories as $i => $cat)
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $i === 0 ? 'active' : '' }}" id="tab-{{ $cat->id }}-tab" data-bs-toggle="tab" data-bs-target="#rider-tab-{{ $cat->id }}" type="button" role="tab">{{ $cat->label }}</button>
      </li>
    @endforeach
  </ul>

  <div class="tab-content" id="riderCategoryTabContent">
    @foreach($riderCategories as $i => $cat)
      <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="rider-tab-{{ $cat->id }}" role="tabpanel">
        <div class="card mb-4">
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
          </div>
        </div>
      </div>
    @endforeach
  </div>
@endif
