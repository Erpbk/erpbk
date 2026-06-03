@isset($rider)
<div class="mb-3">
  <label class="form-label fw-semibold">Agreement documents</label>
  <div class="d-flex flex-wrap gap-2 mb-2">
    @forelse($agreements ?? [] as $agreement)
    <a href="{{ route('agreements.modal', ['company_slug' => request()->route('company_slug'), 'riderId' => $rider->id, 'category' => $agreement->slug]) }}"
      class="btn btn-primary btn-sm agreement-modal-trigger"
      data-modal-title="{{ $agreement->name }}">
      <i class="ti ti-file-text me-1"></i> Generate {{ $agreement->name }}
    </a>
    @empty
    <span class="text-muted small">No agreements are assigned to this module.</span>
    @endforelse
    <a href="{{ route('rider.contract', ['company_slug' => request()->route('company_slug'), 'id' => $rider->id]) }}"
      class="btn btn-warning btn-sm" target="_blank">
      <i class="fas fa-file"></i> Legacy Contract
    </a>
  </div>
</div>

<div id="agreement-generate-container" class="d-none border-top pt-3 mt-2"></div>

@if(!empty($rider->contract))
<a href="{{ storage_url('app/contract/'.$rider->contract) }}" class="btn btn-success btn-xs mr-1 mb-2" target="_blank">
  <i class="fas fa-file"></i>&nbsp; Signed Contract (uploaded)
</a>
@endif

<form action="{{ route('rider_contract_upload', ['company_slug' => request()->route('company_slug'), 'id' => $rider->id]) }}" method="post" enctype="multipart/form-data">
  @csrf
  <div class="row mt-3">
    <div class="col-md-12">
      <div class="form-group">
        <label><b>Upload Signed Contract File</b></label>
        <input name="contract" class="form-control" type="file">
      </div>
    </div>
  </div>
  <div class="modal-footer1 mt-3">
    <button type="submit" class="save_rec btn btn-primary save_rec">Upload</button>
  </div>
</form>

<script>
  (function() {
    function initAgreementGeneratePanel() {
      var panel = document.getElementById('agreement-generate-panel');
      if (!panel || panel.getAttribute('data-agreement-bound') === '1') {
        return;
      }
      panel.setAttribute('data-agreement-bound', '1');

      var slug = panel.getAttribute('data-company-slug') || '';
      var riderId = panel.getAttribute('data-rider-id') || '';
      var select = document.getElementById('agreement_template_select');
      var dateInput = document.getElementById('agreement_date_input');
      if (!select || !dateInput) {
        return;
      }

      function syncTemplateId() {
        var tid = select.value;
        var els = document.querySelectorAll('.agreement-email-template-id');
        for (var i = 0; i < els.length; i++) {
          els[i].value = tid;
        }
      }

      function syncDate() {
        var els = document.querySelectorAll('.agreement-email-date');
        for (var i = 0; i < els.length; i++) {
          els[i].value = dateInput.value;
        }
      }

      function syncEditUrl() {
        var editLink = document.getElementById('agreement-edit-content-link');
        if (!editLink) {
          return;
        }
        if (!select || !select.selectedOptions || !select.selectedOptions.length) {
          return;
        }
        var opt = select.selectedOptions[0];
        var url = opt.getAttribute('data-edit-url');
        if (url) {
          editLink.href = url;
        }
      }

      function buildUrl(isPdf) {
        var params = 'template_id=' + encodeURIComponent(select.value) +
          '&agreement_date=' + encodeURIComponent(dateInput.value);
        if (isPdf) {
          params += '&download=1';
        }
        var base = @json(url('/app')) + '/' + encodeURIComponent(slug) + '/riders/' + encodeURIComponent(riderId) + '/agreements/';
        return base + (isPdf ? 'pdf?' : 'preview?') + params;
      }

      select.addEventListener('change', function() {
        syncTemplateId();
        syncEditUrl();
      });
      dateInput.addEventListener('change', syncDate);

      syncEditUrl();

      var btnPreview = document.getElementById('btn-agreement-preview');
      var btnPdf = document.getElementById('btn-agreement-pdf');
      var btnPrint = document.getElementById('btn-agreement-print');
      var emailForm = document.getElementById('agreement-email-form');

      if (btnPreview) {
        btnPreview.addEventListener('click', function() {
          if (!select.value) {
            alert('Please select a template.');
            return;
          }
          window.open(buildUrl(false), '_blank');
        });
      }
      if (btnPdf) {
        btnPdf.addEventListener('click', function() {
          if (!select.value) {
            alert('Please select a template.');
            return;
          }
          window.location.href = buildUrl(true);
        });
      }
      if (btnPrint) {
        btnPrint.addEventListener('click', function() {
          if (!select.value) {
            alert('Please select a template.');
            return;
          }
          var w = window.open(buildUrl(false), '_blank');
          if (w) {
            w.onload = function() {
              w.print();
            };
          }
        });
      }
      if (emailForm) {
        emailForm.addEventListener('submit', function(e) {
          e.preventDefault();
          syncTemplateId();
          syncDate();

          if (!select || !select.value) {
            alert('Please select an agreement template before sending email.');
            return;
          }

          var toInput = emailForm.querySelector('input[name="email_to"]');
          if (!toInput || !toInput.value.trim()) {
            alert('Please enter a recipient email address.');
            return;
          }

          var submitBtn = emailForm.querySelector('button[type="submit"]');
          var btnLabel = submitBtn ? submitBtn.innerHTML : '';
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'Sending…';
          }

          var formData = new FormData(emailForm);

          fetch(emailForm.action, {
              method: 'POST',
              body: formData,
              headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              },
              credentials: 'same-origin'
            })
            .then(function(response) {
              return response.json().then(function(data) {
                return {
                  ok: response.ok,
                  data: data
                };
              }).catch(function() {
                return {
                  ok: false,
                  data: {
                    success: false,
                    message: 'Unexpected server response.'
                  }
                };
              });
            })
            .then(function(result) {
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnLabel;
              }
              if (result.ok && result.data && result.data.success) {
                alert(result.data.message || 'Email sent successfully.');
                return;
              }
              var errMsg = (result.data && result.data.message) ? result.data.message : 'Failed to send email.';
              if (result.data && result.data.errors) {
                var firstKey = Object.keys(result.data.errors)[0];
                if (firstKey && result.data.errors[firstKey][0]) {
                  errMsg = result.data.errors[firstKey][0];
                }
              }
              alert(errMsg);
            })
            .catch(function() {
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = btnLabel;
              }
              alert('Failed to send email. Check your connection and try again.');
            });
        });
      }
    }

    document.querySelectorAll('.agreement-modal-trigger').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        var container = document.getElementById('agreement-generate-container');
        if (!container) {
          return;
        }
        container.classList.remove('d-none');
        if (window.jQuery) {
          container.innerHTML = '<p class="text-muted small py-2">Loading…</p>';
          jQuery(container).load(link.href, function() {
            initAgreementGeneratePanel();
          });
        } else {
          fetch(link.href, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(function(r) {
              return r.text();
            })
            .then(function(html) {
              container.innerHTML = html;
              initAgreementGeneratePanel();
            });
        }
      });
    });
  })();
</script>
@endisset