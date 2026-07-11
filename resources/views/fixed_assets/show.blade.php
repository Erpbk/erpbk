<div class="card-body p-4">
    @php $__companySlug = \App\Support\CompanyRouteContext::slug(); @endphp
    <div class="row mb-3">
        <div class="col-md-6">
            <h5 class="mb-1">
                @if($asset->bike_id)
                    <a href="{{ route('bikes.show',  $asset->bike_id) }}" target="_blank" rel="noopener">{{ $asset->name }}</a>
                @else
                    {{ $asset->name }}
                @endif
            </h5>
            <p class="text-muted mb-0">{{ $asset->asset_code }} &middot; {{ $asset->category?->name }}</p>
        </div>
        <div class="col-md-6 text-md-end">
            @if($asset->status === 'draft')
                <span class="badge bg-warning text-dark">Draft</span>
            @elseif($asset->status === 'active')
                <span class="badge bg-success">Active</span>
            @elseif($asset->status === 'fully_depreciated')
                <span class="badge bg-info">Fully Depreciated</span>
            @else
                <span class="badge bg-secondary">Disposed</span>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-4"><strong>Acquisition Type</strong><br>{{ \App\Models\FixedAsset::acquisitionTypes()[$asset->acquisition_type ?? 'new_purchase'] ?? $asset->acquisition_type }}</div>
        <div class="col-md-4"><strong>Acquisition Date</strong><br>{{ $asset->acquisition_date?->format('d M Y') }}</div>
        <div class="col-md-4"><strong>In-Service Date</strong><br>{{ $asset->in_service_date?->format('d M Y') }}</div>
        <div class="col-md-4 mt-3"><strong>Branch</strong><br>{{ $asset->branch?->name ?? '—' }}</div>
        <div class="col-md-4 mt-3"><strong>Acquisition Cost</strong><br>{{ number_format((float) $asset->acquisition_cost, 2) }}</div>
        <div class="col-md-4 mt-3"><strong>Salvage Value</strong><br>{{ number_format((float) $asset->salvage_value, 2) }}</div>
        @if($asset->isOpeningBalance())
        <div class="col-md-4 mt-3"><strong>Opening Accumulated Depreciation</strong><br>{{ number_format((float) $asset->opening_accumulated_depreciation, 2) }}</div>
        <div class="col-md-4 mt-3"><strong>Depreciation As Of</strong><br>{{ $asset->depreciation_as_of_date?->format('d M Y') ?? '—' }}</div>
        <div class="col-md-4 mt-3"><strong>Current Book Value</strong><br>{{ number_format($asset->currentBookValue(), 2) }}</div>
        @elseif($asset->past_depreciation_handling)
        <div class="col-md-4 mt-3"><strong>Past Depreciation Handling</strong><br>{{ \App\Models\FixedAsset::pastDepreciationHandlingOptions()[$asset->past_depreciation_handling] ?? $asset->past_depreciation_handling }}</div>
        @endif
        <div class="col-md-4 mt-3"><strong>Depreciation Method</strong><br>{{ \App\Models\AssetCategory::depreciationMethods()[$asset->depreciation_method] ?? $asset->depreciation_method }}</div>
        <div class="col-md-4 mt-3"><strong>Posting Frequency</strong><br>{{ \App\Models\AssetCategory::depreciationFrequencies()[$asset->depreciation_frequency ?? 'monthly'] ?? $asset->depreciation_frequency }}</div>
        <div class="col-md-4 mt-3"><strong>Useful Life</strong><br>{{ $asset->useful_life_months }} months</div>
        @if($asset->description)
        <div class="col-md-12 mt-3"><strong>Description</strong><br>{{ $asset->description }}</div>
        @endif
    </div>

    <hr>

    <h6 class="mb-2">Depreciation Schedule</h6>
    <style>
        .depreciation-schedule-table {
            font-size: 0.72rem;
            line-height: 1.25;
        }
        .depreciation-schedule-table table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0;
        }
        .depreciation-schedule-table th,
        .depreciation-schedule-table td {
            padding: 0.2rem 0.25rem;
            vertical-align: middle;
            word-break: break-word;
        }
        .depreciation-schedule-table th {
            font-size: 0.68rem;
            font-weight: 600;
            white-space: normal;
        }
        .depreciation-schedule-table .col-period { width: 6%; }
        .depreciation-schedule-table .col-date { width: 14%; }
        .depreciation-schedule-table .col-billing { width: 11%; }
        .depreciation-schedule-table .col-amount { width: 16%; }
        .depreciation-schedule-table .col-status { width: 11%; }
        .depreciation-schedule-table .status-label {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 600;
            line-height: 1.1;
            padding: 0.1rem 0.25rem;
            border-radius: 0.2rem;
        }
        .depreciation-schedule-table .status-posted { color: #198754; background: rgba(25, 135, 84, 0.12); }
        .depreciation-schedule-table .status-skipped { color: #6c757d; background: rgba(108, 117, 125, 0.12); }
        .depreciation-schedule-table .status-pending { color: #997404; background: rgba(255, 193, 7, 0.15); }
    </style>
    <div class="depreciation-schedule-table">
        <table class="table table-sm table-bordered mb-0">
            <thead class="text-center">
                <tr>
                    <th class="col-period">#</th>
                    <th class="col-date">Trans Date</th>
                    <th class="col-billing">Bill Month</th>
                    <th class="col-amount">Depreciation</th>
                    <th class="col-amount">Accumulated</th>
                    <th class="col-amount">Book Value</th>
                    <th class="col-status">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($asset->depreciationSchedules as $row)
                    <tr class="text-center">
                        <td>{{ $row->period_number }}</td>
                        <td>{{ $row->period_date?->format('d/m/y') }}</td>
                        <td>{{ $row->period_date?->format('M-y') }}</td>
                        <td class="text-end">{{ number_format((float) $row->depreciation_amount, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->accumulated_depreciation, 2) }}</td>
                        <td class="text-end">{{ number_format((float) $row->book_value, 2) }}</td>
                        <td>
                            @if($row->status === 'posted')
                                <span class="status-label status-posted">Posted</span>
                            @elseif($row->status === 'skipped')
                                <span class="status-label status-skipped">Skipped</span>
                            @else
                                <span class="status-label status-pending">Pending</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-2">No schedule generated.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="action-btn">
    @can('assets_edit')
    <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="xl" data-title="Edit Asset" data-action="{{ route('fixed-assets.edit', $asset->id) }}">Edit Asset</a>
    @endcan
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Close</button>
</div>
