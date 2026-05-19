@once
@push('page-scripts')
<script>
(function() {
    function initTrashDropdowns() {
        var attempts = 0;
        var maxAttempts = 10;

        function tryInitialize() {
            attempts++;

            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                dropdownElementList.forEach(function(dropdownToggleEl) {
                    try {
                        bootstrap.Dropdown.getOrCreateInstance(dropdownToggleEl);
                    } catch (e) {
                        console.warn('Failed to initialize trash dropdown:', e);
                    }
                });
            } else if (attempts < maxAttempts) {
                setTimeout(tryInitialize, 100);
            }
        }

        tryInitialize();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTrashDropdowns);
    } else {
        initTrashDropdowns();
    }
})();
</script>
@endpush
@endonce
