@php
$chequeTopSliderCategories = $chequeTopSliderCategories ?? collect();
$chequeTopOptionStats = $chequeTopOptionStats ?? [];
@endphp
@if($chequeTopSliderCategories->sum(fn ($c) => $c->options->count()) > 0)
<div class="fleet-supervisor-section mb-3">
  <div class="fleet-supervisor-accordion expanded" id="chequeTopFleetAccordion">
    <div class="fleet-supervisor-slider-container">
      <div class="slider-controls">
        <button class="slider-btn prev-btn" id="chequeTopPrevBtn" type="button" aria-label="Previous">
          <i class="ti ti-chevron-left"></i>
        </button>
        <div class="slider-indicators" id="chequeTopSliderIndicators"></div>
        <button class="slider-btn next-btn" id="chequeTopNextBtn" type="button" aria-label="Next">
          <i class="ti ti-chevron-right"></i>
        </button>
      </div>
      <div class="fleet-supervisor-cards slider-track" id="sliderTrack">
        @php $slideIndex = 0; @endphp
        @foreach($chequeTopSliderCategories as $category)
        @foreach($category->options as $option)
        @php
        $optionStats = $chequeTopOptionStats[$option->id] ?? ['cleared' => 0, 'pending' => 0];
        $countCleared = (int) ($optionStats['cleared'] ?? 0);
        $countPending = (int) ($optionStats['pending'] ?? 0);
        $categoryColumn = $category->cheque_column ?? '';
        @endphp
        <div class="fleet-supervisor-card @if((int) request('cheque_top_option_id') === (int) $option->id) active filtered @endif" data-slide="{{ $slideIndex++ }}" onclick="filterByChequeTopOption('{{ $option->id }}')">
          <h3 class="fleet-supervisor-name">{{ $option->name }}</h3>
          <div class="small text-muted mb-1">{{ $category->name }}</div>
          <div class="fleet-supervisor-stats">
            <div class="fleet-stat active @if((int) request('cheque_top_option_id') === (int) $option->id && in_array('cleared', request('cheque_top_status', []))) active-selected @endif" onclick="event.stopPropagation(); filterByChequeTopOptionStatus('{{ $option->id }}', 'cleared')">
              <i class="fleet-stat-icon ti ti-circle-check"></i>
              <span class="fleet-stat-label">Cleared</span>
              <span class="fleet-stat-value">{{ $countCleared }}</span>
            </div>
            <div class="fleet-stat inactive @if((int) request('cheque_top_option_id') === (int) $option->id && in_array('pending', request('cheque_top_status', []))) active-selected @endif" onclick="event.stopPropagation(); filterByChequeTopOptionStatus('{{ $option->id }}', 'pending')">
              <i class="fleet-stat-icon ti ti-clock"></i>
              <span class="fleet-stat-label">Pending</span>
              <span class="fleet-stat-value">{{ $countPending }}</span>
            </div>
          </div>
        </div>
        @endforeach
        @endforeach
      </div>
    </div>
  </div>
</div>
@endif
