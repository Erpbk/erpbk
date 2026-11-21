<script>
(function () {
  function closeAllYearPanels(except) {
    document.querySelectorAll('.vat-year-panel').forEach(function (panel) {
      if (except && panel === except) return;
      panel.hidden = true;
    });
  }

  function renderYearGrid(wrap) {
    const input = wrap.querySelector('.vat-year-picker');
    const panel = wrap.querySelector('.vat-year-panel');
    const grid = wrap.querySelector('.vat-year-grid');
    const range = wrap.querySelector('.vat-year-range');
    if (!input || !panel || !grid || !range) return;

    let start = parseInt(wrap.dataset.decadeStart || '0', 10);
    if (!start) {
      const current = parseInt(input.value, 10) || new Date().getFullYear();
      start = current - ((current % 12 + 12) % 12);
      wrap.dataset.decadeStart = String(start);
    }

    const selected = parseInt(input.value, 10) || null;
    const thisYear = new Date().getFullYear();
    range.textContent = start + ' – ' + (start + 11);
    grid.innerHTML = '';

    for (let y = start; y < start + 12; y++) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'vat-year-cell';
      btn.textContent = String(y);
      btn.dataset.year = String(y);
      if (selected === y) btn.classList.add('is-selected');
      if (thisYear === y) btn.classList.add('is-current');
      btn.addEventListener('click', function () {
        input.value = String(y);
        input.dispatchEvent(new Event('change', { bubbles: true }));
        panel.hidden = true;
      });
      grid.appendChild(btn);
    }
  }

  function initYearPickers() {
    document.querySelectorAll('.vat-year-wrap').forEach(function (wrap) {
      const input = wrap.querySelector('.vat-year-picker');
      const panel = wrap.querySelector('.vat-year-panel');
      const prev = wrap.querySelector('.vat-year-prev');
      const next = wrap.querySelector('.vat-year-next');
      const clearBtn = wrap.querySelector('.vat-year-clear');
      if (!input || !panel || input.dataset.yearPickerReady === '1') return;
      input.dataset.yearPickerReady = '1';

      function openPanel() {
        closeAllYearPanels(panel);
        const selected = parseInt(input.value, 10) || new Date().getFullYear();
        wrap.dataset.decadeStart = String(selected - ((selected % 12 + 12) % 12));
        renderYearGrid(wrap);
        panel.hidden = false;
      }

      input.addEventListener('click', function (e) {
        e.preventDefault();
        if (panel.hidden) openPanel();
        else panel.hidden = true;
      });
      input.addEventListener('focus', function () {
        if (panel.hidden) openPanel();
      });

      if (prev) {
        prev.addEventListener('click', function (e) {
          e.preventDefault();
          wrap.dataset.decadeStart = String((parseInt(wrap.dataset.decadeStart || '0', 10) || new Date().getFullYear()) - 12);
          renderYearGrid(wrap);
        });
      }
      if (next) {
        next.addEventListener('click', function (e) {
          e.preventDefault();
          wrap.dataset.decadeStart = String((parseInt(wrap.dataset.decadeStart || '0', 10) || new Date().getFullYear()) + 12);
          renderYearGrid(wrap);
        });
      }
      if (clearBtn) {
        clearBtn.addEventListener('click', function (e) {
          e.preventDefault();
          input.value = '';
          input.dispatchEvent(new Event('change', { bubbles: true }));
          panel.hidden = true;
        });
      }
    });

    document.addEventListener('click', function (e) {
      if (e.target.closest('.vat-year-wrap')) return;
      closeAllYearPanels();
    });
  }

  function initFilterSelect2() {
    if (!(window.jQuery && typeof jQuery.fn.select2 !== 'undefined')) return;
    jQuery(function ($) {
      // Append to body so the menu is not clipped by filter-sidebar overflow.
      $('#filterForm select.select2').each(function () {
        const $el = $(this);
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.select2('destroy');
        }
        $el.select2({
          dropdownParent: $(document.body),
          allowClear: true,
          width: '100%',
          placeholder: $el.data('placeholder') || ($el.find('option:first').text() || 'Select'),
        });
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      initYearPickers();
      initFilterSelect2();
    });
  } else {
    initYearPickers();
    initFilterSelect2();
  }
})();
</script>
