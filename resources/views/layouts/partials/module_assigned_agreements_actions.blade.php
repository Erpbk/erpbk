@php
  $agreementActionItems = [];
  try {
    if (auth()->check()) {
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

  function recordHref(item, recordId) {
    var pattern = item.record_preview_pattern || item.index_url || item.preview_url || item.show_url || '';
    if (!pattern || !recordId) {
      return '';
    }
    return pattern.replace('__RECORD__', encodeURIComponent(recordId));
  }

  function iconHtml() {
    return '<i class="ti ti-file-certificate me-1"></i>';
  }

  function rowItem(item, recordId) {
    var href = recordHref(item, recordId);
    if (!href) {
      return null;
    }
    var a = document.createElement('a');
    a.className = 'dropdown-item waves-effect';
    a.setAttribute('data-agreement-action', '1');
    a.href = href;
    a.innerHTML = iconHtml() + ' ';
    a.appendChild(document.createTextNode(item.name || 'Agreements'));
    return a;
  }

  function injectRowMenus() {
    document.querySelectorAll('[id^="actiondropdown"]').forEach(function (btn) {
      var menu = btn.parentElement ? btn.parentElement.querySelector('.dropdown-menu') : null;
      if (!menu || menu.getAttribute('data-agreement-injected') === '1') {
        return;
      }
      if (menu.querySelector('[data-agreement-action]')) {
        menu.setAttribute('data-agreement-injected', '1');
        return;
      }
      var recordId = String(btn.id || '').replace(/^actiondropdown_?/, '');
      if (!recordId) {
        menu.setAttribute('data-agreement-injected', '1');
        return;
      }
      var el = rowItem(items[0], recordId);
      if (el) {
        menu.insertBefore(el, menu.firstChild);
      }
      menu.setAttribute('data-agreement-injected', '1');
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', injectRowMenus);
  } else {
    injectRowMenus();
  }

  document.addEventListener('shown.bs.dropdown', injectRowMenus);

  if (window.MutationObserver) {
    var scheduled = false;
    var observer = new MutationObserver(function () {
      if (scheduled) {
        return;
      }
      scheduled = true;
      setTimeout(function () {
        scheduled = false;
        injectRowMenus();
      }, 50);
    });
    observer.observe(document.body, { childList: true, subtree: true });
  }

  if (window.jQuery) {
    jQuery(document).on('ajaxComplete', injectRowMenus);
  }
})();
</script>
@endif
