
<!-- Name Field -->
<div class="form-group col-sm-8">
    {!! Form::label('name', 'Name:') !!}
    @isset($roles)
    {!! Form::text('name', null, ['class' => 'form-control', 'required','readonly', 'maxlength' => 255, 'maxlength' => 255]) !!}

    @else
    {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 255, 'maxlength' => 255]) !!}
    @endisset
</div>
<br>
<h5>Role Permissions</h5>
<div class="table-responsive scrollbar" >
    <table class="table table-flush-spacing">
      <tbody>
       {{--  <tr>
          <td class="text-nowrap fw-medium">Administrator Access <i class="ti ti-info-circle" data-bs-toggle="tooltip" data-bs-placement="top" aria-label="Allows a full access to the system" data-bs-original-title="Allows a full access to the system"></i></td>
          <td>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="selectAll">
              <label class="form-check-label" for="selectAll">
                Select All
              </label>
            </div>
          </td>
        </tr> --}}
        @php
            use Spatie\Permission\Models\Permission;
            $hasParentId = \Illuminate\Support\Facades\Schema::hasColumn('permissions', 'parent_id');
            if (! isset($modules) && $hasParentId) {
              $modules = Permission::query()
                ->where(function ($q) {
                  $q->whereNull('parent_id')->orWhere('parent_id', 0);
                })
                ->get();
            }
        @endphp
        @if ($hasParentId)
        @foreach ($modules as $module)

        <tr>
          <td class="text-nowrap fw-medium">{{$module->name}}</td>
          @php
              $permissions = Permission::where('parent_id',$module->id)->get();
          @endphp
          <td>
            <div class="d-flex">
          @foreach ($permissions as $item)

                <div class="form-check me-3 me-lg-5">
                    <input class="form-check-input" name="permission[]" id="{{$item->id}}" value="{{$item->id}}" type="checkbox"
                    @isset($rolePermissions[$item->id]) checked @endisset >
                    @php
                         $name = explode('_',$item->name,2);
                        $name = ucwords(str_replace("_"," ",$name[1]));
                    @endphp
                    <label class="form-check-label" for="{{$item->id}}">{{$name}}</label>
                </div>

            @endforeach
        </div>
          </td>
        </tr>

        @endforeach
        @else
        @php
          $groupedPermissions = Permission::query()
            ->orderBy('name')
            ->get()
            ->groupBy(function ($permission) {
              $parts = explode('_', $permission->name, 2);
              if (count($parts) === 2) {
                return ucwords(str_replace('_', ' ', $parts[0]));
              }
              return ucwords(str_replace('_', ' ', $permission->name));
            });
        @endphp
        @foreach ($groupedPermissions as $moduleName => $permissions)
        <tr>
          <td class="text-nowrap fw-medium">{{ $moduleName }}</td>
          <td>
            <div class="d-flex">
              @foreach ($permissions as $item)
              <div class="form-check me-3 me-lg-5">
                <input class="form-check-input" name="permission[]" id="{{ $item->id }}" value="{{ $item->id }}" type="checkbox"
                  @isset($rolePermissions[$item->id]) checked @endisset>
                @php
                  $parts = explode('_', $item->name, 2);
                  $label = count($parts) === 2 ? ucwords(str_replace('_', ' ', $parts[1])) : ucwords(str_replace('_', ' ', $item->name));
                @endphp
                <label class="form-check-label" for="{{ $item->id }}">{{ $label }}</label>
              </div>
              @endforeach
            </div>
          </td>
        </tr>
        @endforeach
        @endif

      </tbody>
    </table>
  </div>

  @push('third_party_scripts')
     <script>
$("#selectAll").click(function(){

    $(':checkbox').each(function() {
    if(this.checked == true) {
      this.checked = false;
    } else {
      this.checked = true;
    }
  });
})
</script>
  @endpush
