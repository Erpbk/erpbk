@php
  $companySlug = request()->route('company_slug');
  $today = date('Y-m-d');
  $contracts = collect($agreements ?? [])->map(function ($agreement) {
    $template = $agreement->contractTemplate();
    return [
      'id' => $agreement->id,
      'name' => $agreement->name,
      'ready' => $template !== null,
      'template_id' => $template?->id,
    ];
  });
  $routeBase = ['company_slug' => $companySlug, 'module' => $module, 'record' => $record];
@endphp

<div class="contracts-modal" id="module-contracts-modal"
  data-company-slug="{{ $companySlug }}"
  data-module="{{ $module }}"
  data-record-id="{{ $record }}"
  data-record-name="{{ e($meta['name'] ?? '') }}"
  data-record-email="{{ $meta['email'] ?? '' }}"
  data-agreement-date="{{ $today }}"
  data-pdf-url="{{ route('module-contracts.pdf', $routeBase) }}"
  data-email-url="{{ route('module-contracts.email', $routeBase) }}"
  data-preview-url="{{ route('module-contracts.preview', $routeBase) }}"
  data-edit-template-base="{{ url('/app/' . $companySlug . '/' . $module . '/agreements/templates') }}">

  <div id="contract-list-view">
    <p class="text-muted small mb-3">
      Select a contract for <strong>{{ $meta['name'] ?? '' }}</strong> ({{ $meta['code'] ?? '' }}).
    </p>

    @if($contracts->where('ready', true)->isNotEmpty())
    <div class="row g-2">
      @foreach($contracts->where('ready', true) as $contract)
      <div class="col-sm-6">
        <button type="button"
          class="btn btn-outline-primary w-100 text-start contract-pick-btn py-3"
          data-contract-id="{{ $contract['id'] }}"
          data-contract-name="{{ e($contract['name']) }}"
          data-template-id="{{ $contract['template_id'] }}">
          <i class="ti ti-file-certificate me-2"></i>
          <span class="fw-semibold">{{ $contract['name'] }}</span>
        </button>
      </div>
      @endforeach
    </div>
    @endif

    @if($contracts->where('ready', false)->isNotEmpty())
    <div class="mt-3">
      <p class="text-muted small mb-2">Not available (template not assigned in Settings):</p>
      @foreach($contracts->where('ready', false) as $contract)
      <div class="border rounded px-3 py-2 mb-2 text-muted small">
        <i class="ti ti-alert-circle me-1"></i> {{ $contract['name'] }}
      </div>
      @endforeach
    </div>
    @endif

    @if($contracts->isEmpty())
    <div class="alert alert-info mb-0 py-2 small">
      No contracts are assigned to {{ $moduleLabel ?? $module }}. Configure them in Documents → Agreements.
    </div>
    @endif
  </div>

  <div id="contract-actions-view" class="d-none">
    <button type="button" class="btn btn-link btn-sm text-muted px-0 mb-2" id="contract-back-btn">
      <i class="ti ti-arrow-left me-1"></i> All contracts
    </button>

    <h5 class="mb-1" id="contract-actions-title"></h5>
    <p class="text-muted small mb-3">Contract date: {{ \Carbon\Carbon::parse($today)->format('d M Y') }}</p>

    <div class="d-grid gap-2 mb-3">
      @canany(['agreements_generate', 'agreements_view', 'gn_settings'])
      <button type="button" class="btn btn-primary" id="contract-download-btn">
        <i class="ti ti-download me-1"></i> Download Contract
      </button>
      <button type="button" class="btn btn-outline-info btn-sm" id="contract-preview-btn">
        <i class="ti ti-eye me-1"></i> Preview
      </button>
      <button type="button" class="btn btn-outline-success" id="contract-email-toggle-btn">
        <i class="ti ti-mail me-1"></i> Email Contract
      </button>
      @endcanany
      @canany(['agreements_edit', 'agreements_manage_templates', 'gn_settings'])
      <a href="#" class="btn btn-outline-primary btn-sm d-none" id="contract-edit-template-btn" target="_blank">
        <i class="ti ti-edit me-1"></i> Edit template
      </a>
      @endcanany
    </div>

    <div id="contract-email-panel" class="d-none border rounded p-3 bg-light">
      <label class="form-label small mb-1">Send to</label>
      <input type="email" id="contract-email-to" class="form-control form-control-sm mb-2"
        value="{{ $meta['email'] ?? '' }}" placeholder="recipient@example.com">
      <label class="form-label small mb-1">Message <span class="text-muted">(optional)</span></label>
      <textarea id="contract-email-message" class="form-control form-control-sm mb-2" rows="2"
        placeholder="Optional message"></textarea>
      <button type="button" class="btn btn-success btn-sm w-100" id="contract-email-send-btn">
        <i class="ti ti-send me-1"></i> Send contract
      </button>
    </div>
  </div>
