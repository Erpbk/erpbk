<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Rider Activities Report</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @page {
            margin: 2mm;
            size: A4 landscape;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 2mm;
        }

        .header {
            text-align: center;
            margin-bottom: 2mm;
            page-break-after: avoid;
        }

        .header h2 {
            margin: 0;
            font-size: 13px;
            line-height: 1.1;
            margin-bottom: 0.5mm;
        }

        .header p {
            margin: 0.3mm 0;
            font-size: 8px;
            line-height: 1.1;
        }

        .totals-cards {
            display: table;
            width: 100%;
            margin-bottom: 2mm;
            border-collapse: separate;
            border-spacing: 2px;
            page-break-after: avoid;
        }

        .total-card {
            display: table-cell;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left-width: 3px;
            border-radius: 3px;
            padding: 2px 3px;
            vertical-align: top;
            width: 12.5%;
        }

        .total-delivered {
            border-left-color: #10b981;
        }

        .total-rejected {
            border-left-color: #ef4444;
        }

        .total-hours {
            border-left-color: #3b82f6;
        }

        .total-ontime {
            border-left-color: #8b5cf6;
        }

        .total-valid-days {
            border-left-color: #f59e0b;
        }

        .total-card .label {
            font-size: 8px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 1px;
            font-weight: bold;
            line-height: 1.1;
        }

        .total-card .value {
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            line-height: 1.1;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            font-size: 9px;
            page-break-inside: avoid;
        }

        thead {
            display: table-header-group;
        }

        tbody {
            display: table-row-group;
        }

        table th {
            background-color: #f3f4f6;
            font-weight: bold;
            padding: 2px 2px;
            text-align: center;
            border: 1px solid #d1d5db;
            font-size: 9px;
            line-height: 1.1;
            height: 4.5mm;
        }

        table td {
            padding: 2px 2px;
            text-align: center;
            border: 1px solid #d1d5db;
            font-size: 9px;
            line-height: 1.1;
            height: 4.5mm;
        }

        table tbody tr {
            height: 4.5mm;
            page-break-inside: avoid;
        }

        table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            display: inline-block;
        }

        .bg-success {
            background-color: #10b981;
            color: white;
        }

        .bg-warning {
            background-color: #f59e0b;
            color: white;
        }

        .bg-danger {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Rider Activities Report</h2>
        <p><strong>Rider:</strong> {{ $rider->name ?? 'N/A' }}</p>
        <p><strong>Month:</strong> {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</p>
    </div>

    <div class="totals-cards">
        <div class="total-card total-delivered">
            <div class="label">Working Days</div>
            <div class="value">{{ $totals['working_days'] ?? 0 }}</div>
        </div>
        <div class="total-card total-rejected">
            <div class="label">Valid Days</div>
            <div class="value">{{ $totals['valid_days'] ?? 0 }}</div>
        </div>
        <div class="total-card total-hours">
            <div class="label">Invalid Days</div>
            <div class="value">{{ $totals['invalid_days'] ?? 0 }}</div>
        </div>
        <div class="total-card total-ontime">
            <div class="label">Off Days</div>
            <div class="value">{{ $totals['off_days'] ?? 0 }}</div>
        </div>
        <div class="total-card total-valid-days">
            <div class="label">Total Orders</div>
            <div class="value">{{ number_format($totals['total_orders'] ?? 0) }}</div>
        </div>
        <div class="total-card total-ontime">
            <div class="label">OnTime%</div>
            <div class="value">{{ number_format($totals['avg_ontime'] ?? 0, 2) }}%</div>
        </div>
        <div class="total-card total-rejected">
            <div class="label">Rejection</div>
            <div class="value">{{ number_format($totals['total_rejected'] ?? 0) }}</div>
        </div>
        <div class="total-card total-hours">
            <div class="label">Total Hours</div>
            <div class="value">{{ number_format($totals['total_hours'] ?? 0, 2) }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>ID</th>
                <th>Name</th>
                <th>Designation</th>
                <th>Delivered</th>
                <th>Ontime%</th>
                <th>Rejected</th>
                <th>HR</th>
                <th>Valid Day</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $r)
            <tr>
                <td>{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
                <td>{{ $r->d_rider_id }}</td>
                @php
                $rider = company_table('riders')->where('id', $r->rider_id)->first();
                @endphp
                <td>{{ $rider->name ?? 'N/A' }}</td>
                <td>{{ $rider->designation }}</td>
                <td>{{ $r->delivered_orders }}</td>
                <td>
                    @if($r->ontime_orders_percentage)
                    {{ number_format($r->ontime_orders_percentage * 100, 2) }}%
                    @else
                    -
                    @endif
                </td>
                <td>{{ $r->rejected_orders }}</td>
                <td>{{ $r->login_hr }}</td>
                <td>
                    @if ($r->delivery_rating == 'Yes')
                    <span class="badge bg-success">Valid</span>
                    @elseif($r->delivery_rating == 'No')
                    <span class="badge bg-warning">Invalid</span>
                    @else
                    <span class="badge bg-danger">Off</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>