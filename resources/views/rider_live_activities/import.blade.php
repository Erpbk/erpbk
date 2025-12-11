@php
$successMessage = session('success');
$importSummary = session('activities_import_summary');
$validationErrors = $errors ? $errors->all() : [];
$formAction = $formAction ?? route('rider.live_activities_import');
$errorsRoute = $errorsRoute ?? route('rider.live_activities_import_errors', ['type' => 'noon']);
@endphp

<form
  action="{{ $formAction }}"
  method="POST"
  enctype="multipart/form-data"
  id="formajax"
  data-errors-route="{{ $errorsRoute }}"
  data-success-message="{{ $successMessage }}"
  data-summary='@json($importSummary)'
  data-validation-errors='@json($validationErrors)'>
  @csrf
  <div class="row">
    <div class="col-12">
      @if(!empty($importTypeLabel))
      <h5 class="text-primary mb-3">{{ $importTypeLabel }}</h5>
      @endif
      @if(!empty($sampleDownloadUrl))
      <a href="{{ $sampleDownloadUrl }}" class="text-success w-100" download="{{ $sampleDownloadLabel ?? 'Rider Activities Sample' }}">
        <i class="fa fa-file-download text-success"></i> &nbsp; {{ $sampleDownloadLabel ?? 'Download Sample File' }}
      </a>
      @endif
      <p class="text-muted mt-2">
        <small>Note: The file should have headers with date, rider_id, payout_type, and other activity fields. See sample file for format.</small>
      </p>
      <div class="alert alert-info mt-2">
        <strong>⚠️ Important:</strong> Only records with today's date ({{ $currentDate ?? date('Y-m-d') }}) will be imported or updated. 
        Records with other dates will be skipped. For each day, you can import multiple times to update the same day's records.
      </div>
    </div>
    <div class="col-12 mt-3 mb-3">
      <label class="mb-3 pl-2">Select file</label>
      <input type="file" name="file" class="form-control mb-3" style="height: 40px;" accept=".csv,.xlsx,.xls" />
    </div>
  </div>
  <button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Start Import</button>
  <a href="{{ $errorsRoute }}" class="btn btn-info" style="width: 100%; margin-top: 10px;">Check Last Import Errors</a>
