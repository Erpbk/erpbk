<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    @php $vf = static fn (string $f): bool => field_visible('sim', $f); @endphp
    <thead class="text-center">
        <tr role="row">
            @if($vf('name'))<th>Name</th>@endif
            @if($vf('company_contact'))<th>Contact</th>@endif
            @if($vf('email'))<th>Email</th>@endif
            @if($vf('address'))<th>Address</th>@endif
            <th>Chart account</th>
            @if($vf('status'))<th>Status</th>@endif
            <th width="120px">Action</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
      <tr class="text-center" data-id="{{ $row->id }}">
            @if($vf('name'))<td class="text-start">
                <a href="{{ route('simCompanies.show', $row->id) }}">{{ $row->name }}</a>
            </td>@endif
            @if($vf('company_contact'))<td>{{ $row->company_contact }}</td>@endif
            @if($vf('email'))<td>{{ $row->email }}</td>@endif
            @if($vf('address'))<td class="text-start small">{{ \Illuminate\Support\Str::limit($row->address, 40) }}</td>@endif
            <td class="text-start small">
                @if($row->account)
                    {{ $row->account->account_code }} — {{ $row->account->name }}
                @else
                    —
                @endif
            </td>
            @if($vf('status'))<td>
                @if($row->status == 1)
                <span class="badge bg-success">Active</span>
                @else
                <span class="badge bg-danger">Inactive</span>
                @endif
            </td>@endif
            <td>
                <div class="btn-group">
                    @if($row->account_id)
                    <a href="{{ route('simCompanies.ledger', $row->id) }}" class="btn btn-default btn-sm" title="Ledger">
                        <i class="fa fa-book"></i>
                    </a>
                    @endif
                    @can('sims_companies_edit')
                    <a href="javascript:void(0);" data-action="{{ route('simCompanies.edit', $row->id) }}" class="btn btn-info btn-sm show-modal" data-size="lg" data-title="Update SIM company">
                        <i class="fa fa-edit"></i>
                    </a>
                    @endcan
                    @can('sims_companies_delete')
                    <a href="javascript:void(0);" onclick='confirmDelete("{{ route('simCompanies.delete', $row->id) }}")' class="btn btn-danger btn-sm confirm-modal" data-size="lg" data-title="Delete Item">
                        <i class="fa fa-trash"></i>
                    </a>
                    @endcan
                </div>
            </td>
            <td></td>
        </tr>
        @endforeach
        @if($data->isEmpty())
        <tr>
            <td colspan="8" class="text-center py-5">No SIM companies found</td>
        </tr>
        @endif
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
@include('delete_requests._pending_table_script', ['items' => $data])
