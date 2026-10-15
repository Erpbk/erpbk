@extends('riders.view')

@section('page_content')
@php
  $checklist = $checklist ?? [];
  $uploadedCount = isset($uploadedCount) ? (int) $uploadedCount : collect($checklist)->where('status', 'complete')->count();
  $pendingCount = isset($pendingCount) ? (int) $pendingCount : collect($checklist)->whereIn('status', ['missing', 'partial'])->count();
  $expiringCount = 0;
  $expiredCount = 0;
  foreach ($checklist as $row) {
    $checkFiles = array_filter([
      $row['file'] ?? null,
      $row['front_file'] ?? null,
      $row['back_file'] ?? null,
    ]);
    foreach ($checkFiles as $f) {
      $meta = \App\Support\RiderDocumentReplacement::expiryMetaForFile($f, $riders);
      $status = $meta['status'] ?? 'none';
      if (in_array($status, ['expired'], true)) {
        $expiredCount++;
      } elseif (in_array($status, ['expiring', 'critical', 'warning'], true)) {
        $expiringCount++;
      }
    }
  }
@endphp

<div class="card-action mb-0">
  @can('riders_documents_view')
  <div class="card rider-docs-card mb-4">
    <div class="card-header rider-docs-header border-bottom">
      <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 w-100">
        <div class="min-w-0">
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="rider-docs-icon">
              <i class="ti ti-folder-open"></i>
            </span>
            <div>
              <h5 class="mb-0 fw-semibold">Documents</h5>
              <div class="text-muted small">Configured rider documents — upload from checklist</div>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="rider-docs-stat">
              <span class="rider-docs-stat-dot is-uploaded"></span>
              <strong>{{ $uploadedCount }}</strong> complete
            </span>
            <span class="rider-docs-stat">
              <span class="rider-docs-stat-dot is-pending"></span>
              <strong>{{ $pendingCount }}</strong> pending
            </span>
            @if($expiringCount > 0)
            <span class="rider-docs-stat">
              <span class="rider-docs-stat-dot is-expiring"></span>
              <strong>{{ $expiringCount }}</strong> expiring
            </span>
            @endif
            @if($expiredCount > 0)
            <span class="rider-docs-stat">
              <span class="rider-docs-stat-dot is-expired"></span>
              <strong>{{ $expiredCount }}</strong> expired
            </span>
            @endif
          </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
          <div class="rider-docs-search input-group input-group-sm">
            <span class="input-group-text bg-white border-end-0">
              <i class="ti ti-search text-muted"></i>
            </span>
            <input type="text"
              class="form-control border-start-0"
              id="file-search"
              placeholder="Search documents..."
              autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear search">
              <i class="ti ti-x"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 rider-docs-table" id="files-table">
        <thead>
          <tr>
            <th style="width: 56px;" class="text-center">#</th>
            <th class="text-start">Document</th>
            <th style="width: 120px;" class="text-center">Type</th>
            <th style="width: 140px;" class="text-center">Status</th>
            <th style="width: 160px;" class="text-center">Expiry</th>
            <th style="width: 160px;" class="text-end">Action</th>
          </tr>
        </thead>
        <tbody id="files-table-body">
          @php $counter = 1; @endphp
          @forelse ($checklist as $row)
          @php
            $status = $row['status'] ?? 'missing';
            $isDual = ($row['kind'] ?? '') === 'dual';
            $primaryFile = $isDual
              ? ($row['front_file'] ?? $row['back_file'] ?? null)
              : ($row['file'] ?? null);
            $docBadge = $primaryFile
              ? \App\Support\RiderDocumentReplacement::expiryMetaForFile($primaryFile, $riders)
              : ['status' => 'none', 'expiry' => null, 'label' => null];
            $uploadUrl = route('files.rider-document-upload', [
              'rider' => $riders->id,
              'documentType' => $row['document_type_id'],
            ]);
            $modalTitle = ($status === 'complete' || $status === 'partial' ? 'Update' : 'Upload') . ' ' . $row['label'];
            $searchBlob = strtolower(($row['label'] ?? '') . ' ' . ($row['key'] ?? '') . ' ' . ($row['front_label'] ?? '') . ' ' . ($row['back_label'] ?? ''));
          @endphp
          <tr class="file-row is-{{ $status }}" data-name="{{ $searchBlob }}">
            <td class="row-counter text-center text-muted">{{ $counter++ }}</td>
            <td class="text-start">
              <div class="d-flex align-items-center gap-2">
                <span class="rider-docs-file-icon {{ $status === 'missing' ? 'is-missing' : '' }}">
                  <i class="ti {{ $isDual ? 'ti-copy' : ($status === 'complete' ? 'ti-file-check' : 'ti-file-off') }}"></i>
                </span>
                <div class="min-w-0">
                  <div class="rider-docs-name fw-medium text-body">{{ $row['label'] }}</div>
                  <div class="text-muted small">
                    @if($isDual)
                      @if(!empty($row['front_file']))
                        @php $frontUrl = storage_url($row['front_file']->type . '/' . $row['front_file']->type_id . '/' . $row['front_file']->file_name); @endphp
                        <a href="{{ $frontUrl }}" target="_blank" rel="noopener">{{ $row['front_label'] ?: 'Front' }}</a>
                      @else
                        <span>{{ $row['front_label'] ?: 'Front' }}</span>
                      @endif
                      <span class="mx-1">·</span>
                      @if(!empty($row['back_file']))
                        @php $backUrl = storage_url($row['back_file']->type . '/' . $row['back_file']->type_id . '/' . $row['back_file']->file_name); @endphp
                        <a href="{{ $backUrl }}" target="_blank" rel="noopener">{{ $row['back_label'] ?: 'Back' }}</a>
                      @else
                        <span>{{ $row['back_label'] ?: 'Back' }}</span>
                      @endif
                    @elseif(!empty($row['file']))
                      @php $fileUrl = storage_url($row['file']->type . '/' . $row['file']->type_id . '/' . $row['file']->file_name); @endphp
                      <a href="{{ $fileUrl }}" target="_blank" rel="noopener">View file</a>
                      @if(!empty($row['file']->updated_at))
                        · {{ \App\Helpers\General::DateFormat($row['file']->updated_at) }}
                      @endif
                    @else
                      Required · not uploaded yet
                    @endif
                  </div>
                </div>
              </div>
            </td>
            <td class="text-center">
              <span class="badge {{ $isDual ? 'bg-label-info' : 'bg-label-secondary' }}">
                {{ $isDual ? 'Dual' : 'Single' }}
              </span>
            </td>
            <td class="text-center">
              @if($status === 'complete')
                <span class="badge bg-label-success">Complete</span>
              @elseif($status === 'partial')
                <span class="badge bg-label-warning">Partial</span>
              @else
                <span class="badge rider-docs-missing-badge">Missing</span>
              @endif
            </td>
            <td class="text-center">
              @if($primaryFile)
                @include('riders._document_expiry_badge', ['badge' => $docBadge])
              @else
                <span class="text-muted">—</span>
              @endif
            </td>
            <td class="text-end">
              <div class="rider-docs-actions d-inline-flex align-items-center justify-content-end gap-1">
                @if(auth()->user()?->can('riders_documents_create') || auth()->user()?->can('riders_documents_edit') || auth()->user()?->can('documents_create') || auth()->user()?->can('documents_edit'))
                <a class="btn btn-sm {{ $status === 'complete' ? 'btn-outline-primary' : 'btn-primary' }} show-modal"
                  href="javascript:void(0);"
                  data-action="{{ $uploadUrl }}"
                  data-size="lg"
                  data-title="{{ $modalTitle }}"
                  title="{{ $status === 'complete' ? 'Update' : 'Upload' }}">
                  <i class="ti {{ $status === 'complete' ? 'ti-edit' : 'ti-upload' }}{{ $status === 'missing' ? ' me-1' : '' }}"></i>
                  @if($status === 'missing')
                    Upload
                  @endif
                </a>
                @endif
                @can('riders_documents_delete')
                  @php
                    $deleteIds = [];
                    if (!empty($row['file'])) { $deleteIds[] = $row['file']->id; }
                    if (!empty($row['front_file'])) { $deleteIds[] = $row['front_file']->id; }
                    if (!empty($row['back_file'])) { $deleteIds[] = $row['back_file']->id; }
                  @endphp
                  @if(!empty($deleteIds))
                  <a href="javascript:void(0);"
                    data-ids="{{ implode(',', $deleteIds) }}"
                    class="btn btn-sm btn-outline-danger delete-doc-type"
                    title="Delete">
                    <i class="ti ti-trash"></i>
                  </a>
                  @endif
                @endcan
              </div>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="6" class="text-center py-5">
              <i class="ti ti-folder-off fs-2 text-muted d-block mb-2"></i>
              <div class="fw-medium">No document types configured</div>
              <div class="text-muted small">Add document types in Rider Document Settings</div>
            </td>
          </tr>
          @endforelse
        </tbody>
      </table>

      <div id="no-results" class="rider-docs-empty text-center py-5" style="display: none;">
        <i class="ti ti-search-off fs-2 text-muted d-block mb-2"></i>
        <div class="fw-medium">No documents found</div>
        <div class="text-muted small">Try a different search term</div>
      </div>
    </div>
  </div>
  @else
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="ti ti-lock fs-2 text-muted d-block mb-2"></i>
      <h5 class="mb-1">Access denied</h5>
      <p class="text-muted mb-0">You are not authorized to view rider documents.</p>
    </div>
  </div>
  @endcan
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="text/javascript">
  $(document).ready(function() {
    $(document).on('click', '.delete-doc-type', function(e) {
      e.preventDefault();
      const ids = String($(this).data('ids') || '').split(',').filter(Boolean);
      if (!ids.length) return;

      Swal.fire({
        title: 'Delete document?',
        text: ids.length > 1
          ? 'This will remove all sides of this document.'
          : 'This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete'
      }).then((result) => {
        if (!result.isConfirmed) return;

        const token = '{{ csrf_token() }}';
        const destroyTpl = @json(route('files.destroy', ['file' => '__ID__']));
        const deletes = ids.map(function(id) {
          return $.ajax({
            url: destroyTpl.replace('__ID__', id),
            type: 'DELETE',
            data: { _token: token }
          });
        });

        $.when.apply($, deletes).done(function() {
          Swal.fire('Deleted!', 'Document has been deleted.', 'success').then(() => location.reload());
        }).fail(function() {
          Swal.fire('Error!', 'Failed to delete document.', 'error');
        });
      });
    });

    function recalculateCounters() {
      let counter = 1;
      $('.file-row:visible').each(function() {
        $(this).find('.row-counter').text(counter++);
      });
    }

    $('#file-search').on('keyup', function() {
      const searchTerm = $(this).val().toLowerCase().trim();
      const rows = $('.file-row');
      let visibleRows = 0;

      rows.each(function() {
        const fileName = String($(this).data('name') || '');
        if (!searchTerm || fileName.includes(searchTerm)) {
          $(this).show();
          visibleRows++;
        } else {
          $(this).hide();
        }
      });

      recalculateCounters();

      if (visibleRows === 0 && searchTerm !== '') {
        $('#no-results').show();
        $('#files-table-body').hide();
      } else {
        $('#no-results').hide();
        $('#files-table-body').show();
      }
    });

    $('#clear-search').on('click', function() {
      $('#file-search').val('');
      $('.file-row').show();
      $('#no-results').hide();
      $('#files-table-body').show();
      recalculateCounters();
    });
  });
