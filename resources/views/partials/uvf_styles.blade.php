{{-- Shared Universal Voucher Form (UVF) styles — matches Payment Voucher / Advance Loan chrome --}}
<style>
    .uvf-form,
    .rider-payment-form {
        --rpf-blue: #1e4b8f;
        --rpf-blue-soft: #3a6db5;
        --rpf-border: #c5d4eb;
        --rpf-bg: #f4f7fc;
        padding: 0.25rem 0.15rem 0.5rem;
    }

    .rpf-header {
        margin-bottom: 1.1rem;
    }

    .rpf-title {
        margin: 0;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--rpf-blue);
        letter-spacing: -0.01em;
    }

    .rpf-subtitle {
        margin: 0.15rem 0 0;
        font-size: 0.9rem;
        color: var(--rpf-blue-soft);
    }

    .rpf-label {
        display: block;
        font-weight: 600;
        font-size: 0.82rem;
        color: var(--rpf-blue);
        margin-bottom: 0.35rem;
    }

    .rpf-input,
    .uvf-form .select2-container--default .select2-selection--single,
    .rider-payment-form .select2-container--default .select2-selection--single {
        border-color: var(--rpf-border) !important;
        border-radius: 0.35rem !important;
        min-height: 38px;
    }

    .rpf-payee-box {
        border: 1px solid var(--rpf-border);
        border-radius: 0.35rem;
        padding: 0.45rem 0.75rem;
        background: #fff;
        min-height: 38px;
    }

    .rpf-payee-name {
        font-weight: 600;
        color: #1f2937;
        line-height: 1.25;
    }

    .rpf-payee-id {
        font-size: 0.78rem;
        color: #6b7280;
        margin-top: 0.1rem;
    }

    .rpf-amount-group .input-group-text {
        background: var(--rpf-bg);
        border-color: var(--rpf-border);
        color: var(--rpf-blue);
        font-weight: 600;
    }

    .rpf-balance-meta {
        margin-top: 0.35rem;
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .rpf-balance-due,
    .rpf-ledger-due {
        font-size: 0.82rem;
        font-weight: 600;
    }

    .rpf-balance-due {
        color: #dc2626;
    }

    .rpf-ledger-due {
        color: #1e4b8f;
    }

    .rpf-attach-box {
        border: 1.5px dashed #7aa2d8;
        border-radius: 0.6rem;
        background: #eef4fb;
        padding: 1rem 0.85rem;
        height: 100%;
        min-height: 220px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .rpf-attach-box .document-scan-dropzone {
        background: transparent;
        border: none;
        min-height: 72px;
    }

    .rpf-attach-box .document-scan-dropzone-icon {
        color: var(--rpf-blue-soft);
        font-size: 1.75rem;
    }

    .rpf-attach-box .document-scan-dropzone-text {
        color: var(--rpf-blue);
        font-weight: 600;
    }

    .rpf-attach-box .document-scan-dropzone-actions {
        display: flex;
        flex-direction: column;
        gap: 0.45rem;
    }

    .rpf-attach-box .document-scan-dropzone-actions .btn {
        border-color: var(--rpf-blue-soft);
        color: var(--rpf-blue);
        background: #fff;
    }

    .rpf-attach-hint {
        margin-top: 0.55rem;
        text-align: center;
        font-size: 0.75rem;
        color: #6b7280;
    }

    .rpf-section-title {
        color: var(--rpf-blue);
        font-weight: 700;
        margin-bottom: 0.65rem;
    }

    .rpf-summary.h-100 {
        display: flex;
        flex-direction: column;
    }

    .rpf-summary.h-100 .rpf-summary-total {
        margin-top: auto;
    }

    .rpf-adj-stack {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }

    .rpf-inv-tbl-wrap {
        width: 100%;
        border: 1px solid #c5d8f0;
        border-radius: 6px;
        overflow: hidden;
        background: #fff;
    }

    .rpf-inv-items-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
        color: #0f172a;
    }

    .rpf-inv-items-table th,
    .rpf-inv-items-table td {
        border: 1px solid #e2e8f0;
        padding: 8px 10px;
        font-size: 12px;
        vertical-align: middle;
    }

    .rpf-inv-sec-header,
    .rpf-inv-accent-total td {
        background: #eef4fc !important;
        color: #0f172a !important;
        font-weight: 700;
        text-align: left;
    }

    .rpf-inv-sec-header {
        text-align: left;
        font-size: 12px;
        letter-spacing: 0.02em;
        text-transform: uppercase;
    }

    .rpf-inv-items-table tbody td:first-child,
    .rpf-inv-accent-total td:first-child {
        text-align: left;
        width: 70%;
    }

    .rpf-inv-num,
    .rpf-inv-items-table tbody td:last-child,
    .rpf-inv-accent-total td:last-child {
        text-align: right !important;
        font-weight: 600;
        width: 30%;
        white-space: nowrap;
    }

    .rpf-inv-accent-total td {
        font-size: 13px;
        padding: 8px 10px;
    }

    .rpf-adj-empty {
        text-align: center !important;
        color: #6b7280;
        font-weight: 500 !important;
    }

    .rpf-adj-payable {
        font-size: 0.9rem;
        color: var(--rpf-blue);
        font-weight: 600;
        padding: 0.15rem 0.1rem 0;
    }

    .rpf-adj-payable .rpf-breakdown-month:not(:empty)::before {
        content: '·';
        margin: 0 0.35rem;
    }

    .rpf-invoice-scroll {
        max-height: 280px;
        overflow-y: auto;
        border: 1px solid var(--rpf-border);
        border-radius: 0.4rem;
    }

    .rpf-invoice-table {
        margin-bottom: 0;
        font-size: 0.88rem;
    }

    .rpf-invoice-table thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #e8eef8;
        color: var(--rpf-blue);
        font-size: 0.72rem;
        letter-spacing: 0.03em;
        font-weight: 700;
        border-color: var(--rpf-border);
        white-space: nowrap;
    }

    .rpf-invoice-table td {
        vertical-align: middle;
        border-color: #dde5f2;
    }

    .rpf-line-amount {
        width: 120px;
        margin-left: auto;
        text-align: right;
    }

    .rpf-table-footer {
        text-align: right;
        padding: 0.65rem 0.25rem 0;
        color: #374151;
        font-size: 0.9rem;
    }

    .rpf-summary {
        background: #fff;
        border: 1px solid var(--rpf-border);
        border-radius: 0.45rem;
        padding: 0.85rem 1rem 0;
        overflow: hidden;
    }

    .rpf-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding: 0.35rem 0;
        font-size: 0.9rem;
        color: #374151;
    }

    .rpf-summary-charges {
        padding-bottom: 0.65rem;
    }

    .rpf-charges-group {
        max-width: 150px;
    }

    .rpf-charges-group .input-group-text {
        background: var(--rpf-bg);
        border-color: var(--rpf-border);
        color: var(--rpf-blue);
        font-size: 0.78rem;
    }

    .rpf-summary-total {
        margin: 0 -1rem;
        padding: 0.75rem 1rem;
        background: #e7effa;
        color: var(--rpf-blue);
        font-weight: 800;
        font-size: 1.05rem;
        text-align: right;
    }

    .uvf-form .form-group,
    .rider-payment-form .form-group {
        margin-bottom: 0;
    }

    .uvf-lines-table {
        width: 100%;
        margin-bottom: 0;
        font-size: 0.88rem;
    }

    .uvf-lines-table thead th {
        background: #e8eef8;
        color: var(--rpf-blue);
        font-size: 0.72rem;
        letter-spacing: 0.03em;
        font-weight: 700;
        border-color: var(--rpf-border);
        white-space: nowrap;
    }

    .uvf-lines-table td {
        vertical-align: middle;
        border-color: #dde5f2;
        padding: 0.5rem 0.4rem;
    }

    .rpf-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.6rem;
        padding: 0.75rem 1rem 0.25rem;
        border-top: 1px solid #d7e2f2;
        margin-top: 0.75rem;
    }

    .rpf-actions .btn-outline-primary {
        border-color: #3a6db5;
        color: #1e4b8f;
        background: #fff;
        min-width: 96px;
    }

    .rpf-save-btn {
        background: #1e4b8f !important;
        border-color: #1e4b8f !important;
        min-width: 130px;
        font-weight: 600;
    }

    @media (max-width: 991.98px) {
        .rpf-attach-box {
            min-height: 180px;
            margin-top: 0.5rem;
        }

        .rpf-line-amount {
            width: 100%;
        }
    }
</style>
