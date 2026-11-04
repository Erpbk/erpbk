{{--
  DomPDF-safe inline style map for download/PDF renders.
  Included from tax_invoice_styles so every invoice show view can use $invPdf[...].
  Screen/print leaves these empty so class-based CSS remains the source of truth.
--}}
@php
$__invBrand = $brand ?? ($companyBrand ?? []);
$__invBlue = $__invBrand['primary_color'] ?? '#004aad';
$__invSoft = $__invBrand['primary_soft'] ?? ($__invBrand['primary_light'] ?? '#eef4fc');
$__invLine = $__invBrand['border_color'] ?? '#c5d8f0';
$__isPdf = !empty($isPdf);

if ($__isPdf) {
    $invPdf = [
        'box' => 'max-width:100%;width:100%;margin:0;background:#ffffff;overflow:hidden;font-family:DejaVu Sans,Arial,Helvetica,sans-serif;color:#0f172a;font-size:10px;line-height:1.4;',
        'sheet' => 'padding:8px 6px;',
        'parties' => 'display:table;width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0;margin:0 0 12px 0;',
        'party' => 'display:table-cell;width:50%;vertical-align:top;background-color:' . $__invSoft . ';border:1px solid ' . $__invLine . ';padding:10px 12px;',
        'partyFirst' => 'display:table-cell;width:50%;vertical-align:top;background-color:' . $__invSoft . ';border:1px solid ' . $__invLine . ';border-right:10px solid #ffffff;padding:10px 12px;',
        'partyAlt' => 'display:table-cell;width:50%;vertical-align:top;background-color:' . $__invSoft . ';border:1px solid ' . $__invLine . ';padding:10px 12px;',
        'partyTitle' => 'display:table;width:100%;margin:0 0 8px 0;font-size:8.5px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:' . $__invBlue . ';text-align:left;white-space:nowrap;padding:0;',
        'partyName' => 'margin:0 0 8px 0;padding:0;font-size:13px;font-weight:700;color:#0f172a;text-align:left;line-height:1.35;',
        'partyLine' => 'display:table;width:100%;table-layout:fixed;margin:0 0 4px 0;font-size:9.5px;line-height:1.5;',
        'partyK' => 'display:table-cell;width:88px;color:#64748b;font-weight:500;text-align:left;padding-right:10px;white-space:nowrap;',
        'partyV' => 'display:table-cell;color:#0f172a;font-weight:700;text-align:right;word-break:break-word;',
        'desc' => 'margin:0 0 10px 0;padding:8px 10px;border:1px solid ' . $__invLine . ';background-color:' . $__invSoft . ';text-align:left;',
        'descTitle' => 'display:block;font-size:8.5px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:' . $__invBlue . ';margin:0 0 4px 0;padding:0;text-align:left;',
        'descBody' => 'margin:0;padding:0;color:#334155;font-size:10px;text-align:left;',
        'totalsArea' => 'display:table;width:100%;table-layout:fixed;border-collapse:separate;border-spacing:0;margin:8px 0;',
        'totalsNotes' => 'display:table-cell;width:55%;vertical-align:top;padding-right:12px;',
        'totalsCol' => 'display:table-cell;width:45%;vertical-align:top;',
        'noteBox' => 'background-color:' . $__invSoft . ';border:1px solid ' . $__invLine . ';border-top:3px solid ' . $__invBlue . ';padding:8px 10px;box-sizing:border-box;height:100%;text-align:left;',
        'noteTitle' => 'font-size:8.5px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:' . $__invBlue . ';margin:0 0 6px 0;padding:0;text-align:left;',
        'noteBody' => 'font-size:9.5px;color:#64748b;line-height:1.55;padding:0;text-align:left;',
        'totalsTable' => 'width:100%;display:block;',
        'totalsRow' => 'display:table;width:100%;table-layout:fixed;padding:5px 8px;font-size:9.5px;border-bottom:1px solid #e2e8f0;box-sizing:border-box;',
        'totalsRowLast' => 'display:table;width:100%;table-layout:fixed;padding:5px 8px;font-size:9.5px;border-bottom:none;box-sizing:border-box;',
        'totalsRowGrand' => 'display:table;width:100%;table-layout:fixed;padding:6px 0;border-bottom:none;box-sizing:border-box;',
        'totalsK' => 'display:table-cell;color:#64748b;font-weight:500;text-align:left;vertical-align:middle;',
        'totalsV' => 'display:table-cell;color:#0f172a;font-weight:700;text-align:right;vertical-align:middle;',
        'grand' => 'display:table;width:100%;table-layout:fixed;background-color:' . $__invBlue . ';padding:8px 10px;box-sizing:border-box;',
        'grandK' => 'display:table-cell;font-size:9.5px;font-weight:700;text-transform:uppercase;text-align:left;color:#ffffff;vertical-align:middle;',
        'grandV' => 'display:table-cell;font-size:13px;font-weight:800;text-align:right;color:#ffffff;vertical-align:middle;',
        'hdr' => 'width:100%;border-collapse:collapse;margin:0 0 12px 0;border-bottom:2.5px solid ' . $__invBlue . ';',
        'badge' => 'display:inline-block;background-color:' . $__invBlue . ';color:#ffffff;font-size:10px;font-weight:700;letter-spacing:1px;text-transform:uppercase;padding:6px 12px;margin:0 0 10px 0;',
        'company' => 'font-size:13px;font-weight:700;color:' . $__invBlue . ';margin:0 0 5px 0;text-align:center;',
        'meta' => 'font-size:8.5px;color:#64748b;line-height:1.5;text-align:center;',
        'th' => 'background-color:' . $__invSoft . ';color:#0f172a;font-size:8.5px;font-weight:700;letter-spacing:0.3px;text-transform:uppercase;padding:6px 5px;border:none;border-bottom:1px solid ' . $__invLine . ';text-align:center;',
        'hide' => 'display:none;',
    ];
} else {
    $invPdf = [];
}
@endphp
