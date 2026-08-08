@php
  $optionIdParam = (string) ($topBarConfig['request']['option_id'] ?? 'top_option_id');
  $statusParam = (string) ($topBarConfig['request']['status'] ?? '');
  $statKeys = array_keys(app(\App\Services\Module\TopBarListingService::class)->statDefinitions($topBarModuleKey));
  $defaultStatuses = $topBarConfig['listing_default_statuses'] ?? $statKeys;
@endphp
<script>
(function() {
  var cfg = {
    moduleKey: @json($topBarModuleKey),
    optionIdParam: @json($optionIdParam),
    statusParam: @json($statusParam),
    statusAsArray: @json($statusParam !== '' && !in_array($statusParam, ['bike_top_wh'], true)),
    trackId: @json($trackId),
    defaultStatuses: @json(array_values($defaultStatuses)),
  };

  function isStatusParamKey(key) {
    return key === cfg.statusParam || key.indexOf(cfg.statusParam + '[') === 0;
  }

  function clearStatusParams(url) {
    if (!cfg.statusParam) return;
    // Laravel may emit status, status[], or status[0]
    Array.from(url.searchParams.keys()).forEach(function(key) {
      if (isStatusParamKey(key)) {
        url.searchParams.delete(key);
      }
    });
  }

  function expandStatusKey(statusKey) {
    if (cfg.moduleKey === 'employees' && statusKey === 'inactive') {
      return ['inactive', 'on_leave'];
    }
    return [statusKey];
  }

  function setStatusParams(url, statuses) {
    if (!cfg.statusParam || !statuses.length) return;
    var expanded = [];
    statuses.forEach(function(s) {
      expandStatusKey(s).forEach(function(v) {
        if (expanded.indexOf(v) === -1) expanded.push(v);
      });
    });
    if (cfg.statusAsArray) {
      expanded.forEach(function(s) {
        url.searchParams.append(cfg.statusParam + '[]', s);
      });
    } else {
      url.searchParams.set(cfg.statusParam, expanded[0]);
    }
  }

  function getStatusValues(url) {
    if (!cfg.statusParam) return [];
    var values = [];
    url.searchParams.forEach(function(value, key) {
      if (!isStatusParamKey(key) || !value) return;
      if (values.indexOf(value) === -1) values.push(value);
    });
    return values;
  }

  function filterByTopOption(optionId) {
    var url = new URL(window.location.href);
    // Clicking the already-selected card clears the filter
    if (url.searchParams.get(cfg.optionIdParam) === String(optionId)) {
      url.searchParams.delete(cfg.optionIdParam);
      clearStatusParams(url);
      window.location.href = url.toString();
      return;
    }
    url.searchParams.delete(cfg.optionIdParam);
    clearStatusParams(url);
    url.searchParams.set(cfg.optionIdParam, String(optionId));
    setStatusParams(url, cfg.defaultStatuses);
    window.location.href = url.toString();
  }

  function filterByTopOptionStatus(optionId, statusKey) {
    var url = new URL(window.location.href);
    var currentOptionId = url.searchParams.get(cfg.optionIdParam);
    var currentStatuses = getStatusValues(url);
    var targetStatuses = expandStatusKey(statusKey);
    var isSelected = targetStatuses.every(function(s) { return currentStatuses.includes(s); });
    // Option selected but status params unreadable (e.g. odd array encoding) — treat as selected
    var optionOnlySelected = currentOptionId === String(optionId) && currentStatuses.length === 0;

    if (currentOptionId === String(optionId) && (isSelected || optionOnlySelected)) {
      if (optionOnlySelected) {
        url.searchParams.delete(cfg.optionIdParam);
        clearStatusParams(url);
      } else {
        var newStatuses = currentStatuses.filter(function(s) { return targetStatuses.indexOf(s) === -1; });
        clearStatusParams(url);
        setStatusParams(url, newStatuses);
        if (newStatuses.length === 0) {
          url.searchParams.delete(cfg.optionIdParam);
        }
      }
    } else {
      url.searchParams.set(cfg.optionIdParam, String(optionId));
      clearStatusParams(url);
      setStatusParams(url, [statusKey]);
    }
    window.location.href = url.toString();
  }

  function initErpTopBarSlider(trackEl) {
    if (!trackEl || trackEl.dataset.tickerInit === '1') return;

    var cards = Array.from(trackEl.querySelectorAll('.fleet-supervisor-card'));
    if (!cards.length) return;

    var container = trackEl.closest('.fleet-supervisor-slider-container');
    if (container) container.classList.add('ticker-mode');

    trackEl.dataset.tickerInit = '1';
    if (cards.length < 2) return;

    var isAnimating = false;
    var computedTrackStyle = window.getComputedStyle(trackEl);
    var gap = parseFloat(computedTrackStyle.columnGap || computedTrackStyle.gap || '16') || 16;

    function slideNextCard() {
      if (isAnimating) return;
      var firstCard = trackEl.querySelector('.fleet-supervisor-card');
      if (!firstCard) return;
      isAnimating = true;
      var shiftAmount = firstCard.offsetWidth + gap;
      trackEl.style.transition = 'transform 520ms ease';
      trackEl.style.transform = 'translateX(-' + shiftAmount + 'px)';
      window.setTimeout(function() {
        trackEl.style.transition = 'none';
        trackEl.style.transform = 'translateX(0)';
        trackEl.appendChild(firstCard);
        void trackEl.offsetWidth;
        isAnimating = false;
      }, 540);
    }

    var intervalId = window.setInterval(slideNextCard, 2600);
    trackEl.dataset.tickerIntervalId = String(intervalId);
  }

  document.addEventListener('DOMContentLoaded', function() {
    var track = document.getElementById(cfg.trackId);
    if (!track) return;

    track.querySelectorAll('.fleet-supervisor-card').forEach(function(card) {
      var optionId = card.getAttribute('data-option-id');
      card.addEventListener('click', function() {
        filterByTopOption(optionId);
      });
      card.querySelectorAll('.fleet-stat[data-stat-key]').forEach(function(stat) {
        stat.addEventListener('click', function(e) {
          e.stopPropagation();
          if (cfg.statusParam) {
            filterByTopOptionStatus(optionId, stat.getAttribute('data-stat-key'));
          } else {
            // Modules without status chips (e.g. Activities insights): the count
            // area is the main click target — still apply the option filter.
            filterByTopOption(optionId);
          }
        });
      });
    });

    setTimeout(function() { initErpTopBarSlider(track); }, 150);
  });

  window.filterByTopOption = filterByTopOption;
  window.filterByTopOptionStatus = filterByTopOptionStatus;
  window.initErpTopBarSlider = initErpTopBarSlider;
})();
</script>
