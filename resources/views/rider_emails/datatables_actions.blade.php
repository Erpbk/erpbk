{!! Form::open(['route' => ['riderEmails.destroy', $id], 'method' => 'delete']) !!}
<div class='btn-group'>
    @can('email_view')
    <a href="javascript:void(0);" data-action="{{ route('riderEmails.show', $id) }}" data-title="View Email" data-size="md" class='btn btn-default btn-sm show-modal'>
        <i class="fa fa-eye"></i>
    </a>
    @endcan
</div>
{!! Form::close() !!}
