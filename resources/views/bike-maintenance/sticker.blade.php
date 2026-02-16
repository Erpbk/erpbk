{{-- resources/views/maintenance/sticker.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Sticker - 76x50mm</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        /* Exact sticker size: 76mm x 50mm */
        .sticker {
            width: 76mm;
            height: 50mm;
            border: 2px solid #000;
            padding: 3mm;
            font-family: Arial, sans-serif;
            background: white;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            border-bottom: 1px solid #000;
            padding-bottom: 1mm;
            margin-bottom: 2mm;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .content {
            flex: 1;
            font-size: 10pt;
        }
        
        .content table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .content td {
            padding: 1mm 0;
        }
        
        .content td.label {
            font-weight: bold;
            width: 45%;
            font-size: 9pt;
        }
        
        .content td.value {
            font-weight: bold;
            font-size: 11pt;
        }
        
        .next-service {
            text-align: center;
            font-weight: bold;
            color: #c00;
            font-size: 11pt;
            margin: 2mm 0;
        }
        
        .no-print {
            text-align: center;
            margin: 20px;
        }
        
        .print-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin: 0 5px;
        }
        
        .print-btn.close {
            background-color: #f44336;
        }
        
        .print-btn:hover {
            opacity: 0.9;
        }
        
        /* PRINT STYLES - Critical for correct sticker printing */
        @media print {
            @page {
                size: 76mm 50mm;
                margin: 0;
            }
            
            html, body {
                width: 76mm;
                height: 50mm;
                margin: 0;
                padding: 0;
                background: white;
            }
            
            body {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .no-print {
                display: none;
            }
            
            .sticker {
                width: 76mm;
                height: 50mm;
                border: none; /* Remove border for actual printing */
                box-shadow: none;
                padding: 3mm;
                margin: 0;
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            
            /* If printing multiple stickers */
            .sticker-container {
                width: 76mm;
                height: 50mm;
                margin: 0;
                padding: 0;
            }
            
            /* For multiple stickers on roll (each new page) */
            .sticker {
                page-break-after: always;
            }
        }
        
        /* For printing multiple stickers at once */
        .sticker-container.multiple {
            width: 100%;
        }
        
        .sticker-container.multiple .sticker {
            margin-bottom: 2mm;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Sticker</button>
        <button class="print-btn close" onclick="window.close()">✖️ Close</button>
    </div>

    <div class="sticker-container {{ isset($multiple) && $multiple ? 'multiple' : '' }}">
        <div class="sticker">
            <div class="header">
                MAINTENANCE
            </div>
            
            <div class="content">
                <table>
                    <tr>
                        <td class="label">Date:</td>
                        <td class="value">{{ $sticker['date']->format('d M y') }}</td>
                    </tr>
                    <tr>
                        <td class="label">Bike:</td>
                        <td class="value">{{ $sticker['bike'] }}</td>
                    </tr>
                        <tr>
                            <td class="label">Current Reading:</td>
                            <td class="value">{{ number_format($sticker['current_reading']) }} km</td>
                        </tr>
                        <tr>
                            <td class="label">Interval:</td>
                            <td class="value">{{ number_format($sticker['next_reading'] - $sticker['current_reading']) }} km</td>
                        </tr>
                        </table>
                
                <div class="next-service">
                    Next Service At: {{ number_format($sticker['next_reading']) }} km
                </div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // setTimeout(function() {
            //     window.print();
            // }, 500);
        };
    </script>
</body>
</html>