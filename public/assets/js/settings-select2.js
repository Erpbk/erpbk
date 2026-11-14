/**
 * Apply Select2 to every <select> inside Settings pages.
 * Skips: [data-no-select2], .no-select2, and selects already handled with custom templates
 * unless force re-init is requested via window.initSettingsSelect2(root, true).
 */
(function (window, document) {
  'use strict';

  function hasSelect2() {
    return !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2);
  }

  function shouldSkip($el) {
    if (!$el || !$el.length) return true;
    if ($el.is('[data-no-select2], .no-select2')) return true;
    if ($el.closest('[data-no-select2]').length) return true;
    // Native multi month/day pickers that are tiny can stay native if marked.
    return false;
  }

  function dropdownParentFor($el) {
    var $parent = $el.closest('.modal.show, .modal, .offcanvas.show, .offcanvas, .cd-modal, .cd-card, .dropdown-menu, .tab-pane.active, .tab-pane, .company-details-root, .content-wrapper');
    return $parent.length ? $parent : window.jQuery(document.body);
  }

  function placeholderFor($el) {
    var ph = $el.attr('data-placeholder') || $el.attr('placeholder');
    if (ph) return ph;
    var empty = $el.find('option[value=""]').first().text();
    if (empty && String(empty).trim()) return String(empty).trim();
    return 'Select…';
  }

  function initOne(el, force) {
    if (!hasSelect2() || !el) return;
    var $el = window.jQuery(el);
    if (shouldSkip($el)) return;

    if ($el.hasClass('select2-hidden-accessible')) {
      if (!force) return;
      try {
        $el.select2('destroy');
      } catch (e) {}
    }

    var allowClear =
      $el.is('[data-allow-clear]') ||
      $el.find('option[value=""]').length > 0 ||
      $el.prop('multiple') === true;

    var opts = {
      width: '100%',
      dropdownParent: dropdownParentFor($el),
      placeholder: placeholderFor($el),
      allowClear: !!allowClear && !$el.prop('required'),
      minimumResultsForSearch: 0
    };

    try {
      $el.select2(opts);
    } catch (err) {
      // Ignore init errors from detached nodes.
    }
  }

  function initSettingsSelect2(root, force) {
    if (!hasSelect2()) return;
    var $root = root ? window.jQuery(root) : window.jQuery(document);
    if (!$root.length) return;

    var $selects = $root.is('select') ? $root : $root.find('select');
    $selects.each(function () {
      initOne(this, !!force);
    });
  }

  window.initSettingsSelect2 = initSettingsSelect2;

  function boot() {
    initSettingsSelect2(document, false);

    // Re-init when settings content is swapped/re-rendered.
    if (window.MutationObserver) {
      var timer = null;
      var target =
        document.querySelector('.content-wrapper') ||
        document.getElementById('companyDetailsRoot') ||
        document.body;
      if (!target) return;

      var obs = new MutationObserver(function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
          initSettingsSelect2(target, false);
        }, 80);
      });
      obs.observe(target, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window, document);
