@extends('riders.view')

@section('page_content')
@php
  $uploadedCount = is_countable($files ?? null) ? count($files) : 0;
  $pendingCount = is_countable($missingFiles ?? null) ? count($missingFiles) : 0;
  $expiringCount = 0;
  $expiredCount = 0;
  foreach (($files ?? []) as $f) {
    $meta = \App\Support\RiderDocumentReplacement::expiryMetaForFile($f, $riders);
    $status = $meta['status'] ?? 'none';
    if (in_array($status, ['expired'], true)) {
      $expiredCount++;
    } elseif (in_array($status, ['expiring', 'critical', 'warning'], true)) {
      $expiringCount++;
    } elseif (!empty($meta['expiry'])) {
      try {
        $days = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($meta['expiry']), false);
        if ($days < 0) {
          $expiredCount++;
        } elseif ($days <= 60) {
          $expiringCount++;
        }
      } catch (\Throwable) {
        // ignore
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
              <div class="text-muted small">Rider document checklist and uploads</div>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-2 mt-3">
            <span class="rider-docs-stat">
              <span class="rider-docs-stat-dot is-uploaded"></span>
              <strong>{{ $uploadedCount }}</strong> uploaded
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

          @can('riders_documents_create')
          <a class="btn btn-primary show-modal action-btn rider-docs-upload-btn"
            href="javascript:void(0);"
            data-action="{{ route('files.create',['type_id'=>$riders->id,'type'=>'rider']) }}"
            data-size="lg"
            data-title="Upload File">
            <i class="ti ti-upload me-1"></i>Upload File
          </a>
          @endcan
        </div>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 rider-docs-table" id="files-table">
        <thead>
          <tr>
            <th style="width: 56px;" class="text-center">#</th>
            <th class="text-start">Document</th>
            <th style="width: 160px;" class="text-center">Expiry</th>
            <th style="width: 140px;" class="text-end">Action</th>
          </tr>
        </thead>
        <tbody id="files-table-body">
          @php $counter = 1; @endphp
          @foreach ($files as $riderFile)
          @php
            $docBadge = \App\Support\RiderDocumentReplacement::expiryMetaForFile($riderFile, $riders);
            $displayName = ucwords(str_replace('_', ' ', $riderFile->name));
            $fileUrl = storage_url($riderFile->type . '/' . $riderFile->type_id . '/' . $riderFile->file_name);
            $ext = strtolower((string) ($riderFile->file_type ?? pathinfo((string) $riderFile->file_name, PATHINFO_EXTENSION)));
            $iconClass = match (true) {
              in_array($ext, ['pdf'], true) => 'ti-file-type-pdf text-danger',
              in_array($ext, ['doc', 'docx'], true) => 'ti-file-type-doc text-primary',
              in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) => 'ti-photo text-success',
              default => 'ti-file-text text-secondary',
            };
          @endphp
          <tr class="file-row is-uploaded" data-name="{{ strtolower($riderFile->name) }}">
            <td class="row-counter text-center text-muted">{{ $counter++ }}</td>
            <td class="text-start">
              <div class="d-flex align-items-center gap-2">
                <span class="rider-docs-file-icon">
                  <i class="ti {{ $iconClass }}"></i>
                </span>
                <div class="min-w-0">
                  <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="rider-docs-name fw-medium">
                    {{ $displayName }}
                  </a>
                  <div class="text-muted small">
                    Uploaded
                    @if(!empty($riderFile->updated_at))
                      · {{ \App\Helpers\General::DateFormat($riderFile->updated_at) }}
                    @endif
                  </div>
                </div>
              </div>
            </td>
            <td class="text-center">
              @include('riders._document_expiry_badge', ['badge' => $docBadge])
            </td>
            <td class="text-end">
              <div class="btn-group btn-group-sm rider-docs-actions" role="group">
                @if(auth()->user()?->can('riders_documents_edit') || auth()->user()?->can('riders_documents_create') || auth()->user()?->can('documents_edit'))
                <a class="btn btn-outline-primary show-modal action-btn"
                  href="javascript:void(0);"
                  data-action="{{ route('files.edit', $riderFile->id) }}"
                  data-size="lg"
                  data-title="Edit Document"
                  title="Edit">
                  <i class="ti ti-edit"></i>
                </a>
                @endif
                @can('riders_documents_delete')
                <a href="javascript:void(0);"
                  data-url="{{ route('files.destroy', $riderFile->id) }}"
                  class="btn btn-outline-danger delete-file"
                  title="Delete">
                  <i class="ti ti-trash"></i>
                </a>
                @endcan
              </div>
            </td>
          </tr>
          @endforeach

          @if(!empty($missingFiles))
          @foreach($missingFiles as $key => $fileName)
          <tr class="file-row is-missing" data-name="{{ strtolower($fileName . ' ' . $key) }}">
            <td class="row-counter text-center text-muted">{{ $counter++ }}</td>
            <td class="text-start">
              <div class="d-flex align-items-center gap-2">
                <span class="rider-docs-file-icon is-missing">
                  <i class="ti ti-file-off"></i>
                </span>
                <div class="min-w-0">
                  <div class="rider-docs-name text-body">{{ $fileName }}</div>
                  <div class="text-muted small">Required · not uploaded yet</div>
                </div>
              </div>
            </td>
            <td class="text-center">
              <span class="badge rider-docs-missing-badge">Missing</span>
            </td>
            <td class="text-end">
              @can('riders_documents_create')
              @php
                $docSubtypeKey = null;
                $docTypeKey = (string) $key;
                if (str_ends_with((string) $key, '_front')) {
                  $docTypeKey = substr((string) $key, 0, -6);
                  $docSubtypeKey = 'front';
                } elseif (str_ends_with((string) $key, '_back')) {
                  $docTypeKey = substr((string) $key, 0, -5);
                  $docSubtypeKey = 'back';
                }
                $createParams = [
                  'type_id' => $riders->id,
                  'type' => 'rider',
                  'suggested_name' => $key,
                  'document_key' => $key,
                  'document_type_key' => $docTypeKey,
                ];
                if ($docSubtypeKey) {
                  $createParams['document_subtype_key'] = $docSubtypeKey;
                }
              @endphp
              <a class="btn btn-sm btn-primary show-modal action-btn"
                href="javascript:void(0);"
                data-action="{{ route('files.create', $createParams) }}"
                data-size="lg"
                data-title="Upload {{ $fileName }}">
                <i class="ti ti-upload me-1"></i>Upload
              </a>
              @endcan
            </td>
          </tr>
          @endforeach
          @endif
        </tbody>
      </table>

      <div id="no-results" class="rider-docs-empty text-center py-5" style="display: none;">
        <i class="ti ti-search-off fs-2 text-muted d-block mb-2"></i>
        <div class="fw-medium">No documents found</div>
        <div class="text-muted small">Try a different search term</div>
      </div>

      @if($uploadedCount === 0 && $pendingCount === 0)
      <div class="rider-docs-empty text-center py-5">
        <i class="ti ti-folder-off fs-2 text-muted d-block mb-2"></i>
        <div class="fw-medium">No documents yet</div>
        <div class="text-muted small mb-3">Upload the first rider document to get started</div>
        @can('riders_documents_create')
        <a class="btn btn-primary show-modal action-btn"
          href="javascript:void(0);"
          data-action="{{ route('files.create',['type_id'=>$riders->id,'type'=>'rider']) }}"
          data-size="lg"
          data-title="Upload File">
          <i class="ti ti-upload me-1"></i>Upload File
        </a>
        @endcan
      </div>
      @endif
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
    $(document).on('click', '.delete-file', function(e) {
      e.preventDefault();
      const url = $(this).data('url');

      Swal.fire({
        title: 'Delete document?',
        text: "This cannot be undone.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: url,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
              Swal.fire('Deleted!', 'Document has been deleted.', 'success').then(() => location.reload());
            },
            error: function() {
              Swal.fire('Error!', 'Failed to delete document.', 'error');
            }
          });
        }
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

  .rider-docs-table tbody tr.is-missing:hover {
    background: #f8fafc;
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

  a.rider-docs-name:hover {
    color: #1e4b8e;
    text-decoration: underline;
  }

  .rider-docs-missing-badge {
    background: #f1f5f9;
    color: #64748b;
    border: 1px solid #e2e8f0;
    font-weight: 600;
    padding: 0.28rem 0.55rem;
  }

  .rider-docs-actions .btn {
    width: 2rem;
    height: 2rem;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }

  .rider-docs-empty {
    background: #fff;
  }

  @media (max-width: 576px) {
    .rider-docs-search {
      min-width: 100%;
      max-width: 100%;
    }

    .rider-docs-upload-btn {
      width: 100%;
    }
  }
</style>
@endsection
