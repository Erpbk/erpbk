@forelse($documentTypes as $index => $doc)
  <tr data-id="{{ $doc->id }}">
    <td class="align-middle">
      <span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span>
    </td>
    <td class="align-middle">{{ $index + 1 }}</td>
    <td class="align-middle"><code>{{ $doc->key }}</code></td>
    <td class="align-middle">
      @if($doc->type === 'single')
        <span class="badge bg-label-info">Single</span>
      @else
        <span class="badge bg-label-primary">Dual</span>
      @endif
    </td>
    <td class="align-middle">
      @if($doc->type === 'single')
        {{ $doc->label ?: '—' }}
      @else
        <span class="text-muted small">Front: {{ $doc->front_label ?? '—' }}</span><br>
        <span class="text-muted small">Back: {{ $doc->back_label ?? '—' }}</span>
      @endif
    </td>
    <td class="align-middle">
      @if($doc->is_active)
        <span class="badge bg-label-success">Active</span>
      @else
        <span class="badge bg-label-secondary">Inactive</span>
      @endif
    </td>
    <td class="text-end align-middle">
      <div class="btn-group btn-group-sm" role="group">
        <button type="button"
                class="btn btn-outline-secondary btn-icon btn-edit-document-type"
                data-id="{{ $doc->id }}"
                data-key="{{ $doc->key }}"
                data-type="{{ $doc->type }}"
                data-label="{{ $doc->label }}"
                data-front-label="{{ $doc->front_label }}"
                data-back-label="{{ $doc->back_label }}"
                data-active="{{ $doc->is_active ? '1' : '0' }}"
                data-bs-toggle="modal"
                data-bs-target="#editRiderDocumentTypeModal">
          <i class="ti ti-edit"></i>
        </button>
        <form method="POST"
              action="{{ route('settings-panel.rider-settings.destroy-document-type', $doc->id) }}"
              class="d-inline btn-delete-document-type"
              data-confirm="Delete this document type?">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-icon">
            <i class="ti ti-trash"></i>
          </button>
        </form>
      </div>
    </td>
  </tr>
@empty
  <tr>
    <td colspan="6" class="text-center text-muted py-4">No document types yet. Add one to define required rider documents.</td>
  </tr>
@endforelse