</form>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const formElement = document.getElementById('formajax');
    const errorsRoute = formElement ? formElement.dataset.errorsRoute : null;
    const successMessage = formElement ? formElement.dataset.successMessage : '';
    let summary = null;
    let validationErrors = [];

    if (formElement && formElement.dataset.summary) {
      try {
        summary = JSON.parse(formElement.dataset.summary);
      } catch (error) {
        summary = null;
      }
    }

    if (formElement && formElement.dataset.validationErrors) {
      try {
        validationErrors = JSON.parse(formElement.dataset.validationErrors) || [];
      } catch (error) {
        validationErrors = [];
      }
    }

    const escapeHtml = (value) => {
      if (value === null || value === undefined) {
        return '';
      }
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };

    if (successMessage) {
      Swal.fire({
        icon: 'success',
        title: 'Import Successful',
        text: successMessage,
        confirmButtonText: 'OK',
        confirmButtonColor: '#28a745'
      });
    }

    if (summary && Array.isArray(summary.errors) && summary.errors.length) {
      const totalRows = summary.total_rows ?? 0;
      const successCount = summary.success_count ?? 0;
      const skippedCount = summary.skipped_count ?? 0;
      const errorCount = summary.error_count ?? summary.errors.length;

      let errorHtml = '<div style="text-align: left;">';
      errorHtml += '<div class="mb-3" style="background: #f8f9fa; padding: 15px; border-radius: 5px;">';
      errorHtml += '<div class="row">';
      errorHtml += `<div class='col-6'><strong>📊 Total Rows:</strong> <span style='color: #007bff;'>${escapeHtml(totalRows)}</span></div>`;
      errorHtml += `<div class='col-6'><strong>✅ Imported:</strong> <span style='color: #28a745;'>${escapeHtml(successCount)}</span></div>`;
      errorHtml += '</div>';
      errorHtml += "<div class='row mt-1'>";
      errorHtml += `<div class='col-6'><strong>⚠️ Skipped:</strong> <span style='color: #ffc107;'>${escapeHtml(skippedCount)}</span></div>`;
      errorHtml += `<div class='col-6'><strong>❌ Errors:</strong> <span style='color: #dc3545;'>${escapeHtml(errorCount)}</span></div>`;
      errorHtml += '</div>';
      errorHtml += '</div>';

      errorHtml += '<div class="alert alert-danger" style="max-height: 400px; overflow-y: auto; margin-bottom: 0;">';
      errorHtml += '<strong>⚠️ Error Details - Please Review:</strong>';
      errorHtml += '<table class="table table-sm table-bordered mt-2 mb-0" style="background: white;">';
      errorHtml += '<thead style="background: #343a40; color: white;">';
      errorHtml += '<tr>';
      errorHtml += '<th style="width: 80px; text-align: center;">Excel Row #</th>';
      errorHtml += '<th style="width: 150px;">Error Type</th>';
      errorHtml += '<th>What Went Wrong</th>';
      errorHtml += '<th style="width: 120px;">Rider ID</th>';
      errorHtml += '</tr>';
      errorHtml += '</thead>';
      errorHtml += '<tbody>';

      summary.errors.forEach((errorItem) => {
        const row = escapeHtml(errorItem.row ?? 'N/A');
        const errorType = escapeHtml(errorItem.error_type ?? 'N/A');
        const message = escapeHtml(errorItem.message ?? '-');
        const riderId = escapeHtml(errorItem.rider_id ?? errorItem.payout_type ?? 'N/A');

        errorHtml += '<tr>';
        errorHtml += `<td class="text-center" style="background: #fff3cd;"><strong style="color: #856404; font-size: 14px;">Row ${row}</strong></td>`;
        errorHtml += `<td><span class="badge badge-danger" style="font-size: 11px;">${errorType}</span></td>`;
        errorHtml += `<td style="font-size: 13px;">${message}</td>`;
        errorHtml += `<td><code>${riderId}</code></td>`;
        errorHtml += '</tr>';
      });

      errorHtml += '</tbody></table>';
      errorHtml += '</div>';

      errorHtml += '<div class="alert alert-info mt-3 mb-0" style="font-size: 13px;">';
      errorHtml += '<strong>📝 How to Fix These Errors:</strong>';
      errorHtml += '<ol style="margin-bottom: 0; padding-left: 25px;">';
      errorHtml += '<li><strong>Open your Excel file</strong> and locate the row numbers shown above</li>';
      errorHtml += '<li><strong>Check Rider IDs:</strong> Make sure they exist in the Riders database</li>';
      errorHtml += '<li><strong>Verify Dates:</strong> Use format YYYY-MM-DD. <strong>IMPORTANT:</strong> Only today\'s date will be processed. Other dates will be skipped.</li>';
      errorHtml += '<li><strong>Fill Empty Fields:</strong> Ensure rider_id and date are not blank</li>';
      errorHtml += '<li><strong>Save and Re-import:</strong> After fixing, upload the file again</li>';
      errorHtml += '</ol>';
      errorHtml += '</div>';
      errorHtml += '</div>';

      Swal.fire({
        icon: 'warning',
        title: `⚠️ Import Completed with ${escapeHtml(errorCount)} Error(s)`,
        html: errorHtml,
        width: '950px',
        showCancelButton: true,
        confirmButtonText: 'View Detailed Report',
        cancelButtonText: 'Close',
        confirmButtonColor: '#17a2b8',
        cancelButtonColor: '#6c757d',
        customClass: {
          popup: 'text-left',
          title: 'swal-title-custom'
        }
      }).then((result) => {
        if (result.isConfirmed && errorsRoute) {
          window.open(errorsRoute, '_blank');
        }
      });
    } else if (summary) {
      const totalRows = summary.total_rows ?? 0;
      const successCount = summary.success_count ?? 0;

      let successHtml = '<div style="text-align: center;">';
      successHtml += '<div class="mb-3" style="background: #d4edda; padding: 20px; border-radius: 5px; border: 2px solid #28a745;">';
      successHtml += '<h4 style="color: #155724; margin-bottom: 15px;">✅ All Records Imported Successfully!</h4>';
      successHtml += '<div class="row">';
      successHtml += `<div class="col-6"><strong style="font-size: 16px;">Total Rows:</strong><br><span style="color: #007bff; font-size: 24px; font-weight: bold;">${escapeHtml(totalRows)}</span></div>`;
      successHtml += `<div class="col-6"><strong style="font-size: 16px;">Imported:</strong><br><span style="color: #28a745; font-size: 24px; font-weight: bold;">${escapeHtml(successCount)}</span></div>`;
      successHtml += '</div>';
      successHtml += '</div>';
      successHtml += '</div>';

      Swal.fire({
        icon: 'success',
        title: 'Import Successful',
        html: successHtml,
        confirmButtonText: 'Great!',
        confirmButtonColor: '#28a745',
        width: '500px'
      });
    }

    if (Array.isArray(validationErrors) && validationErrors.length) {
      let errorList = '<ul style="text-align: left; margin: 0; padding-left: 20px;">';
      validationErrors.forEach((errorMessage) => {
        errorList += `<li>${escapeHtml(errorMessage)}</li>`;
      });
      errorList += '</ul>';

      Swal.fire({
        icon: 'error',
        title: 'Import Failed',
        html: errorList,
        confirmButtonText: 'OK',
        confirmButtonColor: '#dc3545'
      });
    }
  });
</script>