<dialog id="letterhead-print-dialog" class="letterhead-print-dialog">
  <form method="dialog" class="letterhead-print-dialog-inner">
    <h2 class="letterhead-print-dialog-title">Print agreement</h2>
    <p class="letterhead-print-dialog-text">Choose how to print this agreement:</p>
    <div class="letterhead-print-dialog-actions">
      <button type="submit" class="letterhead-print-dialog-btn letterhead-print-dialog-btn--primary" value="with">
        Print with Letterhead
      </button>
      <button type="submit" class="letterhead-print-dialog-btn letterhead-print-dialog-btn--secondary" value="without">
        Print without Letterhead
      </button>
      <button type="button" class="letterhead-print-dialog-btn letterhead-print-dialog-btn--cancel" id="letterhead-print-cancel">
        Cancel
      </button>
    </div>
  </form>
</dialog>

<style>
  .letterhead-print-dialog {
    border: none;
    border-radius: 10px;
    padding: 0;
    max-width: 420px;
    width: calc(100% - 32px);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.2);
  }

  .letterhead-print-dialog::backdrop {
    background: rgba(15, 23, 42, 0.45);
  }

  .letterhead-print-dialog-inner {
    padding: 24px;
    margin: 0;
  }

  .letterhead-print-dialog-title {
    margin: 0 0 8px;
    font-size: 18px;
    font-weight: 600;
    color: #0f172a;
  }

  .letterhead-print-dialog-text {
    margin: 0 0 20px;
    font-size: 14px;
    color: #475569;
  }

  .letterhead-print-dialog-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .letterhead-print-dialog-btn {
    padding: 10px 16px;
    font-size: 14px;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid #cbd5e1;
    background: #fff;
    color: #334155;
  }

  .letterhead-print-dialog-btn--primary {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
  }

  .letterhead-print-dialog-btn--secondary {
    background: #f8fafc;
  }

  .letterhead-print-dialog-btn--cancel {
    background: transparent;
    border-color: transparent;
    color: #64748b;
  }
</style>
