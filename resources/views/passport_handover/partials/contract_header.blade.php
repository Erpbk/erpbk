@php
$companyName = $branding['company_name'] ?? config('app.name', 'Company');
@endphp
<div class="doc-top-band">
    <table>
        <tr>
            <td style="width: 120px;">
                @if(!empty($branding['logo_src']))
                <img src="{{ $branding['logo_src'] }}" alt="{{ $companyName }}" class="company-logo">
                @elseif(!empty($branding['logo_url']))
                <img src="{{ $branding['logo_url'] }}" alt="{{ $companyName }}" class="company-logo">
                @else
                <div class="logo-fallback">{{ strtoupper(substr($companyName, 0, 1)) }}</div>
                @endif
            </td>
            <td style="padding-left: 16px;">
                <h2 class="company-title">{{ $companyName }}</h2>
                <p class="company-meta">
                    @if(!empty($branding['address'])){{ $branding['address'] }}<br>@endif
                    @if(!empty($branding['location_line'])){{ $branding['location_line'] }}<br>@endif
                    @if(!empty($branding['phone']))Tel: {{ $branding['phone'] }}@endif
                    @if(!empty($branding['phone']) && !empty($branding['email'])) · @endif
                    @if(!empty($branding['email'])){{ $branding['email'] }}@endif
                </p>
            </td>
            <td style="width: 210px;">
                <div class="doc-badge">
                    <strong>Ref: {{ $docRef }}</strong>
                    {{ $docDateLabel }}<br>
                    {{ $docDateValue }}
                </div>
            </td>
        </tr>
    </table>
</div>
<div class="accent-line"></div>
