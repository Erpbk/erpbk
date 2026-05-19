@php
    $settings = \Illuminate\Support\Facadescompany_table('settings')->pluck('value', 'name')->toArray();
    $first = $installments->first();
    $totalAmount = (float) $installments->sum('amount');
    $paidSum = (float) $installments->where('status', 'paid')->sum('amount');
    $pendingSum = (float) $installments->where('status', 'pending')->sum('amount');
    $planRefTotal = $first->total_amount !== null && $first->total_amount !== '' ? (float) $first->total_amount : $totalAmount;
    $paidCount = $installments->where('status', 'paid')->count();
    $pendingCount = $installments->where('status', 'pending')->count();
@endphp

<div class="visa-installment-invo-wrap">
    <div class="controls no-print">
        <button type="button" class="print-btn" onclick="printVisaInstallmentInvoice()">Print Invoice</button>
    </div>

    <div class="invoice-box">
        <!-- Header: Logo + Company + Title (same structure as rider_invoices/show) -->
        <table class="no-border" style="margin-bottom: 20px; border: none; background: transparent;">
            <tr style="border: none;">
                <td style="width: 33%; border: none !important; vertical-align: middle;">
                    <img src="{{ $companyLogoUrl ?? \Illuminate\Support\Facades\URL::asset('assets/img/logo-full.png') }}" width="150" alt="logo" />
                </td>
                <td style="width: 34%; text-align: center; border: none !important;">
                    <h4 style="margin: 0 0 4px 0; font-size: 14px; font-weight:700;">{{ $settings['company_name'] ?? ($companyDisplayName ?? config('variables.templateName')) }}</h4>
                    <p style="margin: 3px 0; font-size: 12px;">{{ $settings['company_address'] ?? '' }}</p>
                    <p style="margin: 3px 0; font-size: 12px;">TRN {{ $settings['vat_number'] ?? '' }}</p>
                </td>
                <td style="width: 33%; text-align: center; border: none !important;">
                    <h2 style="margin: 0; font-weight: 800; color: #004aad; font-size: 24px;">VISA INSTALLMENT PLAN</h2>
                </td>
            </tr>
        </table>

        <div class="flex-row-cards">
            <div class="rider-card">
                <div class="card-header">
                    <strong>👤 Rider Details</strong>
                </div>
                <div class="details-grid">
                    <span class="detail-label">Rider ID:</span>
                    <span class="detail-value">{{ $rider->rider_id }}</span>
                    <span class="detail-label">Rider Name:</span>
                    <span class="detail-value">{{ $rider->name }}</span>
                    <span class="detail-label">Rider Status:</span>
                    <span class="detail-value" @if(isset($rider->status) && in_array((int) $rider->status, [3,4,5], true)) style="color:red;" @endif>
                        {{ isset($rider->status) ? \App\Helpers\General::RiderStatus($rider->status) : '—' }}
                    </span>
                    <span class="detail-label">Mobile:</span>
                    <span class="detail-value">{{ $rider->personal_contact ?? $rider->company_contact ?? (@$rider->sim->number) ?? '—' }}</span>
                    <span class="detail-label">Joining Date:</span>
                    <span class="detail-value">{{ $rider->doj ? \Carbon\Carbon::parse($rider->doj)->format('d/m/Y') : '—' }}</span>
                    <span class="detail-label">Client:</span>
                    <span class="detail-value">{{ @$rider->vendor->name ?? '—' }}</span>
                    <span class="detail-label">Fleet Supervisor:</span>
                    <span class="detail-value">{{ $rider->fleet_supervisor ?? '—' }}</span>
                </div>
            </div>

            <div class="details-card">
                <div class="card-header">
                    <strong>📄 Plan Summary</strong>
                </div>
                <div class="details-grid">
                    <span class="detail-label">Invoice No:</span>
                    <span class="detail-value">INV-{{ str_pad($first->id, 6, '0', STR_PAD_LEFT) }}</span>
                    <span class="detail-label">Invoice Date:</span>
                    <span class="detail-value">{{ $first->created_at->format('d/m/Y') }}</span>
                    <span class="detail-label">Plan reference:</span>
                    <span class="detail-value">{{ $first->reference_number ?? '—' }}</span>
                    <span class="detail-label">Installments:</span>
                    <span class="detail-value">{{ $installments->count() }} (Paid {{ $paidCount }} · Pending {{ $pendingCount }})</span>
                    <span class="detail-label">Plan total (ref):</span>
                    <span class="detail-value">{{ \App\Helpers\Currency::format($planRefTotal, 2) }}</span>
                    <span class="detail-label">Ledger key:</span>
                    <span class="detail-value">{{ $first->rider_id }}</span>
                </div>
            </div>
        </div>

        <div style="overflow-x: auto; margin-top: 5px;">
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Sr.</th>
                        <th>Date</th>
                        <th>Billing Month</th>
                        <th>Narration</th>
                        <th>Status</th>
                        <th>Amount ({{ \App\Helpers\Currency::code() }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($installments as $key => $installment)
                    <tr>
                        <td class="num">{{ $key + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($installment->date)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($installment->billing_month)->format('M-Y') }}</td>
                        <td>{{ $installment->narration ?: ('Installment ' . ($key + 1)) }}</td>
                        <td style="text-align: center;">{{ $installment->status === 'paid' ? 'Paid' : 'Pending' }}</td>
                        <td class="num">{{ number_format((float) $installment->amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr class="accent-total">
                        <td colspan="4" style="text-align:right; font-weight:bold;">Total installments ({{ $installments->count() }})</td>
                        <td style="text-align:center; font-weight:bold;">—</td>
                        <td class="num" style="font-weight:bold;">{{ number_format($totalAmount, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="financial-summary">
            <table>
                <thead><tr><th colspan="2" class="secondary-header">Financial Summary</th></tr></thead>
                <tbody>
                    <tr><td style="font-weight: 600;">Total plan amount</td><td class="num">{{ number_format($totalAmount, 2) }}</td></tr>
                    <tr><td>Paid installments</td><td class="num">{{ number_format($paidSum, 2) }}</td></tr>
                    <tr><td>Pending installments</td><td class="num">{{ number_format($pendingSum, 2) }}</td></tr>
                    <tr class="success-highlight"><td><strong>REFERENCE TOTAL</strong></td><td class="num"><strong>{{ number_format($planRefTotal, 2) }}</strong></td></tr>
                </tbody>
            </table>
        </div>

        <div class="grand-total-wrapper">
            <div class="grand-total-card">
                <div>TOTAL INSTALLMENT AMOUNT</div>
                <div>{{ \App\Helpers\Currency::format($totalAmount, 2) }}</div>
            </div>
        </div>

        <div class="notes-section">
            <strong>📌 Note:</strong><br>
            All installment payments are due as per the billing month above. This plan is binding once acknowledged.
            Changes must be agreed in writing. Early payment does not imply a discount unless specified.
        </div>

        <div class="installment-sign-block" style="text-align: right; margin-top: 16px;">
            <div>
                <span class="sign-spacer" style="display: block; height: 36px;"></span>
                <span style="display: inline-block; border-top: 2px solid #000; padding-top: 6px; font-weight: bold;">{{ $rider->name }}</span>
                <br>
                <span style="font-size: 11px; color: #5b6e8c;">Rider acknowledgment</span>
            </div>
        </div>

        <div class="footer-note">
            <p style="margin: 0;">Thank you for your partnership! For queries reach: {{ $settings['company_phone'] ?? 'Company Phone' }} | {{ $settings['company_email'] ?? 'Company Email' }}</p>
        </div>
    </div>
</div>

<script>
    (function () {
        function formatNums() {
            document.querySelectorAll('.visa-installment-invo-wrap .num').forEach(function (el) {
                var raw = el.innerText.trim();
                var num = parseFloat(raw.replace(/,/g, ''));
                if (!isNaN(num) && raw !== '') {
                    var formatted = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    if (el.innerText !== formatted) el.innerText = formatted;
                }
            });
        }
        formatNums();
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', formatNums);
        }
    })();
</script>
