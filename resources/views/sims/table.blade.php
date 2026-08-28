<!-- Statistics Section - Will stick with headers -->
<div class="sticky-table-header">
  <div class="sticky-statistics">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-2">
        {{-- <button style="align-content:flex-end !important" data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);">
          <i class="fa fa-search"></i>
        </button> --}}
        <div></div>
        <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter Sims</button>
      </div>
      <div class="totals-cards totals-cards-single-row">
        @php
          $statBaseQuery = request()->except(['page']);
          $currentStatus = strtolower((string) request('status', ''));
          $currentCompany = (string) request('company', '');
          $assignedActive = in_array($currentStatus, ['assigned', 'active'], true);
          $inOfficeActive = in_array($currentStatus, ['in_office', 'in-office', 'office'], true);
          $deactivatedActive = in_array($currentStatus, ['deactivated', 'inactive'], true);
          $abscondedActive = in_array($currentStatus, ['user_absconded', 'absconded'], true);
          $totalActive = $currentStatus === '' && $currentCompany === '';
          $simStatUrl = function (array $overrides) use ($statBaseQuery) {
              $params = array_merge($statBaseQuery, $overrides);
              foreach ($params as $key => $value) {
                  if ($value === null || $value === '') {
                      unset($params[$key]);
                  }
              }
              return route('sims.index', $params);
          };
        @endphp
        <a href="{{ $simStatUrl(['status' => null, 'company' => null]) }}" class="total-card total-sims{{ $totalActive ? ' is-active' : '' }}" title="{{ $totalActive ? 'Showing all SIMs' : 'Show all SIMs' }}">
          <div class="label"><i class="fa fa-sim-card"></i>Total Sims</div>
          <div class="value" id="total_orders">{{ $stats['total'] ?? 0 }}</div>
        </a>
        <a href="{{ $simStatUrl(['status' => $assignedActive ? null : 'assigned']) }}" class="total-card total-active{{ $assignedActive ? ' is-active' : '' }}" title="{{ $assignedActive ? 'Clear Assigned filter' : 'Show assigned SIMs' }}">
          <div class="label"><i class="fa fa-check-circle"></i>Assigned</div>
          <div class="value" id="avg_ontime">{{ $stats['active'] ?? 0 }}</div>
        </a>
        <a href="{{ $simStatUrl(['status' => $inOfficeActive ? null : 'in_office']) }}" class="total-card total-in-office{{ $inOfficeActive ? ' is-active' : '' }}" title="{{ $inOfficeActive ? 'Clear In office filter' : 'Show in-office SIMs' }}">
          <div class="label"><i class="fa fa-building"></i>In office</div>
          <div class="value" id="total_in_office">{{ $stats['in_office'] ?? 0 }}</div>
        </a>
        <a href="{{ $simStatUrl(['status' => $deactivatedActive ? null : 'deactivated']) }}" class="total-card total-inactive{{ $deactivatedActive ? ' is-active' : '' }}" title="{{ $deactivatedActive ? 'Clear Deactivated filter' : 'Show deactivated SIMs' }}">
          <div class="label"><i class="fa fa-times-circle"></i>Deactivated</div>
          <div class="value" id="total_rejected">{{ $stats['deactivated'] ?? 0 }}</div>
        </a>
        <a href="{{ $simStatUrl(['status' => $abscondedActive ? null : 'user_absconded']) }}" class="total-card total-user-absconded{{ $abscondedActive ? ' is-active' : '' }}" title="{{ $abscondedActive ? 'Clear User Absconded filter' : 'Show SIMs assigned to absconded users' }}">
          <div class="label"><i class="fa fa-user-secret"></i>User Absconded</div>
          <div class="value" id="total_user_absconded">{{ $stats['user_absconded'] ?? 0 }}</div>
        </a>
        @foreach(($stats['companies'] ?? []) as $i => $companyStat)
        @php
          $companyKey = (string) ($companyStat['id'] ?? '');
          $companyIsActive = $currentCompany !== '' && (
              $currentCompany === $companyKey || $currentCompany === (string) ($companyStat['name'] ?? '')
          );
        @endphp
        <a href="{{ $simStatUrl(['company' => $companyIsActive ? null : $companyKey]) }}" class="total-card total-sim-company total-sim-company-{{ $i % 3 }}{{ $companyIsActive ? ' is-active' : '' }}" title="{{ $companyIsActive ? 'Clear '.$companyStat['name'].' filter' : 'Show '.$companyStat['name'].' SIMs' }}">
          <div class="label"><i class="fa fa-building"></i>{{ $companyStat['name'] }} Sims</div>
          <div class="value">{{ $companyStat['count'] ?? 0 }}</div>
        </a>
        @endforeach
      </div>
    </div>
  </div>
