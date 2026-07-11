@php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp
<table class="table dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Category</th>
            <th>Acquisition Date</th>
            <th>Cost</th>
            <th>Book Value</th>
            <th>Status</th>
            <th width="120px">Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data as $asset)
            @php
                $lastPostedOrSkipped = $asset->depreciationSchedules
                    ->whereIn('status', ['posted', 'skipped'])
                    ->last();
                $bookValue = $lastPostedOrSkipped
                    ? (float) $asset->acquisition_cost - (float) $lastPostedOrSkipped->accumulated_depreciation
                    : (float) $asset->acquisition_cost;
            @endphp
            <tr class="text-center">
                <td>
                    <a href="javascript:void(0);" class="show-modal-right" data-size="xl" data-title="Asset Details" data-action="{{ route('fixed-assets.show', $asset->id) }}">
                        {{ $asset->asset_code }}
                    </a>
                </td>
                <td>
                    @if($asset->bike_id)
                        <a href="{{ route('bikes.show', ['company_slug' => $__companySlug, 'bike' => $asset->bike_id]) }}" target="_blank" rel="noopener">
                            {{ $asset->name }}
                        </a>
                    @else
                        {{ $asset->name }}
                    @endif
                </td>
                <td>{{ $asset->category?->name }}</td>
                <td>{{ $asset->acquisition_date?->format('d M Y') }}</td>
                <td>{{ number_format((float) $asset->acquisition_cost, 2) }}</td>
                <td>{{ number_format((float) $bookValue, 2) }}</td>
                <td>
                    @if($asset->status === 'draft')
                        <span class="badge bg-warning text-dark">Draft</span>
                    @elseif($asset->status === 'active')
                        <span class="badge bg-success">Active</span>
                    @elseif($asset->status === 'fully_depreciated')
                        <span class="badge bg-info">Fully Depreciated</span>
                    @else
                        <span class="badge bg-secondary">Disposed</span>
                    @endif
                </td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2" type="button" data-bs-toggle="dropdown">
                            <i class="icon-base ti ti-dots icon-md"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item show-modal-right" href="javascript:void(0);" data-size="xl" data-title="Asset Details" data-action="{{ route('fixed-assets.show', $asset->id) }}">
                                <i class="fa fa-eye"></i> View
                            </a>
                            @can('assets_edit')
                            <a class="dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Edit Asset" data-action="{{ route('fixed-assets.edit', $asset->id) }}">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            @endcan
                            @can('assets_delete')
                            <a class="dropdown-item" href="javascript:void(0);" onclick="confirmDelete('{{ route('fixed-assets.delete', $asset->id) }}')">
                                <i class="fa fa-trash"></i> Delete
                            </a>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="text-center text-muted py-4">No fixed assets found.</td>
            </tr>
        @endforelse
    </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
