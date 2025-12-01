<!DOCTYPE html>
<html>

<head>
    <title>Garage Item Report</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            color: #333;
        }

        .header p {
            margin: 5px 0;
            color: #666;
        }

        .content {
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .status-in-stock {
            color: green;
            font-weight: bold;
        }

        .status-low-stock {
            color: orange;
            font-weight: bold;
        }

        .status-out-of-stock {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Garage Item Report</h1>
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>

    <div class="content">
        <h2>Item Details</h2>
        <table>
            <tr>
                <th>Item Name</th>
                <td>{{ $data['item_name'] }}</td>
            </tr>
            <tr>
                <th>Item Code</th>
                <td>{{ $data['item_code'] }}</td>
            </tr>
            <tr>
                <th>Supplier</th>
                <td>{{ $data['supplier_name'] }}</td>
            </tr>
            <tr>
                <th>Quantity</th>
                <td>{{ $data['quantity'] }}</td>
            </tr>
            <tr>
                <th>Price</th>
                <td>{{ number_format($data['price'], 2) }}</td>
            </tr>
            <tr>
                <th>Total Value</th>
                <td>{{ number_format($data['total_price'], 2) }}</td>
            </tr>
            <tr>
                <th>Purchase Date</th>
                <td>{{ $data['purchase_date'] }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    @if($data['status'] == 'In Stock')
                    <span class="status-in-stock">{{ $data['status'] }}</span>
                    @elseif($data['status'] == 'Low Stock')
                    <span class="status-low-stock">{{ $data['status'] }}</span>
                    @else
                    <span class="status-out-of-stock">{{ $data['status'] }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>This is an automatically generated report. Please keep for your records.</p>
    </div>
</body>

</html>