</div>

<!-- Table with Scroll - SINGLE TABLE for both headers and body -->
<div class="table-scroll-container">
  <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
      <tr role="row">
        @php
        $vf = static fn (string $f): bool => field_visible('sim', $f);
        $tableCols = $tableColumns ?? [];
        $dataColumns = array_values(array_filter($tableCols, function($c) use ($vf) {
        $k = $c['data'] ?? ($c['key'] ?? null);
        return $k !== 'search' && $k !== 'control' && $vf((string) $k);
        }));
        @endphp
        @foreach($dataColumns as $col)
        @php $title = $col['title'] ?? ($col['name'] ?? ($col['data'] ?? '')); @endphp
        <th title="{{ $title }}" class="sorting" tabindex="0" rowspan="1" colspan="1">{{ $title }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($data as $r)
      <tr class="text-center" data-id="{{ $r->id }}">
        @if($vf('number'))<td>
          <a href="{{ route('sims.show', $r->id) }}" class="table-link">
            {{$r->number}}
          </a>
        </td>@endif
        @if($vf('company'))<td>{{$r->telecomCompany?->name ?? '-'}}</td>@endif
        @if($vf('emi'))<td>{{$r->emi}}</td>@endif
        @if($vf('assign_to'))<td>
          @if($r->assign_to)
          @if($r->assign_type === 'employee' && $r->employee)
          {{ $r->employee->employee_id }}
          @elseif($r->riders)
          {{ $r->riders->rider_id }}
          @else
          -
          @endif
          @else
          -
          @endif
        </td>@endif
        @if($vf('rider_name'))<td>
          @if($r->assign_to)
          @php $assigneeAbsconded = $r->assigneeIsAbsconded(); @endphp
          @if($r->assign_type === 'employee' && $r->employee)
          <a href="{{ route('employees.show', $r->employee->id) }}" class="table-link">{{ $r->employee->name }}</a>
          @elseif($r->riders)
          <a href="{{ route('riders.show', $r->riders->id) }}" class="table-link">{{ $r->riders->name }}</a>
          @else
          -
          @endif
          @if($assigneeAbsconded)
          <span class="badge bg-label-danger ms-1">Absconded</span>
          @endif
          @else
          -
          @endif
        </td>@endif
        @if($vf('vendor'))<td>
          @if($r->vendors)
          {{$r->vendors->name}}
          @else
          -
          @endif
        </td>@endif
        @if($vf('status'))<td>
          @php $simStatus = \App\Models\Sims::statusDisplay($r->status); @endphp
          <span class="badge {{ $simStatus['badge'] }}" style="font-size: 0.8rem;">{{ $simStatus['label'] }}</span>
        </td>@endif
        <td style="position: relative;">
          <div class="dropdown sim-table-action-dropdown">
            <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
              <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end sim-table-dropdown-menu" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
              @include('layouts.partials.module_contract_action', [
                'module' => 'sims',
                'recordId' => $r->id,
              ])
              @can('sims_assign_create')
              @if(!$r->assign_to)
              @if((int) $r->status === \App\Models\Sims::STATUS_DEACTIVATED)
              <span class="dropdown-item text-muted" title="Activate this SIM before assigning it.">
                <i class="fa fa-ban my-1"></i>Deactivated
              </span>
              @else
              <a href="javascript:void(0);" data-size="lg" data-title="Assign Sim" data-action="{{ route('sims.assign', $r->id) }}" class='show-modal dropdown-item waves-effect'>
                <i class="fa fa-motorcycle my-1"></i>Assign
              </a>
              @endif
              @else
              <a href="javascript:void(0);" data-size="lg" data-title="Return Sim" data-action="{{ route('sims.return', $r->id) }}" class='dropdown-item waves-effect show-modal'>
                <i class="fa fa-undo my-1"></i>Return
              </a>
              @endif
              @endcan
              @can('sims_sim_edit')
              <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Edit Sim" data-action="{{ route('sims.edit', $r->id) }} ">
                <i class="fa fa-edit my-1"></i> Edit
              </a>
              @endcan
              @can('sims_sim_delete')
              <a href="#" class='dropdown-item waves-effect'
                data-delete-url="{{ route('sims.delete', $r->id) }}"
                onclick="confirmDelete(this.dataset.deleteUrl)">
                <i class="fa fa-trash my-1"></i> Delete
              </a>
              @endcan
            </div>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
  @if($data->isEmpty())
  <div class="text-center mt-5">
    <h3>No Sims found</h3>
  </div>
  @endif
</div>

@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
@include('delete_requests._pending_table_script', ['items' => $data])
