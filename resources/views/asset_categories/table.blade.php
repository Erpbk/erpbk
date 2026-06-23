<table class="table dataTable no-footer">
    <thead class="text-center">
        <tr>
            <th>Name</th>
            <th>Method</th>
            <th>Frequency</th>
            <th>Useful Life</th>
            <th>Salvage %</th>
            <th>Status</th>
            <th width="120px">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($categories as $category)
            <tr class="text-center">
                <td>{{ $category->name }}</td>
                <td>{{ \App\Models\AssetCategory::depreciationMethods()[$category->depreciation_method] ?? $category->depreciation_method }}</td>
                <td>{{ \App\Models\AssetCategory::depreciationFrequencies()[$category->depreciation_frequency ?? 'monthly'] ?? $category->depreciation_frequency }}</td>
                <td>{{ $category->useful_life_months }} months</td>
                <td>{{ number_format((float) $category->salvage_value_percent, 2) }}%</td>
                <td>
                    @if($category->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-danger">Inactive</span>
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill border-0 p-2" type="button" data-bs-toggle="dropdown">
                            <i class="ti ti-dots"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            @can('asset_edit')
                            <a class="dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Edit Category" data-action="{{ route('asset-categories.edit', $category->id) }}">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            @endcan
                            @can('asset_delete')
                            @unless($category->isSystemLocked())
                            <a class="dropdown-item" href="javascript:void(0);" onclick="confirmDelete('{{ route('asset-categories.delete', $category->id) }}')">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                            @endunless
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="text-center text-muted py-4">No asset categories yet.</td>
            </tr>
        @endforelse
    </tbody>
</table>
