{{-- Left sidebar: voucher list — appears alongside the voucher detail panel (reference layout) --}}
<div id="voucherListSidebar" class="voucher-list-sidebar-wrapper" aria-hidden="true">
  <div id="voucherListSidebarBody" class="flex-grow-1 overflow-hidden d-flex flex-column">
    <div class="p-3 text-center text-muted">
      <div class="spinner-border spinner-border-sm" role="status"></div>
      <p class="mb-0 mt-2 small">Loading…</p>
    </div>
  </div>
</div>
<div id="voucherListSidebarBackdrop" class="voucher-list-sidebar-backdrop" aria-hidden="true"></div>

{{-- Voucher detail panel (right) + layout so list and panel sit side by side --}}
<style>
  /* Voucher list sidebar: left panel, fixed, hidden by default */
  .voucher-list-sidebar-wrapper {
    display: none;
    position: fixed;
    left: 0;
    top: 0;
    width: 280px;
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

  /* Backdrop: only dims the main content; does not cover list or detail panel */
  .voucher-list-sidebar-backdrop {
    display: none;
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.15);
    z-index: 1078;
  }
  .voucher-list-sidebar-backdrop.visible { display: block !important; }

  /* Voucher detail panel: default (no list) */
  #voucherPanel.voucher-panel-offcanvas.offcanvas-end {
    width: 75% !important;
    max-width: none;
    min-width: 400px;
    display: flex !important;
    flex-direction: column;
    height: 100%;
  }
  /* When list sidebar is visible: detail panel sits immediately to its right (no gap) */
  body.voucher-panels-open #voucherPanel.voucher-panel-offcanvas.offcanvas-end {
    left: 280px !important;
    right: 0 !important;
    width: calc(100% - 280px) !important;
    max-width: none;
  }
  /* Backdrop must not cover the list so clicking the list does not close the panel */
  body.voucher-panels-open .offcanvas-backdrop {
    left: 280px !important;
    width: calc(100% - 280px) !important;
  }
  #voucherPanel .offcanvas-body { flex: 1 1 auto; overflow-y: auto; min-height: 0; }
  #voucherPanel .voucher-panel-footer { flex-shrink: 0; border-top: 1px solid var(--bs-border-color); padding: 0.75rem 1rem; text-align: left; background: var(--bs-body-bg); }
  @media (max-width: 768px) {
    body.voucher-panels-open #voucherPanel.voucher-panel-offcanvas.offcanvas-end { left: 0 !important; width: 100% !important; }
    body.voucher-panels-open .offcanvas-backdrop { left: 0 !important; width: 100% !important; }
  }
  @media (max-width: 576px) {
    #voucherPanel.voucher-panel-offcanvas.offcanvas-end { width: 100% !important; min-width: 0; }
    .voucher-list-sidebar-wrapper { width: 100% !important; max-width: none !important; }
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
