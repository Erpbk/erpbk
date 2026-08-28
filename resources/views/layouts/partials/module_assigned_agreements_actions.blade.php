@php
  $agreementActionItems = [];
  try {
    if (auth()->check() && (auth()->user()->can('agreements_view') || auth()->user()->can('agreements_generate') || auth()->user()->can('gn_settings'))) {
      $agreementActionItems = app(\App\Services\Agreements\AgreementModuleService::class)->actionMenuItemsForModule();
    }
  } catch (\Throwable) {
    $agreementActionItems = [];
  }
@endphp
@if(!empty($agreementActionItems))
<script>
(function () {
  var items = @json($agreementActionItems);
  if (!items || !items.length) {
    return;
  }

  function iconHtml() {
    return '<i class="ti ti-file-certificate me-1"></i>';
  }

  function pageItem(item) {
    var href = item.preview_url || item.show_url;
    if (!href) {
      return null;
    }
    var a = document.createElement('a');
    a.className = 'action-dropdown-item';
    a.setAttribute('data-agreement-action', '1');
    a.href = href;
    a.target = '_blank';
    a.rel = 'noopener';
    a.innerHTML = iconHtml() +
      '<div><div class="action-dropdown-item-text"></div>' +
      '<div class="action-dropdown-item-desc">Open assigned agreement</div></div>';
    a.querySelector('.action-dropdown-item-text').textContent = item.name;
    return a;
  }

  function rowItem(item, recordId) {
    var href = item.record_preview_pattern
      ? item.record_preview_pattern.replace('__RECORD__', encodeURIComponent(recordId))
      : (item.preview_url || item.show_url);
    if (!href) {
      return null;
    }
    var a = document.createElement('a');
    a.className = 'dropdown-item waves-effect';
    a.setAttribute('data-agreement-action', '1');
    a.href = href;
    a.target = '_blank';
    a.rel = 'noopener';
    a.innerHTML = iconHtml() + ' ';
    a.appendChild(document.createTextNode(item.name));
    return a;
  }

  function injectPageMenus() {
    document.querySelectorAll('.action-dropdown-menu').forEach(function (menu) {
      if (menu.getAttribute('data-agreement-injected') === '1') {
        return;
      }
      if (menu.querySelector('[data-agreement-action]')) {
        menu.setAttribute('data-agreement-injected', '1');
        return;
      }
      items.forEach(function (item) {
        var el = pageItem(item);
        if (el) {
          menu.appendChild(el);
        }
      });
      menu.setAttribute('data-agreement-injected', '1');
    });
  }

  function injectRowMenus() {
    document.querySelectorAll('[id^="actiondropdown_"]').forEach(function (btn) {
      var menu = btn.parentElement ? btn.parentElement.querySelector('.dropdown-menu') : null;
      if (!menu || menu.getAttribute('data-agreement-injected') === '1') {
        return;
      }
      if (menu.querySelector('[data-agreement-action]')) {
        menu.setAttribute('data-agreement-injected', '1');
        return;
      }
      var recordId = String(btn.id || '').replace('actiondropdown_', '');
      if (!recordId) {
        return;
      }
      items.forEach(function (item) {
        var el = rowItem(item, recordId);
        if (el) {
          menu.insertBefore(el, menu.firstChild);
        }
      });
      menu.setAttribute('data-agreement-injected', '1');
    });
  }

  function injectAll() {
    injectPageMenus();
    injectRowMenus();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectAll);
  } else {
    injectAll();
  }

  document.addEventListener('shown.bs.dropdown', injectAll);
})();
</script>
@endif
