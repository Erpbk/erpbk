@php
    $brand = $brand ?? [];
    $invPrimary = $brand['primary_color'] ?? '#2563eb';
    $invSecondary = $brand['secondary_color'] ?? '#1e3a8a';
    $invOnPrimary = $brand['text_on_primary'] ?? '#ffffff';
    $invBorder = $brand['border_color'] ?? '#d1d5db';
    $invSurface = $brand['primary_light'] ?? '#f8fafc';
    $invSurfaceSoft = $brand['primary_soft'] ?? '#f1f5f9';
@endphp

:root {
    --inv-primary: {{ $invPrimary }};
    --inv-secondary: {{ $invSecondary }};
    --inv-on-primary: {{ $invOnPrimary }};
    --inv-border: {{ $invBorder }};
    --inv-surface: {{ $invSurface }};
    --inv-surface-soft: {{ $invSurfaceSoft }};
    --inv-text: #1f2937;
    --inv-text-muted: #6b7280;
}

.invoice-box {
    width: 850px;
    max-width: 100%;
    margin: 0 auto;
    padding: 16px;
    background: #fff;
    border: 1px solid var(--inv-border);
    color: var(--inv-text);
}

.invoice-box table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
}

.invoice-box th,
.invoice-box td {
    border: 1px solid var(--inv-border);
    padding: 6px 8px;
    font-size: 12px;
}

.invoice-box th {
    background: var(--inv-primary);
    color: var(--inv-on-primary);
    font-weight: 600;
    text-align: center;
}

.invoice-box td.num {
    text-align: right;
}

.invoice-box .no-border td {
    border: none;
    padding: 3px 6px;
}

.invoice-box .inv-doc-title {
    margin: 0 0 8px;
    font-size: 22px;
    font-weight: 700;
    color: var(--inv-primary);
    letter-spacing: 0.02em;
}

.invoice-box .inv-meta {
    margin: 4px 0;
    font-size: 13px;
    color: var(--inv-text);
}

.invoice-box .inv-meta-muted {
    color: var(--inv-text-muted);
}

.invoice-box .inv-section-header {
    display: block;
    margin-bottom: 8px;
    padding: 6px 10px;
    background: var(--inv-primary);
    color: var(--inv-on-primary);
    font-weight: 600;
    font-size: 12px;
}

.invoice-box .inv-panel {
    padding: 10px;
    background: var(--inv-surface);
    border: 1px solid var(--inv-border);
}

.invoice-box .inv-panel p {
    margin: 4px 0;
}

.invoice-box .inv-label {
    color: var(--inv-text-muted);
    font-weight: 600;
}

.invoice-box .inv-total-row td {
    background: var(--inv-surface-soft);
    color: var(--inv-primary);
    font-weight: 600;
}

.invoice-box .inv-grand-total td {
    background: var(--inv-primary);
    color: var(--inv-on-primary);
    font-weight: 700;
}

.invoice-box .inv-footer-note {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid var(--inv-border);
    font-size: 11px;
    color: var(--inv-text-muted);
    text-align: center;
}

.invoice-box .items-table th {
    background: var(--inv-primary);
    color: var(--inv-on-primary);
}

.invoice-box .items-table tbody tr:nth-child(even) {
    background: var(--inv-surface);
}
