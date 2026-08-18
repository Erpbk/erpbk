/**
 * Apply Role Field Permissions: visible + not-editable => readonly/disabled (never hidden).
 * Editable locks apply only on the module create/edit form — never on listing filters/search.
 * Expects window.__rfpLocks = { entitySlug: { fieldName: true, ... }, ... }
 * Optional window.__rfpDefaultEntity for the current page module.
 */
(function (window, document) {
  'use strict';

  function locksFor(entity) {
    var all = window.__rfpLocks || {};
    return (entity && all[entity]) ? all[entity] : {};
  }

  function normalizeName(name) {
    if (!name) return '';
    name = String(name).trim();
    var m = name.match(/^custom_field_values\[(\d+)\]$/)
      || name.match(/^custom_field_values\.(\d+)$/)
      || name.match(/^voucher_custom_fields\[(\d+)\]$/);
    if (m) return 'cf_' + m[1];
    if (name.slice(-2) === '[]') name = name.slice(0, -2);
    return name;
  }

  function isListingFilterControl(el) {
    if (!el || !el.closest) return false;
    return !!(
      el.closest('[data-rfp-skip-lock]') ||
      el.closest('.filter-sidebar') ||
      el.closest('#filterSidebar') ||
      el.closest('#filterForm') ||
      el.closest('.filter-body') ||
      el.closest('.card-search') ||
      el.closest('#quickSearch') ||
      el.closest('.filtetmodal') ||
      el.closest('#searchModal')
    );
  }

  function lockControl(el) {
    if (!el) return;
    var tag = (el.tagName || '').toLowerCase();
    var type = (el.getAttribute('type') || '').toLowerCase();
    var already = el.getAttribute('data-rfp-lock-applied') === '1';

    if (!already) {
      if (tag === 'select' || type === 'checkbox' || type === 'radio' || type === 'file') {
        el.disabled = true;
      } else {
        el.readOnly = true;
        // Some browsers still allow focus styling; keep interaction blocked.
        el.setAttribute('readonly', 'readonly');
      }
      el.setAttribute('data-rfp-locked', '1');
      el.setAttribute('data-rfp-lock-applied', '1');
      el.classList.add('rfp-field-locked');
    } else if (tag === 'select' || type === 'checkbox' || type === 'radio' || type === 'file') {
      // Keep disabled in case a plugin re-enabled the control.
      el.disabled = true;
    }

    // Select2: re-sync disabled state after plugin init (must run even on re-apply).
    if (tag === 'select' && window.jQuery) {
      var $el = window.jQuery(el);
      if ($el.hasClass('select2-hidden-accessible') || $el.data('select2')) {
        try {
          $el.prop('disabled', true).trigger('change.select2');
        } catch (e) { /* ignore */ }
      }
    }
  }

  function resolveEntity(root, el) {
    var node = el;
    while (node && node !== root && node !== document.body) {
      if (node.getAttribute) {
        var ent = node.getAttribute('data-rfp-entity');
        if (ent) return ent;
      }
      node = node.parentNode;
    }
    if (root && root.getAttribute) {
      var rootEnt = root.getAttribute('data-rfp-entity');
      if (rootEnt) return rootEnt;
    }
    return window.__rfpDefaultEntity || null;
  }

  function applyFieldPermissionLocks(root) {
    root = root || document;
    var scope = root.querySelectorAll
      ? root
      : document;

    var controls = scope.querySelectorAll
      ? scope.querySelectorAll('input[name], select[name], textarea[name]')
      : [];

    Array.prototype.forEach.call(controls, function (el) {
      if (isListingFilterControl(el)) return;
      if (el.type === 'hidden' && el.getAttribute('data-rfp-locked') !== '1') {
        // Keep hidden companions for disabled checkboxes; don't lock every hidden.
        return;
      }
      if (el.name === '_token' || el.name === '_method') return;

      // Blade may already mark the control; still re-sync Select2/disabled state.
      if (el.getAttribute('data-rfp-locked') === '1') {
        lockControl(el);
        return;
      }

      var entity = resolveEntity(scope.nodeType ? scope : document, el);
      if (!entity) return;

      var field = normalizeName(el.name);
      var locks = locksFor(entity);
      if (!locks[field]) return;

      lockControl(el);
    });

    // Hide/disable quick-edit pencils that target a locked field.
    var editTriggers = scope.querySelectorAll
      ? scope.querySelectorAll('[data-rfp-edit-field], .rfp-edit-trigger[data-field], .inline-edit-btn[data-field], .edit-inline[data-field]')
      : [];
    Array.prototype.forEach.call(editTriggers, function (btn) {
      if (isListingFilterControl(btn)) return;
      var entity = resolveEntity(scope.nodeType ? scope : document, btn)
        || btn.getAttribute('data-rfp-entity')
        || window.__rfpDefaultEntity;
      var field = btn.getAttribute('data-rfp-edit-field')
        || btn.getAttribute('data-field')
        || '';
      field = normalizeName(field);
      if (!entity || !field) return;
      if (locksFor(entity)[field]) {
        btn.style.display = 'none';
        btn.setAttribute('aria-disabled', 'true');
        btn.classList.add('rfp-edit-hidden');
      }
    });
  }

  function boot() {
    applyFieldPermissionLocks(document);
    // Re-apply after Select2 / AJAX modal content loads.
    if (window.jQuery) {
      window.jQuery(document).on('shown.bs.modal ajaxComplete select2:open', function (e) {
        var target = (e && e.target && e.target.querySelectorAll) ? e.target : document;
        setTimeout(function () { applyFieldPermissionLocks(target); }, 50);
      });
      // Select2 init often runs in page scripts after DOMContentLoaded — re-lock shortly after.
      setTimeout(function () { applyFieldPermissionLocks(document); }, 300);
      setTimeout(function () { applyFieldPermissionLocks(document); }, 1000);
    }

    if (window.MutationObserver) {
      var obs = new MutationObserver(function (mutations) {
        var needs = false;
        mutations.forEach(function (m) {
          if (m.addedNodes && m.addedNodes.length) needs = true;
        });
        if (needs) {
          clearTimeout(obs._t);
          obs._t = setTimeout(function () { applyFieldPermissionLocks(document); }, 100);
        }
      });
      obs.observe(document.body, { childList: true, subtree: true });
    }
  }

  window.applyFieldPermissionLocks = applyFieldPermissionLocks;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})(window, document);
