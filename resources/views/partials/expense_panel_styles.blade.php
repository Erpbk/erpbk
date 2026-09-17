<style>
  /* ── Expense panel (neutral / professional) ───────────────── */
  .exp-panel {
    --exp-ink: #1e293b;
    --exp-muted: #64748b;
    --exp-line: #e2e8f0;
    --exp-surface: #f8fafc;
    --exp-accent: #334155;
    --exp-warn: #b45309;
    --exp-ok: #047857;
  }

  .exp-tab-nav {
    border-bottom: 1px solid var(--exp-line);
    margin-bottom: 1.25rem;
  }

  .exp-tab-nav .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: .6rem 1rem;
    font-weight: 600;
    font-size: .875rem;
    color: var(--exp-muted);
    transition: color .15s, border-color .15s;
  }

  .exp-tab-nav .nav-link:hover {
    color: var(--exp-ink);
  }

  .exp-tab-nav .nav-link.active {
    color: var(--exp-ink);
    border-bottom-color: var(--exp-accent);
    background: none;
  }

  .exp-tab-nav .nav-link .badge {
    font-size: .65rem;
    font-weight: 600;
    vertical-align: middle;
    background: #475569 !important;
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
    font-weight: 650;
    color: var(--exp-ink);
    letter-spacing: -.01em;
  }

  .section-header .btn-exp-primary {
    background: var(--exp-accent);
    border-color: var(--exp-accent);
    color: #fff;
    font-weight: 600;
  }

  .section-header .btn-exp-primary:hover {
    background: #1e293b;
    border-color: #1e293b;
    color: #fff;
  }

  .section-header .btn-exp-ghost {
    background: #fff;
    border: 1px solid var(--exp-line);
    color: var(--exp-ink);
    font-weight: 600;
  }

  .section-header .btn-exp-ghost:hover {
    background: var(--exp-surface);
    border-color: #cbd5e1;
    color: var(--exp-ink);
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
    border-radius: .5rem;
    padding: .65rem .9rem;
    min-width: 150px;
    flex: 1;
    border: 1px solid var(--exp-line);
    transition: border-color .15s, box-shadow .15s;
  }

  .stat-pill:hover {
    border-color: #cbd5e1;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
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
    border: 1px solid var(--exp-line);
    color: var(--exp-muted);
  }

  .stat-pill.danger .sp-icon {
    color: var(--exp-warn);
    background: #fffbeb;
    border-color: #fde68a;
  }

  .stat-pill.success .sp-icon {
    color: var(--exp-ok);
    background: #ecfdf5;
    border-color: #a7f3d0;
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
    font-size: 1rem;
    font-weight: 700;
    color: var(--exp-ink);
    line-height: 1.25;
    margin-top: .12rem;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }

  .stat-pill.danger .sp-value {
    color: #92400e;
  }

  .stat-pill.success .sp-value {
    color: #065f46;
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
</style>
