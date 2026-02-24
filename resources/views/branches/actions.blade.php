{{-- resources/views/branches/actions.blade.php --}}
<div class="d-flex gap-1 justify-content-center">
    @can('branches_view')
    <a href="{{ route('branches.show', $branch->id) }}" 
       class="btn btn-sm btn-icon btn-info action-btn" 
       data-bs-toggle="tooltip" 
       title="View Details">
        <i class="icon-base ti ti-eye"></i>
    </a>
    @endcan

    @can('branches_edit')
    <a href="javascript:void(0)" data-action="{{ route('branches.edit', $branch->id) }}" 
       class="btn btn-sm btn-icon btn-primary action-btn show-modal" 
       data-bs-toggle="tooltip" 
       data-title="Edit Branch"
       data-size="lg">
        <i class="icon-base ti ti-edit"></i>
    </a>
    @endcan

    @can('branches_edit')
    <button type="button" 
            class="btn btn-sm btn-icon btn-warning action-btn toggle-status" 
            {{-- data-url="{{ route('branches.toggle-status', $branch->id) }}" --}}
            data-name="{{ $branch->name }}"
            data-bs-toggle="tooltip" 
            title="{{ $branch->is_active ? 'Deactivate' : 'Activate' }}">
        <i class="icon-base ti ti-power"></i>
    </button>
    @endcan

    @can('branches_delete')
    <button type="button" 
            class="btn btn-sm btn-icon btn-danger action-btn delete-branch" 
            data-url="{{ route('branches.destroy', $branch->id) }}"
            data-name="{{ $branch->name }}"
            data-bs-toggle="tooltip" 
            title="Delete Branch">
        <i class="icon-base ti ti-trash"></i>
    </button>
    @endcan
</div>