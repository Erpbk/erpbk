<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th>Name</th>
            <th>Contact</th>
            <th>Email</th>
            <th>Address</th>
            <th>Chart account</th>
            <th>Status</th>
            <th width="120px">Action</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $row)
        <tr class="text-center">
            <td class="text-start">
                <a href="{{ route('fuelCompanies.show', $row->id) }}">{{ $row->name }}</a>
            </td>
            <td>{{ $row->company_contact }}</td>
            <td>{{ $row->email }}</td>
            <td class="text-start small">{{ \Illuminate\Support\Str::limit($row->address, 40) }}</td>
            <td class="text-start small">
                @if($row->account)
                    {{ $row->account->account_code }} — {{ $row->account->name }}
                @else
                    —
                @endif
            </td>
            <td>
                @if($row->status == 1)
                <span class="badge bg-success">Active</span>
                @else
                <span class="badge bg-danger">Inactive</span>
                @endif
            </td>
            <td>
                <div class="btn-group">
                    @can('fuel_edit')
                    <a href="javascript:void(0);" data-action="{{ route('fuelCompanies.edit', $row->id) }}" class="btn btn-info btn-sm show-modal" data-size="lg" data-title="Update fuel company">
                        <i class="fa fa-edit"></i>
                    </a>
                    @endcan
                    @can('fuel_delete')
                    <a href="javascript:void(0);" onclick='confirmDelete("{{ route('fuelCompanies.delete', $row->id) }}")' class="btn btn-danger btn-sm confirm-modal" data-size="lg" data-title="Delete Item">
                        <i class="fa fa-trash"></i>
                    </a>
                    @endcan
                </div>
            </td>
            <td></td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
