<script>
document.addEventListener('DOMContentLoaded', function () {
    var linkModeRadios = document.querySelectorAll('.link-mode-radio');
    var linkPanel = document.getElementById('linkAccountPanel');
    var createPanel = document.getElementById('createAccountPanel');
    var accountTypeSelect = document.getElementById('account_type');
    var accountIdSelect = document.getElementById('account_id');
    var accountsByTypeUrl = @json(route('admin.global-accounts.accounts-by-type', ['type' => '__TYPE__']));
    var oldAccountId = @json(old('account_id', isset($globalAccount) ? $globalAccount->account_id : null));

    function selectedLinkMode() {
        var checked = document.querySelector('.link-mode-radio:checked');
        return checked ? checked.value : 'link';
    }

    function togglePanels() {
        var mode = selectedLinkMode();
        linkPanel.style.display = mode === 'link' ? 'block' : 'none';
        createPanel.style.display = mode === 'create' ? 'block' : 'none';

        accountIdSelect.required = mode === 'link';
        document.getElementById('account_name').required = mode === 'create';
    }

    function loadAccountsByType(type) {
        if (!type) {
            accountIdSelect.innerHTML = '<option value="">{{ __('Select account type first, then choose account') }}</option>';
            return;
        }

        var url = accountsByTypeUrl.replace('__TYPE__', encodeURIComponent(type));
        accountIdSelect.innerHTML = '<option value="">{{ __('Loading...') }}</option>';

        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                var html = '<option value="">{{ __('Select account') }}</option>';
                Object.keys(data).forEach(function (id) {
                    var selected = String(oldAccountId) === String(id) ? ' selected' : '';
                    html += '<option value="' + id + '"' + selected + '>' + data[id] + '</option>';
                });
                accountIdSelect.innerHTML = html;
            })
            .catch(function () {
                accountIdSelect.innerHTML = '<option value="">{{ __('Failed to load accounts') }}</option>';
            });
    }

    linkModeRadios.forEach(function (radio) {
        radio.addEventListener('change', togglePanels);
    });

    accountTypeSelect.addEventListener('change', function () {
        if (selectedLinkMode() === 'link') {
            loadAccountsByType(accountTypeSelect.value);
        }
    });

    togglePanels();

    if (selectedLinkMode() === 'link' && accountTypeSelect.value) {
        loadAccountsByType(accountTypeSelect.value);
    }
});
</script>
