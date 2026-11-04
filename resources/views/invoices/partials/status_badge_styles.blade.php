<style>
  .invoice-status-badge {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.3px;
    line-height: 1.35;
    border: 1px solid transparent;
    animation: invoice-status-blink 1.25s ease-in-out infinite;
  }

  .invoice-status-paid {
    color: #166534;
    background: #dcfce7;
    border-color: #86efac;
  }

  .invoice-status-unpaid {
    color: #991b1b;
    background: #fee2e2;
    border-color: #fca5a5;
    animation-name: invoice-status-blink-red;
  }

  .invoice-status-partial {
    color: #92400e;
    background: #fef3c7;
    border-color: #fcd34d;
    animation-name: invoice-status-blink-amber;
  }

  @keyframes invoice-status-blink {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
    50% { opacity: 0.55; box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
  }

  @keyframes invoice-status-blink-red {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    50% { opacity: 0.55; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0); }
  }

  @keyframes invoice-status-blink-amber {
    0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
    50% { opacity: 0.55; box-shadow: 0 0 0 4px rgba(245, 158, 11, 0); }
  }
</style>
