@if(request()->get('print'))
    {{-- Print version: old structure only (no layout wrapper) --}}
    @include('vouchers.show_fields')
@else
    <div class="row">
        @include('vouchers.show_fields')
    </div>
@endif
