{{--
  Company bank accounts for a document kind (invoice | payment | customer_note),
  optionally scoped to an ERP module (e.g. Customers, Cash & Banks) — same list as Stamp & Signature.
  Optional: $companyBankAccounts, $companyBankModule, $companyBankErpModule, $companyBankBranchId, $companyBankTitle
--}}
@php
    $companyBankModule = $companyBankModule ?? 'invoice';
    $companyBankTitle = $companyBankTitle ?? 'Bank Details';
    $companyBankBranchId = $companyBankBranchId ?? null;
    $companyBankErpModule = $companyBankErpModule ?? null;
    if (! isset($companyBankAccounts)) {
        $company = request()->attributes->get('company');
        if (! $company) {
            $companyId = \App\Support\CompanyContext::id();
            $company = $companyId ? \App\Models\Company::query()->find($companyId) : null;
        }
        $companyBankAccounts = $company
            ? app(\App\Services\Company\CompanyDetailsService::class)
                ->banksForModule(
                    $company,
                    $companyBankModule,
                    $companyBankBranchId ? (int) $companyBankBranchId : null,
                    $companyBankErpModule
                )
            : [];
    }
@endphp
@if(!empty($companyBankAccounts))
<div class="company-bank-accounts" style="margin-top:14px;padding:12px 14px;border:1px solid #e3e6f0;border-radius:10px;background:#f7f8fc;font-size:12.5px;color:#121a33">
    <div style="font-weight:700;font-size:12px;letter-spacing:.04em;text-transform:uppercase;color:#5d6883;margin-bottom:8px">{{ $companyBankTitle }}</div>
    <div style="display:grid;gap:10px">
        @foreach($companyBankAccounts as $bank)
        <div style="display:grid;gap:2px;line-height:1.45">
            <div style="font-weight:700">{{ $bank['bank'] ?: 'Bank account' }}@if(!empty($bank['cur'])) <span style="font-weight:500;color:#5d6883">({{ $bank['cur'] }})</span>@endif</div>
            @if(!empty($bank['title']))<div><span style="color:#5d6883">Account title:</span> {{ $bank['title'] }}</div>@endif
            @if(!empty($bank['iban']))<div><span style="color:#5d6883">IBAN:</span> <span style="font-family:ui-monospace,Menlo,Consolas,monospace">{{ $bank['iban'] }}</span></div>@endif
            @if(!empty($bank['acc']))<div><span style="color:#5d6883">Account number:</span> <span style="font-family:ui-monospace,Menlo,Consolas,monospace">{{ $bank['acc'] }}</span></div>@endif
            @if(!empty($bank['swift']))<div><span style="color:#5d6883">SWIFT / BIC:</span> <span style="font-family:ui-monospace,Menlo,Consolas,monospace">{{ $bank['swift'] }}</span></div>@endif
        </div>
        @endforeach
    </div>
</div>
@endif