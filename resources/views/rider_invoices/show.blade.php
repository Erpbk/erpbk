<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>RiderID: {{ $riderInvoice->rider->rider_id }} Month: {{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Calibri, Arial, sans-serif;
            font-size: 12px;
            color: #000;
            background: #eef2f5;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .invoice-box table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .invoice-box th, .invoice-box td { border: 1px solid #ddd; padding: 8px 10px; font-size: 12px; vertical-align: top; }
        .invoice-box th { background: #004aad; color: white; font-weight: 600; text-align: center; }
        .invoice-box td { text-align: left; }
        .invoice-box td.num { text-align: right; }
        .no-border td { border: none; padding: 4px 6px; }
        .primary-header { background: #211c1d; color: white; font-weight: bold; }
        .secondary-header { background: #004aad; color: white; font-weight: bold; }
        .accent-total { background: #5271ff; color: white; font-weight: bold; }
        .success-highlight { background: #004aad; color: white; font-weight: bold; }
        .rider-card, .details-card {
            padding: 16px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .invoice-box .card-header {
            margin-bottom: 14px;
            padding-bottom: 8px;
            border-bottom: 2px solid #004aad;
            background-color: white !important;
        }
        .invoice-box .card-header strong { color: #004aad; font-size: 15px; }
        .details-grid { display: grid; grid-template-columns: 140px 1fr; gap: 12px 8px; align-items: baseline; }
        .detail-label { font-weight: 700; color: #2c3e66; font-size: 12px; }
        .detail-value { color: #1e293b; font-weight: 500; }
        .flex-row-cards { display: flex; gap: 20px; margin-bottom: 24px; flex-wrap: wrap; }
        .flex-row-cards > div { flex: 1; min-width: 280px; }
        .notes-section {
            margin: 20px 0;
            padding: 12px 16px;
            background: #fef9e6;
            border-left: 4px solid #ffb347;
            border-radius: 8px;
        }
        .items-table th, .items-table td { border: 1px solid #ccc; }
        .items-table th { background: #004aad; color: white; }
        .financial-summary { display: flex; justify-content: flex-end; margin-top: 10px; margin-bottom: 15px; }
        .financial-summary table { width: 45%; min-width: 270px; border: 1px solid #e2e8f0; }
        .grand-total-wrapper { margin-top: 24px; text-align: right; }
        .grand-total-card {
            display: inline-block;
            padding: 12px 28px;
            background: #004aad;
            color: white;
            border-radius: 30px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,74,173,0.2);
        }
        .grand-total-card div:last-child { font-size: 26px; font-weight: 800; }
        .footer-note {
            margin-top: 28px;
            text-align: center;
            font-size: 11px;
            color: #5b6e8c;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }
        .red { color: red; font-weight: bold; }
        .invoice-toolbar {
            max-width: 1200px;
            margin: 0 auto 12px;
            background: #f4f6f8;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }
        .invoice-toolbar-inner { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .toolbar-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid #d8dee6;
            border-radius: 6px;
            color: #334155;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
        }
        .toolbar-btn:hover { background: #f8fafc; color: #004aad; border-color: #004aad; }
        .template-selector {
            max-width: 1200px;
            margin: 0 auto 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
        }
        @media print {
            body { background: white; padding: 0; }
            .invoice-box { box-shadow: none; border-radius: 0; }
            .no-print { display: none !important; }
            th, .secondary-header, .grand-total-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
        @media (max-width: 700px) {
            .flex-row-cards { flex-direction: column; }
            .financial-summary table { width: 100%; }
        }
    </style>
</head>
<body>
@php
    $resolver = app(\App\Services\RiderInvoice\RiderInvoiceTemplateResolver::class);
    $activeTemplate = $activeTemplate ?? $resolver->resolveForInvoice($riderInvoice);
    $templateView = $templateView ?? $activeTemplate->viewName();
    $templates = $templates ?? $resolver->activeTemplates();
    require resource_path('views/rider_invoices/partials/invoice_calculations_vars.php');
@endphp
@include('rider_invoices.partials.action_buttons')

<div class="invoice-box">
    @include($templateView)
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.num').forEach(function(el) {
        var raw = el.innerText.trim();
        var num = parseFloat(raw.replace(/,/g, ''));
        if (!isNaN(num) && raw !== '') {
            var formatted = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            if (el.innerText !== formatted) el.innerText = formatted;
        }
    });

    var templateForm = document.getElementById('riderInvoiceTemplateForm');
    var templateSelect = document.getElementById('template_id');
    if (templateForm && templateSelect) {
        templateSelect.addEventListener('change', function(e) {
            e.preventDefault();
            fetch(templateForm.action, {
                method: 'POST',
                body: new FormData(templateForm),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            }).then(function(res) { return res.json(); })
              .then(function(data) {
                  if (data.reload) window.location.reload();
              }).catch(function() { templateForm.submit(); });
        });
    }
});
</script>
</body>
</html>
