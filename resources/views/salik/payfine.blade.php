<form action="{{ route('salik.payfine') }}" method="POST">
    @csrf
    <input type="hidden" name="id" value="{{ $data->id }}">
    <div class="row">
        <div class="form-group col-sm-6">
            <label class="account_id">Select Account:</label>
            <select class="form select select2" id="account_id" name="account" required>
                <option value=""></option>
                @foreach(DB::table('accounts')->where('status', 1)->orderBy('id', 'desc')->get() as $b)
                <option value="{{ $b->id }}">
                    {{ $b->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-12 form-group text-center">
            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
        </div>
    </div>
</form>