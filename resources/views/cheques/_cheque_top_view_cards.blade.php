@php
$chequeTopViewCategories = \App\Models\ChequeTopCategory::with(['options' => function ($q) {
    $q->where('is_active', 1)->orderBy('display_order')->orderBy('id');
}])->where('show_in_view_cards', 1)->orderBy('display_order')->orderBy('id')->get();
$selectedChequeTopOptionId = (int) ($cheque->cheque_top_option_id ?? 0);
@endphp
@if($chequeTopViewCategories->sum(fn ($c) => $c->options->count()) > 0)
<div class="mb-4">
    <h6 class="text-muted small text-uppercase mb-2">Cheque Top</h6>
    <div class="d-flex flex-wrap gap-2" id="cheque-top-view-cards">
        @foreach($chequeTopViewCategories as $category)
        @foreach($category->options as $option)
        @php $isSelected = $selectedChequeTopOptionId === (int) $option->id; @endphp
        <label class="btn btn-sm {{ $isSelected ? 'btn-primary' : 'btn-outline-secondary' }} cheque-top-view-card mb-0">
            <input type="radio" name="cheque_top_view_option" class="d-none cheque-top-option-radio" value="{{ $option->id }}" data-cheque-id="{{ $cheque->id }}" {{ $isSelected ? 'checked' : '' }}>
            <span>{{ $option->name }}</span>
            <span class="opacity-75 ms-1">({{ $category->name }})</span>
        </label>
        @endforeach
        @endforeach
        @if($selectedChequeTopOptionId > 0)
        <button type="button" class="btn btn-sm btn-outline-danger" id="chequeTopViewClearBtn" data-cheque-id="{{ $cheque->id }}">Clear</button>
        @endif
    </div>
</div>
<script>
(function() {
    var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    var setUrlTemplate = @json(route('cheques.setChequeTopOption', ['id' => '__ID__']));
    function postOption(chequeId, optionId) {
        var url = setUrlTemplate.replace('__ID__', chequeId);
        var fd = new FormData();
        fd.append('_token', csrf);
        if (optionId) fd.append('option_id', optionId);
        return fetch(url, { method: 'POST', body: fd, headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function(r) { return r.json(); });
    }
    document.querySelectorAll('.cheque-top-option-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            if (!this.checked) return;
            postOption(this.getAttribute('data-cheque-id'), this.value).then(function(data) {
                if (!data.success && typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not update.' });
            });
        });
    });
    var clearBtn = document.getElementById('chequeTopViewClearBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            postOption(this.getAttribute('data-cheque-id'), null).then(function(data) {
                if (data.success) window.location.reload();
            });
        });
    }
})();
</script>
@endif
