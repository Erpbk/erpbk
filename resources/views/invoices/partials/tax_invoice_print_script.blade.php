@if(empty($isPdf))
<script>
    (function() {
        // Same-page print only — custom.js owns window.printModalContent.
        // Bind print buttons that load after custom.js (modal AJAX content).
        function bindPrintButtons(root) {
            (root || document).querySelectorAll('.js-print-modal-content').forEach(function(btn) {
                if (btn.getAttribute('data-print-bound') === '1') {
                    return;
                }
                btn.setAttribute('data-print-bound', '1');
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (typeof window.printModalContent === 'function') {
                        window.printModalContent();
                    } else {
                        document.body.classList.add('printing-invoice');
                        window.print();
                        setTimeout(function() {
                            document.body.classList.remove('printing-invoice');
                        }, 1500);
                    }
                });
            });
        }

        bindPrintButtons(document);

        // Re-bind when invoice is loaded into the right-side modal
        if (window.jQuery) {
            $(document).on('shown.bs.modal', '#rightSideModal, #modalTop', function() {
                bindPrintButtons(this);
            });
        }
    })();
</script>
@endif