</script>

<style>
  .rider-docs-card {
    border: 1px solid #e9ecef;
    border-radius: 0.85rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  }

  .rider-docs-header {
    background: #fff;
    padding: 1.15rem 1.25rem;
  }

  .rider-docs-icon {
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 0.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(30, 75, 142, 0.08);
    color: #1e4b8e;
    flex-shrink: 0;
  }

  .rider-docs-icon i {
    font-size: 1.15rem;
  }

  .rider-docs-stat {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.28rem 0.65rem;
    border-radius: 999px;
    background: #f8fafc;
    border: 1px solid #eef2f7;
    color: #475569;
    font-size: 0.78rem;
  }

  .rider-docs-stat-dot {
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 50%;
    background: #94a3b8;
  }

  .rider-docs-stat-dot.is-uploaded { background: #22c55e; }
  .rider-docs-stat-dot.is-pending { background: #64748b; }
  .rider-docs-stat-dot.is-expiring { background: #f97316; }
  .rider-docs-stat-dot.is-expired { background: #ef4444; }

  .rider-docs-search {
    min-width: 220px;
    max-width: 280px;
  }

  .rider-docs-search .form-control:focus {
    box-shadow: none;
    border-color: #ced4da;
  }

  .rider-docs-search .input-group-text {
    border-color: #ced4da;
  }

  .rider-docs-table thead th {
    background: #f8fafc;
    color: #64748b;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    border-bottom: 1px solid #eef2f7;
    padding: 0.75rem 1rem;
    white-space: nowrap;
  }

  .rider-docs-table tbody td {
    padding: 0.9rem 1rem;
    border-color: #f1f5f9;
    vertical-align: middle;
  }

  .rider-docs-table tbody tr.file-row:hover {
    background: #f8fafc;
  }

  .rider-docs-table tbody tr.is-missing {
    background: #fcfcfd;
  }

  .rider-docs-file-icon {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    flex-shrink: 0;
  }

  .rider-docs-file-icon.is-missing {
    background: #f1f5f9;
    color: #94a3b8;
  }

  .rider-docs-file-icon i {
    font-size: 1.05rem;
  }

  .rider-docs-name {
    color: #0f172a;
    text-decoration: none;
    display: block;
    line-height: 1.25;
  }

  .rider-docs-missing-badge {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    font-weight: 600;
    padding: 0.28rem 0.55rem;
  }

  .rider-docs-actions {
    float: none;
    vertical-align: middle;
  }

  .rider-docs-actions .btn {
    float: none;
    margin: 0;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.55rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
    position: static;
    overflow: visible;
  }

  .rider-docs-actions .btn::before {
    display: none;
  }

  .rider-docs-empty {
    background: #fff;
  }

  @media (max-width: 576px) {
    .rider-docs-search {
      min-width: 100%;
      max-width: 100%;
    }
  }
</style>
@endsection
