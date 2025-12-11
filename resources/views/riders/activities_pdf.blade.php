<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rider Activities Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h2 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
            font-size: 12px;
        }
        .totals-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 8px;
        }
        .total-card {
            display: table-cell;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-left-width: 4px;
            border-radius: 8px;
            padding: 8px 10px;
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
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            margin-bottom: 4px;
            font-weight: bold;
        }
        .total-card .value {
            font-size: 14px;
            font-weight: 700;
            color: #111827;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th {
            background-color: #f3f4f6;
            font-weight: bold;
            padding: 8px;
            text-align: center;
            border: 1px solid #d1d5db;
        }
        table td {
            padding: 6px;
            text-align: center;
            border: 1px solid #d1d5db;
        }
        table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
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
                <th>Payout</th>
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
                $riderName = DB::Table('riders')->where('id', $r->rider_id)->value('name');
                @endphp
                <td>{{ $riderName ?? 'N/A' }}</td>
                <td>{{ $r->payout_type }}</td>
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

