@php
    $formBrand = $brand ?? ($companyBrand ?? []);
    $invPrimary = $formBrand['primary_color'] ?? '#1e3a5f';
    $invPrimary2 = $formBrand['secondary_color'] ?? ($formBrand['primary_dark'] ?? '#2563eb');
    $invPrimarySoft = $formBrand['primary_soft'] ?? ($formBrand['primary_light'] ?? '#e8f0fe');
    $invBorder = $formBrand['border_color'] ?? '#e2e8f0';
    $invPrimaryRgb = $formBrand['primary_rgb'] ?? '30, 58, 95';
    $invItemsCols = $invItemsCols ?? '2.2fr .7fr .9fr .8fr .7fr 1fr .5fr';
@endphp
<style>
    .inv-form-wrap,
    .ri-form-wrap {
        --inv-primary: {{ $invPrimary }};
        --inv-primary-2: {{ $invPrimary2 }};
        --inv-primary-soft: {{ $invPrimarySoft }};
        --inv-border: {{ $invBorder }};
        --inv-muted: #64748b;
        --inv-bg: #f0f4f8;
        --inv-primary-rgb: {{ $invPrimaryRgb }};
        --inv-items-cols: {{ $invItemsCols }};
        /* Rider aliases */
        --ri-primary: var(--inv-primary);
        --ri-primary-soft: var(--inv-primary-soft);
        --ri-border: var(--inv-border);
        --ri-muted: var(--inv-muted);
        --ri-bg: var(--inv-bg);
        background: var(--inv-bg);
        margin: 0;
        padding: 1rem;
        border-radius: .5rem;
        overflow: visible;
    }

    .inv-form-wrap .inv-card,
    .ri-form-wrap .ri-card,
    .inv-form-wrap .ri-card,
    .ri-form-wrap .inv-card {
        background: #fff;
        border: 1px solid var(--inv-border);
        border-left: 4px solid var(--inv-primary);
        border-radius: 12px;
        padding: 1.15rem 1.25rem;
        height: auto;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
    }

    /* Equal-height only for paired side-by-side cards */
    .inv-form-wrap .row > [class*="col-lg-6"] > .inv-card,
    .inv-form-wrap .row > [class*="col-lg-6"] > .ri-card,
    .ri-form-wrap .row > [class*="col-lg-6"] > .inv-card,
    .ri-form-wrap .row > [class*="col-lg-6"] > .ri-card {
        height: 100%;
    }

    .inv-form-wrap .inv-card-title,
    .ri-form-wrap .ri-card-title,
    .inv-form-wrap .ri-card-title,
    .ri-form-wrap .inv-card-title {
        display: flex;
        align-items: center;
        gap: .5rem;
        font-size: .95rem;
        font-weight: 700;
        color: var(--inv-primary);
        margin: 0 0 1rem;
    }

    .inv-form-wrap .inv-card-title i,
    .ri-form-wrap .ri-card-title i,
    .inv-form-wrap .ri-card-title i,
    .ri-form-wrap .inv-card-title i {
        color: var(--inv-primary-2);
        font-size: 1rem;
    }

    .inv-form-wrap .form-label,
    .inv-form-wrap label,
    .ri-form-wrap .form-label,
    .ri-form-wrap label {
        font-size: .78rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: .35rem;
    }

    .inv-form-wrap .form-control,
    .inv-form-wrap .form-select,
    .ri-form-wrap .form-control,
    .ri-form-wrap .form-select {
        border-radius: 8px;
        border-color: var(--inv-border);
        font-size: .875rem;
        min-height: 38px;
    }

    .inv-form-wrap .form-control:focus,
    .inv-form-wrap .form-select:focus,
    .ri-form-wrap .form-control:focus,
    .ri-form-wrap .form-select:focus {
        border-color: var(--inv-primary-2);
        box-shadow: 0 0 0 .2rem rgba(var(--inv-primary-rgb), .15);
    }

    .inv-form-wrap .inv-items-head,
    .ri-form-wrap .ri-items-head,
    .inv-form-wrap .ri-items-head,
    .ri-form-wrap .inv-items-head {
        display: grid;
        grid-template-columns: var(--inv-items-cols);
        gap: .5rem;
        padding: 0 .25rem .5rem;
        border-bottom: 1px solid var(--inv-border);
        margin-bottom: .5rem;
    }

    .inv-form-wrap .inv-items-head span,
    .ri-form-wrap .ri-items-head span,
    .inv-form-wrap .ri-items-head span,
    .ri-form-wrap .inv-items-head span {
        font-size: .75rem;
        font-weight: 700;
        color: #475569;
    }

    .inv-form-wrap #rows-container > .row,
    .ri-form-wrap #rows-container > .row {
        display: grid !important;
        grid-template-columns: var(--inv-items-cols);
        gap: .5rem;
        margin: 0 0 .5rem !important;
        align-items: center;
    }

    .inv-form-wrap #rows-container > .row > [class*="col-"],
    .inv-form-wrap #rows-container > .row > .form-group,
    .ri-form-wrap #rows-container > .row > [class*="col-"],
    .ri-form-wrap #rows-container > .row > .form-group {
        width: auto !important;
        max-width: none !important;
        flex: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .inv-form-wrap .inv-add-item,
    .ri-form-wrap .ri-add-item,
    .inv-form-wrap .ri-add-item,
    .ri-form-wrap .inv-add-item {
        border: 1px solid var(--inv-primary-2);
        color: var(--inv-primary);
        background: #fff;
        border-radius: 8px;
        font-weight: 600;
        padding: .4rem .9rem;
    }

    .inv-form-wrap .inv-add-item:hover,
    .ri-form-wrap .ri-add-item:hover,
    .inv-form-wrap .ri-add-item:hover,
    .ri-form-wrap .inv-add-item:hover {
        background: var(--inv-primary-soft);
        color: var(--inv-primary);
    }

    /* Match tax invoice show: notes left + totals right */
    .inv-form-wrap .inv-totals-area,
    .ri-form-wrap .inv-totals-area,
    .inv-form-wrap .ri-totals-area,
    .ri-form-wrap .ri-totals-area {
        display: flex;
        justify-content: space-between;
        align-items: stretch;
        gap: 1.25rem;
        margin: .25rem 0;
    }

    .inv-form-wrap .inv-totals-notes,
    .ri-form-wrap .inv-totals-notes,
    .inv-form-wrap .ri-totals-notes,
    .ri-form-wrap .ri-totals-notes {
        flex: 1;
        min-width: 0;
        max-width: calc(100% - 324px);
        padding: 14px 16px;
        background: #f8fafc;
        border: 1px solid var(--inv-border);
        border-left: 4px solid var(--inv-primary);
        border-top: 2px solid var(--inv-primary);
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }

    .inv-form-wrap .inv-totals-notes h4,
    .ri-form-wrap .inv-totals-notes h4,
    .inv-form-wrap .ri-totals-notes h4,
    .ri-form-wrap .ri-totals-notes h4 {
        margin: 0 0 8px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .9px;
        text-transform: uppercase;
        color: var(--inv-primary);
        flex-shrink: 0;
    }

    .inv-form-wrap .inv-totals-notes .form-group,
    .ri-form-wrap .inv-totals-notes .form-group,
    .inv-form-wrap .ri-totals-notes .form-group,
    .ri-form-wrap .ri-totals-notes .form-group {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        margin: 0;
        min-height: 0;
    }

    .inv-form-wrap .inv-totals-notes .form-control,
    .ri-form-wrap .inv-totals-notes .form-control,
    .inv-form-wrap .ri-totals-notes .form-control,
    .ri-form-wrap .ri-totals-notes .form-control {
        background: #fff;
        border-radius: 6px;
        flex: 0 1 auto;
        min-height: 88px;
        resize: vertical;
    }

    .inv-form-wrap #rows-container,
    .ri-form-wrap #rows-container {
        min-height: 0;
    }

    .inv-form-wrap .inv-totals-notes .form-label,
    .ri-form-wrap .inv-totals-notes .form-label,
    .inv-form-wrap .ri-totals-notes .form-label,
    .ri-form-wrap .ri-totals-notes .form-label,
    .inv-form-wrap .inv-totals-notes small,
    .ri-form-wrap .inv-totals-notes small,
    .inv-form-wrap .ri-totals-notes small,
    .ri-form-wrap .ri-totals-notes small {
        display: none;
    }

    .inv-form-wrap .inv-totals,
    .ri-form-wrap .inv-totals,
    .inv-form-wrap .ri-totals,
    .ri-form-wrap .ri-totals {
        width: 300px;
        flex-shrink: 0;
        margin-left: auto;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
    }

    .inv-form-wrap .inv-summary-row,
    .ri-form-wrap .ri-summary-row,
    .inv-form-wrap .ri-summary-row,
    .ri-form-wrap .inv-summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 16px;
        font-size: 12.5px;
        color: #334155;
        border-bottom: 1px solid var(--inv-border);
    }

    .inv-form-wrap .inv-summary-row:last-child,
    .ri-form-wrap .ri-summary-row:last-child,
    .inv-form-wrap .ri-summary-row:last-child,
    .ri-form-wrap .inv-summary-row:last-child {
        border-bottom: 0;
    }

    .inv-form-wrap .inv-summary-row > span:first-child,
    .ri-form-wrap .ri-summary-row > span:first-child,
    .inv-form-wrap .ri-summary-row > span:first-child,
    .ri-form-wrap .inv-summary-row > span:first-child {
        color: var(--inv-muted);
        font-weight: 500;
    }

    .inv-form-wrap .inv-summary-total,
    .ri-form-wrap .ri-summary-total,
    .inv-form-wrap .ri-summary-total,
    .ri-form-wrap .inv-summary-total {
        margin-top: 6px;
        padding: 14px 16px;
        background: var(--inv-primary);
        color: #fff;
        border-radius: 6px;
        border-bottom: 0;
        font-weight: 700;
    }

    .inv-form-wrap .inv-summary-total > span:first-child,
    .ri-form-wrap .ri-summary-total > span:first-child,
    .inv-form-wrap .ri-summary-total > span:first-child,
    .ri-form-wrap .inv-summary-total > span:first-child {
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: .8px;
        text-transform: uppercase;
        opacity: .92;
    }

    .inv-form-wrap .inv-summary-total .inv-summary-value,
    .ri-form-wrap .ri-summary-total .ri-summary-value,
    .inv-form-wrap .ri-summary-total .ri-summary-value,
    .ri-form-wrap .inv-summary-total .inv-summary-value,
    .inv-form-wrap .inv-summary-total .ri-summary-value,
    .ri-form-wrap .ri-summary-total .inv-summary-value {
        color: #fff;
        font-size: 18px;
        font-weight: 800;
    }

    .inv-form-wrap .inv-summary-value,
    .ri-form-wrap .ri-summary-value,
    .inv-form-wrap .ri-summary-value,
    .ri-form-wrap .inv-summary-value {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
        font-weight: 600;
        color: #0f172a;
    }

    @media (max-width: 991.98px) {
        .inv-form-wrap .inv-totals-area,
        .ri-form-wrap .inv-totals-area,
        .inv-form-wrap .ri-totals-area,
        .ri-form-wrap .ri-totals-area {
            flex-direction: column;
        }

        .inv-form-wrap .inv-totals-notes,
        .ri-form-wrap .inv-totals-notes,
        .inv-form-wrap .ri-totals-notes,
        .ri-form-wrap .ri-totals-notes {
            max-width: 100%;
        }

        .inv-form-wrap .inv-totals,
        .ri-form-wrap .inv-totals,
        .inv-form-wrap .ri-totals,
        .ri-form-wrap .ri-totals {
            width: 100%;
            margin-left: 0;
        }
    }

    .inv-form-wrap .btn-remove-row,
    .ri-form-wrap .btn-remove-row {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border-radius: 8px;
        color: #ef4444 !important;
        background: #fef2f2;
        text-decoration: none;
    }

    .inv-form-wrap .btn-remove-row:hover,
    .ri-form-wrap .btn-remove-row:hover {
        background: #fee2e2;
    }

    .inv-form-wrap .select2-container .select2-selection--single,
    .ri-form-wrap .select2-container .select2-selection--single {
        height: 38px !important;
        border-radius: 8px !important;
        border-color: var(--inv-border) !important;
        padding-top: 4px;
    }

    .inv-form-wrap .select2-container--default .select2-selection--single .select2-selection__arrow,
    .ri-form-wrap .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 36px !important;
    }

    @media (max-width: 991.98px) {
        .inv-form-wrap .inv-items-head,
        .ri-form-wrap .ri-items-head,
        .inv-form-wrap .ri-items-head,
        .ri-form-wrap .inv-items-head {
            display: none;
        }

        .inv-form-wrap #rows-container > .row,
        .ri-form-wrap #rows-container > .row {
            grid-template-columns: 1fr 1fr;
        }

        .inv-form-wrap #rows-container > .row > :first-child,
        .ri-form-wrap #rows-container > .row > :first-child {
            grid-column: 1 / -1;
        }
    }
</style>
