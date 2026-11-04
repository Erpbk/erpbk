{{-- Left sidebar: voucher list — appears alongside the voucher detail panel (reference layout) --}}
@php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp
@if(Route::has('vouchers.list-sidebar') && $__companySlug)
<input type="hidden" id="vouchers_list_sidebar_url" value="{{ route('vouchers.list-sidebar', ['company_slug' => $__companySlug]) }}" />
@endif
<div id="voucherListSidebar" class="voucher-list-sidebar-wrapper" aria-hidden="true">
  <div id="voucherListSidebarBody" class="flex-grow-1 overflow-hidden d-flex flex-column">
    <div class="p-3 text-center text-muted">
      <div class="spinner-border spinner-border-sm" role="status"></div>
      <p class="mb-0 mt-2 small">Loading…</p>
    </div>
  </div>
</div>
<div id="voucherListSidebarBackdrop" class="voucher-list-sidebar-backdrop" aria-hidden="true"></div>

{{-- Module menu | All Vouchers | voucher detail --}}
<style>
  :root {
    --voucher-menu-offset: 16.25rem;
    --voucher-list-width: 280px;
  }
  html.layout-without-menu,
  .layout-wrapper.layout-without-menu {
    --voucher-menu-offset: 0px;
  }

  /* While vouchers are open, keep the full module menu width reserved. */
  body.voucher-panels-open {
    --voucher-menu-offset: 16.25rem;
  }

  .voucher-list-sidebar-wrapper {
    display: none;
    position: fixed;
    left: var(--voucher-menu-offset);
    top: 0;
    width: var(--voucher-list-width);
    max-width: 28%;
    height: 100%;
    z-index: 1085;
    background: var(--bs-body-bg);
    box-shadow: 2px 0 12px rgba(0,0,0,0.08);
    overflow: hidden;
    flex-direction: column;
  }
  .voucher-list-sidebar-wrapper.visible {
    display: flex !important;
  }
  .voucher-list-sidebar-wrapper .voucher-list-sidebar-row { cursor: pointer; transition: background 0.15s ease; }
  .voucher-list-sidebar-wrapper .voucher-list-sidebar-row:hover { background: rgba(0,0,0,0.04); }

  .voucher-list-sidebar-backdrop {
    display: none;
    position: fixed;
    left: calc(var(--voucher-menu-offset) + var(--voucher-list-width));
    top: 0;
    width: calc(100% - var(--voucher-menu-offset) - var(--voucher-list-width));
    height: 100%;
    background: rgba(0,0,0,0.15);
    z-index: 1078;
  }
  .voucher-list-sidebar-backdrop.visible { display: block !important; }

  /* Same module sidebar as the parent module — visible and usable. */
  body.voucher-panels-open #layout-menu.layout-menu {
    z-index: 1095 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    transform: none !important;
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
  body.voucher-panels-open #layout-menu.menu-vertical {
    position: fixed !important;
    left: 0 !important;
    top: 0 !important;
    bottom: 0 !important;
    height: 100% !important;
    width: 16.25rem !important;
    max-width: 16.25rem !important;
  }
  body.voucher-panels-open .layout-overlay.layout-menu-toggle {
    display: none !important;
  }

  #voucherPanel.voucher-panel-offcanvas.offcanvas-end {
    width: 75% !important;
    max-width: none;
    min-width: 400px;
    display: flex !important;
    flex-direction: column;
    height: 100%;
  }
  body.voucher-panels-open #voucherPanel.voucher-panel-offcanvas.offcanvas-end {
    left: calc(var(--voucher-menu-offset) + var(--voucher-list-width)) !important;
    right: 0 !important;
    width: calc(100% - var(--voucher-menu-offset) - var(--voucher-list-width)) !important;
    max-width: none;
  }
  body.voucher-panels-open .offcanvas-backdrop {
    left: calc(var(--voucher-menu-offset) + var(--voucher-list-width)) !important;
    width: calc(100% - var(--voucher-menu-offset) - var(--voucher-list-width)) !important;
  }
  #voucherPanel .offcanvas-body { flex: 1 1 auto; overflow-y: auto; min-height: 0; }
  #voucherPanel .voucher-panel-footer { flex-shrink: 0; border-top: 1px solid var(--bs-border-color); padding: 0.75rem 1rem; text-align: left; background: var(--bs-body-bg); }

  /* Fold / tablet (< 1200): All Vouchers numbers on the left; module nav stays closed. */
  @media (max-width: 1199.98px) {
    body.voucher-panels-open {
      --voucher-menu-offset: 0px;
      --voucher-list-width: min(300px, 36vw);
    }
    /* Do not force the module menu open — it would cover the voucher list. */
    body.voucher-panels-open #layout-menu.layout-menu,
    body.voucher-panels-open #layout-menu.menu-vertical {
      visibility: hidden !important;
      pointer-events: none !important;
      transform: translateX(-100%) !important;
      z-index: 1 !important;
    }
    body.voucher-panels-open .voucher-list-sidebar-wrapper.visible {
      left: 0 !important;
      width: var(--voucher-list-width) !important;
      max-width: 42% !important;
      z-index: 1090 !important;
      display: flex !important;
    }
    .voucher-list-sidebar-backdrop {
      left: var(--voucher-list-width) !important;
      width: calc(100% - var(--voucher-list-width)) !important;
    }
    body.voucher-panels-open #voucherPanel.voucher-panel-offcanvas.offcanvas-end {
      left: var(--voucher-list-width) !important;
      right: 0 !important;
      width: calc(100% - var(--voucher-list-width)) !important;
      min-width: 0;
    }
    body.voucher-panels-open .offcanvas-backdrop {
      left: var(--voucher-list-width) !important;
      width: calc(100% - var(--voucher-list-width)) !important;
    }
  }
  @media (max-width: 576px) {
    body.voucher-panels-open {
      --voucher-list-width: min(260px, 78vw);
    }
    #voucherPanel.voucher-panel-offcanvas.offcanvas-end { min-width: 0; }
  }
</style>
<div class="offcanvas offcanvas-end voucher-panel-offcanvas" data-bs-scroll="true" data-bs-backdrop="true" tabindex="-1" id="voucherPanel" aria-labelledby="voucherPanelTitle">
  <div class="offcanvas-header border-bottom">
    <h5 id="voucherPanelTitle" class="offcanvas-title">Voucher</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-4" id="voucherPanelBody" style="min-height: 200px;">
    <div class="p-4 text-center text-muted">
      <div class="spinner-border spinner-border-sm" role="status"></div>
      <p class="mb-0 mt-2 small">Loading…</p>
    </div>
  </div>
  <div class="voucher-panel-footer small text-muted" id="voucherPanelFooter">—</div>
</div>
