@php
    $companyName = $settings['company_name'] ?? '';
@endphp
<div class="foot">
    <div>
        @if($companyName !== '')
            <strong>{{ ucwords($companyName) }}</strong>
        @endif
        <span class="thanks"> — Thank you for your business.</span>
    </div>
</div>
