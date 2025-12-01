<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installment Plan Invoice - {{ $rider->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.4;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border: 1px solid #ddd;
        }

        .invoice-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 2px solid #007bff;
            text-align: center;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .invoice-title {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .invoice-info {
            display: flex;
            justify-content: space-between;
            padding: 0px 20px;
            border-bottom: 1px solid #eee;
        }

        .invoice-details,
        .rider-details {
            flex: 1;
        }

        .invoice-details h4,
        .rider-details h4 {
            color: #007bff;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }

        .detail-row {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .detail-label {
            font-weight: bold;
            display: inline-block;
            /* width: 120px; */
        }

        .installment-table {
            width: 100%;
            border-collapse: collapse;
            /* margin: 20px 0; */
        }

        .installment-table th,
        .installment-table td {
            padding: 7px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        .installment-table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
            text-align: center;
        }

        .installment-table td {
            text-align: center;
        }

        .amount-column {
            text-align: right !important;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .total-row td {
            border-top: 2px solid #007bff;
        }

        .summary-section {
            padding: 20px;
            background-color: #f8f9fa;
            margin: 20px 0;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .summary-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }

        .summary-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }

        .signature-section {
            padding: 0px 20px;
            border-top: 1px solid #eee;
        }

        .signature-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 30px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            height: 50px;
            margin-bottom: 10px;
        }

        .signature-label {
            font-size: 14px;
            color: #666;
        }

        .terms-section {
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
        }

        .terms-title {
            font-size: 16px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 15px;
        }

        .terms-list {
            font-size: 12px;
            color: #666;
            line-height: 1.6;
        }

        .terms-list li {
            margin-bottom: 8px;
        }

        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        @media print {
            .print-button {
                display: none;
            }

            body {
                margin: 0;
                padding: 0;
            }

            .invoice-container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-container">

        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-image"><img src="{{ asset('assets/img/logo.png') }}" alt="Company Logo" style="width: 37px; height: 31px;"><span style="font-size: 32px; font-weight: bold;">{{config('variables.templateName')}}</span></div>
            <div class="invoice-title">Loan Installment Plan Invoice</div>

        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <div class="rider-details">
                <h4>Rider Information</h4>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span>{{ $rider->name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Rider ID:</span>
                    <span>{{ $rider->rider_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Contact:</span>
                    <span>{{ $rider->contact_no ?? 'N/A' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Account ID:</span>
                    <span>{{ $installments->first()->rider_id }}</span>
                </div>
            </div>
            <div class="invoice-details" style="text-align: end;">
                <h4>Invoice Details</h4>
                <div class="detail-row">
                    <span class="detail-label">Invoice #:</span>
                    <span>INV-{{ str_pad($installments->first()->id, 6, '0', STR_PAD_LEFT) }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date Created:</span>
                    <span>{{ $installments->first()->created_at->format('M d, Y') }}</span>
                </div>
            </div>


        </div>
        <!-- Installment Table -->
        <table class="installment-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Billing Month</th>
                    <th colspan="2">Narration</th>
                    <th>Status</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($installments as $index => $installment)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($installment->date)->format('d M, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($installment->billing_month)->format('F Y') }}</td>
                    <td colspan="2">Installment {{ $index + 1 }}</td>
                    <td>{{ $installment->status === 'paid' ? 'Paid' : 'Pending' }}</td>
                    <td class="">AED {{ number_format($installment->amount, 2) }}</td>
                </tr>
                @endforeach
                <tr>
                    <td></td>
                    <td style="font-weight: bold;">Total Installments:{{ $installments->count() }}</td>
                    <td></td>
                    <td colspan="2" style="text-align: center; font-weight: bold;">Total Amount:</td>
                    <td></td>
                    <td style="font-weight: bold;">AED {{ number_format($installments->sum('amount'), 2) }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Signature Section -->
        <div class="signature-section">
            <h4 style="text-align: center; color: #007bff; margin-bottom: 30px;">
                Agreement Acknowledgment
            </h4>

            <p style="text-align: center; margin-bottom: 30px; font-size: 14px; color: #666;">
                By signing below, both parties acknowledge and agree to the installment plan terms outlined above.
            </p>

            <div class="signature-grid">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <strong>Rider Signature</strong><br>
                        <small>{{ $rider->name }}</small><br>
                    </div>
                </div>

                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        <strong>Authorized Signature</strong><br>
                        <small>{{ auth()->user()->name ?? 'Company Representative' }}</small><br>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms Section -->
        <div class="terms-section">
            <div class="terms-title">Terms & Conditions</div>
            <ul class="terms-list">
                <li>All installment payments are due on or before the specified billing month.</li>
                <li>This installment plan is binding upon signature by both parties.</li>
                <li>Any changes to this plan must be agreed upon in writing by both parties.</li>
                <li>In case of early payment, no discount will be applied unless otherwise specified.</li>
                <li>This document serves as an official record of the agreed installment plan.</li>
            </ul>
        </div>
    </div>
    <!-- Print Button -->
    <button class="print-button" style="text-align: center; display: block; margin: 0 auto; margin-top: 20px;" onclick="window.print()">
        <i class="fa fa-print"></i> Print Invoice
    </button>

    <script>
        // Auto-focus for better printing experience
        window.onload = function() {
            if (window.location.search.includes('print=true')) {
                window.print();
            }
        };
    </script>
</body>

</html>