</div>

<script>
(function() {
  var root = document.getElementById('module-contracts-modal');
  if (!root) return;

  var csrfToken = @json(csrf_token());
  var recordName = root.getAttribute('data-record-name') || '';
  var defaultEmail = root.getAttribute('data-record-email') || '';
  var agreementDate = root.getAttribute('data-agreement-date') || '';
  var pdfBase = root.getAttribute('data-pdf-url') || '';
  var emailUrl = root.getAttribute('data-email-url') || '';
  var previewBase = root.getAttribute('data-preview-url') || '';
  var editBase = root.getAttribute('data-edit-template-base') || '';
  var module = root.getAttribute('data-module') || '';

  var listView = document.getElementById('contract-list-view');
  var actionsView = document.getElementById('contract-actions-view');
  var actionsTitle = document.getElementById('contract-actions-title');
  var emailPanel = document.getElementById('contract-email-panel');
  var emailTo = document.getElementById('contract-email-to');
  var emailMessage = document.getElementById('contract-email-message');
  var editBtn = document.getElementById('contract-edit-template-btn');

  var selected = { name: '', templateId: '' };

  function queryParams() {
    return 'template_id=' + encodeURIComponent(selected.templateId) +
      '&agreement_date=' + encodeURIComponent(agreementDate);
  }

  function showList() {
    listView.classList.remove('d-none');
    actionsView.classList.add('d-none');
    emailPanel.classList.add('d-none');
    selected = { name: '', templateId: '' };
  }

  function showActions(name, templateId) {
    selected = { name: name, templateId: templateId };
    actionsTitle.textContent = name;
    listView.classList.add('d-none');
    actionsView.classList.remove('d-none');
    emailPanel.classList.add('d-none');
    if (emailTo && !emailTo.value) emailTo.value = defaultEmail;
    if (editBtn && templateId) {
      editBtn.href = editBase + '/' + encodeURIComponent(templateId) + '/edit';
      editBtn.classList.remove('d-none');
    }
  }

  document.querySelectorAll('.contract-pick-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
      showActions(btn.getAttribute('data-contract-name'), btn.getAttribute('data-template-id'));
    });
  });

  document.getElementById('contract-back-btn').addEventListener('click', showList);

  var downloadBtn = document.getElementById('contract-download-btn');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', function() {
      if (!selected.templateId) return;
      window.location.href = pdfBase + '?' + queryParams() + '&download=1';
    });
  }

  var previewBtn = document.getElementById('contract-preview-btn');
  if (previewBtn) {
    previewBtn.addEventListener('click', function() {
      if (!selected.templateId) return;
      window.open(previewBase + '?' + queryParams(), '_blank');
    });
  }

  var emailToggle = document.getElementById('contract-email-toggle-btn');
  if (emailToggle) {
    emailToggle.addEventListener('click', function() {
      emailPanel.classList.toggle('d-none');
    });
  }

  var sendBtn = document.getElementById('contract-email-send-btn');
  if (sendBtn) {
    sendBtn.addEventListener('click', function() {
      if (!selected.templateId) return;
      var to = emailTo ? emailTo.value.trim() : '';
      if (!to) { alert('Please enter a recipient email address.'); return; }

      var label = sendBtn.innerHTML;
      sendBtn.disabled = true;
      sendBtn.innerHTML = 'Sending…';

      var formData = new FormData();
      formData.append('_token', csrfToken);
      formData.append('template_id', selected.templateId);
      formData.append('agreement_date', agreementDate);
      formData.append('email_to', to);
      formData.append('email_subject', selected.name + ' — ' + recordName);
      formData.append('email_message', emailMessage ? emailMessage.value : '');

      fetch(emailUrl, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
      })
        .then(function(res) {
          return res.json().then(function(data) { return { ok: res.ok, data: data }; })
            .catch(function() { return { ok: false, data: { message: 'Unexpected server response.' } }; });
        })
        .then(function(result) {
          sendBtn.disabled = false;
          sendBtn.innerHTML = label;
          if (result.ok && result.data && result.data.success) {
            alert(result.data.message || 'Contract emailed successfully.');
            emailPanel.classList.add('d-none');
            return;
          }
          alert((result.data && result.data.message) ? result.data.message : 'Failed to send email.');
        })
        .catch(function() {
          sendBtn.disabled = false;
          sendBtn.innerHTML = label;
          alert('Failed to send email. Check your connection and try again.');
        });
    });
  }
})();
</script>
