@if(!empty($companyLogoUrl))
<img src="{{ $companyLogoUrl }}" height="{{ $height ?? 20 }}" alt="" style="object-fit: contain;" />
@else
<img src="{{ asset('assets/img/fleetvalue.png') }}" height="{{ $height ?? 20 }}" alt="" style="object-fit: contain;" />

@endif