@php
$settings = company_table('settings')->pluck('value', 'name')->toArray();
$voucherBrand = $brand ?? ($companyBrand ?? []);
$voucherPrimary = $voucherBrand['primary_color'] ?? '#2563eb';
$voucherSecondary = $voucherBrand['secondary_color'] ?? '#1e3a8a';
@endphp
<table width="100%" style="font-family: sans-serif;">
    <tr>
        <td colspan="3" style="height: 4px; background: {{ $voucherPrimary }}; padding: 0; line-height: 0; font-size: 0;">&nbsp;</td>
    </tr>
    <tr>
        <td width="33.33%"><img src="{{ $companyLogoUrl ?? URL::asset('assets/img/logo-full.png') }}" width="150" /></td>
        <td width="33.33%" style="text-align: center;">
            <h4 style="margin-bottom: 10px;margin-top: 5px;font-size: 14px; color: {{ $voucherSecondary }};">{{$settings['company_name'] ?? ''}}</h4>
            <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;">{{$settings['company_address'] ?? ''}}</p>
            <p style="margin-bottom: 5px;font-size: 14px;margin-top: 5px;"> TRN {{$settings['vat_number'] ?? ''}}</p>
        <td width="33.33%" style="text-align: right;"></td>
    </tr>

    <tr style="text-align: center;">
        <td colspan="3">
            <h4 style="margin-bottom: 15px;margin-top: 25px;font-size: 14px;border-bottom: 2px solid {{ $voucherPrimary }};color: {{ $voucherSecondary }};padding: 7px 0px;"> @isset($voucher_type){{$voucher_type}}@endisset</h4>
        </td>
    </tr>

</table>