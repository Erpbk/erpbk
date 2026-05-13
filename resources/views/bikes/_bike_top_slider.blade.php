@php
$bikeTopSliderCategories = $bikeTopSliderCategories ?? collect();
$hasBikeTopOptionColumn = \Illuminate\Support\Facades\Schema::hasColumn('bikes', 'bike_top_option_id');
@endphp
<div class="fleet-supervisor-section mb-3">
  <div class="fleet-supervisor-accordion expanded" id="bikeTopFleetAccordion">
    <div class="fleet-supervisor-slider-container">
      <div class="slider-controls">
        <button class="slider-btn prev-btn" type="button" aria-label="Previous">
          <i class="ti ti-chevron-left"></i>
        </button>
        <div class="slider-indicators"></div>
        <button class="slider-btn next-btn" type="button" aria-label="Next">
          <i class="ti ti-chevron-right"></i>
        </button>
      </div>
      <div class="fleet-supervisor-cards slider-track" id="bikeTopSliderTrack">
        @php $slideIndex = 0; @endphp
        @foreach($bikeTopSliderCategories as $category)
        @foreach($category->options as $option)
        @php
        $countActive = 0;
        $countInactive = 0;
        if ($hasBikeTopOptionColumn) {
          $totalForOption = \App\Models\Bikes::where('bike_top_option_id', $option->id)->count();
          $countActive = \App\Models\Bikes::where('bike_top_option_id', $option->id)
            ->where('warehouse', 'Active')
            ->where('status', 1)
            ->count();
          $countInactive = $totalForOption - $countActive;
        }
        $optionCardTitle = \App\Models\BikeCustomField::displayLabelForFixedFieldValue($category->bike_column ?? null, (string) $option->name);
        @endphp
        <div class="fleet-supervisor-card @if((int) request('bike_top_option_id') === (int) $option->id) active filtered @endif" data-slide="{{ $slideIndex++ }}" onclick="filterByBikeTopOption('{{ $option->id }}')">
          <h3 class="fleet-supervisor-name">{{ $optionCardTitle }}</h3>
          <div class="small text-muted mb-1">{{ $category->name }}</div>
          <div class="fleet-supervisor-stats">
            <div class="fleet-stat active @if((int) request('bike_top_option_id') === (int) $option->id && request('bike_top_wh') === 'active') active-selected @endif" onclick="event.stopPropagation(); filterByBikeTopOptionWh('{{ $option->id }}', 'active')">
              <i class="fleet-stat-icon ti ti-user-check"></i>
              <span class="fleet-stat-label">Active</span>
              <span class="fleet-stat-value">{{ $countActive }}</span>
            </div>
            <div class="fleet-stat inactive @if((int) request('bike_top_option_id') === (int) $option->id && request('bike_top_wh') === 'inactive') active-selected @endif" onclick="event.stopPropagation(); filterByBikeTopOptionWh('{{ $option->id }}', 'inactive')">
              <i class="fleet-stat-icon ti ti-user-x"></i>
              <span class="fleet-stat-label">Inactive</span>
              <span class="fleet-stat-value">{{ $countInactive }}</span>
            </div>
          </div>
        </div>
        @endforeach
        @endforeach
      </div>
    </div>
  </div>
</div>