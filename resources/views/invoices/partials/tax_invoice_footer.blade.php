@php
    $settings = $settings ?? [];
@endphp
<footer class="foot">
    <span class="thanks">Thank you for your business.</span>
    <span>
        Queries:
        <strong>{{ $settings['company_phone'] ?? '—' }}</strong>
        @if(!empty($settings['company_email']))
        &nbsp;·&nbsp; <strong>{{ $settings['company_email'] }}</strong>
        @endif
    </span>
</footer>
