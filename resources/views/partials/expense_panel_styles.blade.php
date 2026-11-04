<style>
  /* ── Expense panel (neutral / professional) ───────────────── */
  .exp-panel {
    --exp-ink: #1e293b;
    --exp-muted: #64748b;
    --exp-line: #e2e8f0;
    --exp-surface: #f8fafc;
    --exp-accent: #1e3a5f;
    --exp-danger: #dc2626;
    --exp-ok: #16a34a;
    --exp-warn: #d97706;
    --exp-info: #0ea5e9;
  }

  /* Match rider profile tabs: full baseline + active blue underline sitting on it */
  .exp-tab-nav {
    border-bottom: none;
    margin-bottom: 1.25rem;
    background: #fff;
    border: 1px solid #e8ecf1;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(15, 23, 42, .06);
    padding: 8px 10px 0;
  }

  .exp-tab-nav > .nav {
    border-bottom: 1px solid #e2e8f0 !important;
    gap: 0;
    flex-wrap: wrap;
  }

  .exp-tab-nav .nav-item {
    margin: 0 !important;
  }

  .exp-tab-nav .nav-link {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    border: 0 !important;
    border-bottom: 3px solid transparent !important;
    /* Same as rider/employee profile tabs: rounds the active underline ends */
    border-radius: 0 0 12px 12px !important;
    margin: 0 0 -1px 0 !important;
    padding: .55rem .9rem !important;
    font-weight: 500;
    font-size: .8125rem;
    line-height: 1.25;
    color: #6c757d !important;
    background: transparent !important;
    box-shadow: none !important;
    white-space: nowrap;
    transition: color .15s, border-color .15s;
  }

  .exp-tab-nav .nav-link:hover {
    color: #1e4b8e !important;
    font-weight: 600;
    background: transparent !important;
    border-bottom-color: #1e4b8e !important;
  }

  .exp-tab-nav .nav-link.active {
    color: #1e4b8e !important;
    font-weight: 600;
    border-bottom-color: #1e4b8e !important;
    background: transparent !important;
  }

  .exp-tab-nav .nav-link .badge {
    font-size: .65rem;
    font-weight: 600;
    vertical-align: middle;
    background: #dc2626 !important;
    color: #fff;
  }

  .exp-card {
    border: 1px solid var(--exp-line) !important;
    border-radius: .65rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important;
  }

  .section-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .9rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid var(--exp-line);
  }

  .section-header .section-icon {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: .4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    color: var(--exp-muted);
    background: var(--exp-surface);
    border: 1px solid var(--exp-line);
    flex-shrink: 0;
  }

  .section-header h5 {
    margin: 0;
    font-size: .95rem;
    font-weight: 700;
    color: var(--exp-accent);
    letter-spacing: .04em;
    text-transform: uppercase;
  }

  .section-header .btn-primary {
    font-weight: 600;
  }

  .section-header .btn-outline-primary {
    font-weight: 600;
  }

  .stat-strip {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    padding: 1rem 1.25rem;
    background: var(--exp-surface);
    border-bottom: 1px solid var(--exp-line);
  }

  .stat-pill {
    display: flex;
    align-items: center;
    gap: .75rem;
    background: #fff;
    border-radius: .55rem;
    padding: .7rem .95rem;
    min-width: 150px;
    flex: 1;
    border: 1px solid var(--exp-line);
    border-top: 3px solid var(--exp-line);
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    transition: border-color .15s, box-shadow .15s;
  }

  .stat-pill:hover {
    box-shadow: 0 2px 6px rgba(15, 23, 42, .08);
  }

  .stat-pill.danger {
    border-top-color: var(--exp-danger);
  }

  .stat-pill.success {
    border-top-color: var(--exp-ok);
  }

  .stat-pill.warning {
    border-top-color: var(--exp-warn);
  }

  .stat-pill.info {
    border-top-color: var(--exp-info);
  }

  .stat-pill .sp-icon {
    font-size: .8rem;
    width: 2.1rem;
    height: 2.1rem;
    flex-shrink: 0;
    border-radius: .4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--exp-surface);
    border: none;
    color: var(--exp-muted);
  }

  .stat-pill.danger .sp-icon {
    color: var(--exp-danger);
    background: #fee2e2;
  }

  .stat-pill.success .sp-icon {
    color: var(--exp-ok);
    background: #dcfce7;
  }

  .stat-pill.warning .sp-icon {
    color: var(--exp-warn);
    background: #ffedd5;
  }

  .stat-pill.info .sp-icon {
    color: var(--exp-info);
    background: #e0f2fe;
  }

  .stat-pill .sp-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .stat-pill .sp-label {
    font-size: .62rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--exp-muted);
    line-height: 1.2;
    font-weight: 600;
    white-space: nowrap;
  }

  .stat-pill .sp-value {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--exp-ink);
    line-height: 1.25;
    margin-top: .12rem;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }

  .stat-pill.danger .sp-value {
    color: #b91c1c;
  }

  .stat-pill.success .sp-value {
    color: #15803d;
  }

  .stat-pill.warning .sp-value,
  .stat-pill.info .sp-value {
    color: var(--exp-ink);
  }

  .exp-card > .card-body {
    background: #fff;
  }

  .exp-panel .badge.bg-danger,
  .exp-panel .badge.bg-success,
  .exp-panel .badge.bg-primary,
  .exp-panel .badge.bg-warning,
  .exp-panel .badge.bg-info {
    font-weight: 600;
    letter-spacing: .02em;
    border-radius: .35rem;
    padding: .35em .65em;
  }

  .exp-panel .badge.bg-danger {
    background: #fff7ed !important;
    color: #9a3412 !important;
    border: 1px solid #fed7aa;
  }

  .exp-panel .badge.bg-success {
    background: #ecfdf5 !important;
    color: #065f46 !important;
    border: 1px solid #a7f3d0;
  }

  .exp-panel .badge.bg-primary {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
  }

  .exp-panel .badge.bg-warning {
    background: #fffbeb !important;
    color: #92400e !important;
    border: 1px solid #fde68a;
  }

  .exp-panel .badge.bg-info {
    background: #f8fafc !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0;
  }

  .exp-panel .table thead th {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    font-weight: 650;
    border-bottom-color: #e2e8f0;
    background: #fff;
  }

  .exp-panel .table tbody td {
    vertical-align: middle;
    color: #1e293b;
    border-color: #f1f5f9;
  }

  /* Visa status column: dashed arrow connector (demo v4 flow design) */
  .exp-panel .visa-status-flow,
  .riders-list-demo .visa-status-flow {
    position: relative;
    overflow: visible !important;
    white-space: nowrap;
  }

  .exp-panel .visa-status-bdg,
  .riders-list-demo .visa-status-bdg {
    position: relative;
    z-index: 2;
    display: inline-block;
    font-weight: 600;
    font-size: 11.5px;
    padding: 4px 10px;
    border-radius: 6px;
    background: #eef4ff;
    color: #1e3a8a;
    border: 1px solid #bfdbfe;
  }

  .exp-panel .visa-status-flow::after,
  .riders-list-demo .visa-status-flow::after {
    content: "";
    position: absolute;
    left: 50%;
    top: calc(50% + 12px);
    bottom: calc(-50% + 12px);
    border-left: 2px dashed #94a3b8;
    transform: translateX(-1px);
    z-index: 1;
    pointer-events: none;
  }

  .exp-panel .visa-status-flow::before,
  .riders-list-demo .visa-status-flow::before {
    content: "";
    position: absolute;
    left: 50%;
    bottom: calc(-50% + 11px);
    transform: translateX(-6px);
    border: 6px solid transparent;
    border-top: 8px solid #94a3b8;
    border-bottom: 0;
    z-index: 1;
    pointer-events: none;
  }

  .exp-panel .visa-status-flow.is-last::after,
  .exp-panel .visa-status-flow.is-last::before,
  .riders-list-demo .visa-status-flow.is-last::after,
  .riders-list-demo .visa-status-flow.is-last::before {
    display: none;
  }

  .exp-panel .visa-status-flow.is-done::after,
  .riders-list-demo .visa-status-flow.is-done::after {
    border-left: 2.5px solid #16a34a;
  }

  .exp-panel .visa-status-flow.is-done::before,
  .riders-list-demo .visa-status-flow.is-done::before {
    border-top-color: #16a34a;
  }

  .exp-panel .visa-status-flow.is-done .visa-status-bdg,
  .riders-list-demo .visa-status-flow.is-done .visa-status-bdg {
    background: #dcfce7;
    color: #15803d;
    border-color: #86efac;
  }

  .exp-panel .visa-status-flow.is-done .visa-status-bdg::before,
  .riders-list-demo .visa-status-flow.is-done .visa-status-bdg::before {
    content: "✓ ";
    font-weight: 700;
  }
</style>
