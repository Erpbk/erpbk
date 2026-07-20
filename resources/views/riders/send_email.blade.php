@php
$companyName = $emailBranding['name']
?? ($companyDisplayName ?? (view()->shared('currentCompany')?->name ?? config('app.name')));
$defaultSubject = 'Rider ID: ' . $rider->rider_id . ' - ' . $rider->name;
$defaultHeading = 'Warning for Attendance and Performance Rider ID: ' . $rider->rider_id;
$assignedEmailAccounts = $assignedEmailAccounts ?? collect();
$defaultCcEmails = $defaultCcEmails ?? [];
$singleAccount = $assignedEmailAccounts->count() === 1 ? $assignedEmailAccounts->first() : null;
@endphp

@if($assignedEmailAccounts->isEmpty())
<div class="alert alert-warning mb-3">
    No active email account is assigned to you. Ask an administrator to create an email account in Settings → Email Accounts and assign it to your user.
</div>
@else
<form action="{{ route('rider.sendemail', ['company_slug' => request()->route('company_slug'), 'id' => $rider->id]) }}" method="POST" id="formajax">
    @csrf

    @if($singleAccount)
    <input type="hidden" name="email_account_id" value="{{ $singleAccount->id }}">
    <div class="col-md-12 form-group mb-3">
        <label>From</label>
        <input type="text" class="form-control" value="{{ $singleAccount->displayLabel() }}" readonly>
    </div>
    @else
    <div class="col-md-12 form-group mb-3">
        <label class="required">From</label>
        <select name="email_account_id" class="form-select" required>
            <option value="">Select sender email</option>
            @foreach($assignedEmailAccounts as $account)
            <option value="{{ $account->id }}" {{ (int) old('email_account_id') === (int) $account->id ? 'selected' : '' }}>
                {{ $account->displayLabel() }}
            </option>
            @endforeach
        </select>
    </div>
    @endif

    <div class="col-md-12 form-group mb-3">
        <label class="required">To</label>
        <input type="email" class="form-control" name="email_to" value="{{ old('email_to', $rider->email) }}" readonly>
    </div>

    <div class="col-md-12 form-group mb-3">
        <label>CC</label>
        <div class="email-cc-tags border rounded p-2 @error('email_cc') border-danger @enderror @error('email_cc.*') border-danger @enderror" id="rider-email-cc-tags">
            <div class="d-flex flex-wrap gap-1 mb-2" id="rider-email-cc-chip-list"></div>
            <input type="text" class="form-control border-0 shadow-none p-0" id="rider-email-cc-input" placeholder="Type email and press Enter">
        </div>
        <div id="rider-email-cc-hidden-inputs"></div>
        <div class="form-text">Add multiple CC recipients. Press Enter or comma to add each address.</div>
        @error('email_cc')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        @error('email_cc.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-12 form-group mb-3">
        <label class="required">Subject</label>
        <input type="text" class="form-control" name="email_subject" value="{{ old('email_subject', $defaultSubject) }}" required>
        <input type="hidden" name="email_heading" value="{{ old('email_heading', $defaultHeading) }}">
    </div>

    <div class="col-md-12 form-group mb-3">
        <label class="required">Message</label>
        <textarea name="email_message" rows="8" class="form-control" required>{{ old('email_message', "Hi {$rider->name},

Rider I,D : {$rider->rider_id}
Employee Name : {$rider->name}

I hope you're doing well.
We need to address some important issues regarding your attendance and performance in " . date('M Y') . ". We've noticed that you have been absent several times without prior notice. Additionally, your performance as a bike rider has not met the company’s standards. Specifically, you have been late logging in, and your on-time delivery rate has been below expectations.

As per company guidelines, we expect 26 perfect attendance days and at least 90% on-time delivery. Unfortunately, these targets were not met.
This is a formal warning. If your attendance and performance do not improve immediately, we may need to take further action, including ending your employment According to UAE labor law.
If there are any challenges affecting your work, please speak with your Fleet Supervisor or HR. We are here to support you.
We expect to see improvement starting right away.

Best regards,
{$companyName}
") }}</textarea>
    </div>

    <div class="col-md-6 form-group mb-3">
        <label>Activity Attachment Month</label>
        <input type="month" name="month" value="{{ request('month') ?? date('Y-m') }}" class="form-control" />
    </div>

    <button type="submit" class="btn btn-primary pull-right mt-3">Send Email</button>
</form>

<script>
(function () {
    const initialCc = @json(array_values(array_unique(array_merge($defaultCcEmails, (array) old('email_cc', [])))));
    const chipList = document.getElementById('rider-email-cc-chip-list');
    const hiddenInputs = document.getElementById('rider-email-cc-hidden-inputs');
    const ccInput = document.getElementById('rider-email-cc-input');
    const emails = [];

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function renderCc() {
        chipList.innerHTML = '';
        hiddenInputs.innerHTML = '';
        emails.forEach(function (email) {
            const chip = document.createElement('span');
            chip.className = 'badge bg-label-primary d-inline-flex align-items-center gap-1';
            chip.innerHTML = email + ' <button type="button" class="btn btn-sm btn-link p-0 text-danger" aria-label="Remove">&times;</button>';
            chip.querySelector('button').addEventListener('click', function () {
                const index = emails.indexOf(email);
                if (index >= 0) emails.splice(index, 1);
                renderCc();
            });
            chipList.appendChild(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'email_cc[]';
            input.value = email;
            hiddenInputs.appendChild(input);
        });
    }

    function addEmail(raw) {
        const value = String(raw || '').trim().replace(/,+$/, '');
        if (!value) return;
        if (!isValidEmail(value)) {
            if (window.toastr) toastr.error('Invalid email address: ' + value);
            return;
        }
        const normalized = value.toLowerCase();
        if (!emails.includes(normalized)) {
            emails.push(normalized);
            renderCc();
        }
        ccInput.value = '';
    }

    ccInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            addEmail(ccInput.value);
        }
    });

    ccInput.addEventListener('blur', function () {
        if (ccInput.value.trim() !== '') {
            addEmail(ccInput.value);
        }
    });

    initialCc.forEach(addEmail);
})();
</script>
@endif
