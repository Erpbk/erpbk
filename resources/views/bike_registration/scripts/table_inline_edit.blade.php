<script>
(function() {
    function bikeInlineScope() {
        return document.getElementById('bike-registration-inline-edit-scope');
    }

    function editBrField(id, field) {
        var scope = bikeInlineScope();
        if (!scope) return;
        var input = scope.querySelector('#' + field + '_input_' + id);
        var display = scope.querySelector('#' + field + '_display_' + id);
        if (!input || !display) return;
        display.classList.add('d-none');
        input.classList.remove('d-none');
        input.focus();
        if (field === 'amount') input.select();
    }

    function saveBrInline(id) {
        var scope = bikeInlineScope();
        if (!scope) return;
        var amountInput = scope.querySelector('#amount_input_' + id);
        var dateInput = scope.querySelector('#date_input_' + id);
        var billingInput = scope.querySelector('#billing_input_' + id);
        if (!amountInput || !dateInput || !billingInput) return;

        var amount = amountInput.value;
        var date = dateInput.value;
        var billingMonth = billingInput.value;
        if (!amount || !date || !billingMonth) return;

        var payload = new FormData();
        payload.append('_token', '{{ csrf_token() }}');
        payload.append('id', id);
        payload.append('amount', amount);
        payload.append('date', date);
        payload.append('billing_month', billingMonth);

        fetch('{{ route("BikeRegistration.inlineUpdate") }}', {
            method: 'POST',
            body: payload,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }).then(function(res) {
            return res.json();
        }).then(function(data) {
            if (!data.success) throw new Error(data.message || 'Update failed');
            var scope = bikeInlineScope();
            if (!scope) return;
            var amountDisp = scope.querySelector('#amount_display_' + id);
            var dateDisp = scope.querySelector('#date_display_' + id);
            var billDisp = scope.querySelector('#billing_display_' + id);
            if (amountDisp) amountDisp.textContent = data.amount;
            var d = new Date(data.date);
            if (dateDisp) dateDisp.textContent = d.toLocaleDateString('en-GB', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
            var bm = new Date(data.billing_month + '-01');
            if (billDisp) billDisp.textContent = bm.toLocaleDateString('en-US', {
                month: 'short',
                year: 'numeric'
            });
        }).catch(function() {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Could not update entry.'
                });
            }
        }).finally(function() {
            var scope = bikeInlineScope();
            if (!scope) return;
            ['amount', 'date', 'billing'].forEach(function(field) {
                var input = scope.querySelector('#' + field + '_input_' + id);
                var display = scope.querySelector('#' + field + '_display_' + id);
                if (input && display) {
                    input.classList.add('d-none');
                    display.classList.remove('d-none');
                }
            });
        });
    }

    document.addEventListener('click', function(e) {
        var editLink = e.target.closest('.js-edit-br-field');
        if (!editLink) return;
        editBrField(editLink.getAttribute('data-id'), editLink.getAttribute('data-field'));
    });

    document.addEventListener('blur', function(e) {
        var input = e.target.closest('[id^="amount_input_"], [id^="date_input_"], [id^="billing_input_"]');
        if (!input || !input.closest('#bike-registration-inline-edit-scope')) return;
        var id = (input.id.split('_').pop() || '').trim();
        if (id) saveBrInline(id);
    }, true);

    document.addEventListener('keydown', function(e) {
        var input = e.target.closest('[id^="amount_input_"], [id^="date_input_"], [id^="billing_input_"]');
        if (!input || !input.closest('#bike-registration-inline-edit-scope') || e.key !== 'Enter') return;
        e.preventDefault();
        var id = (input.id.split('_').pop() || '').trim();
        if (id) saveBrInline(id);
    });
})();
</script>
