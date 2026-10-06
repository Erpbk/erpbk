@if(empty($isPdf))
<script>
    (function() {
        // Same-page full A4 print. Defined here so it works even if server custom.js is cached/stale.
        window.printModalContent = function printModalContent() {
            if (window.__invoicePrinting) {
                return;
            }
            window.__invoicePrinting = true;

            var source = document.querySelector('#rightSideModalBody .invoice-box') ||
                document.querySelector('.invoice-box');

            if (!source) {
                window.__invoicePrinting = false;
                window.print();
                return;
            }

            var printRoot = document.getElementById('invoice-print-root');
            if (!printRoot) {
                printRoot = document.createElement('div');
                printRoot.id = 'invoice-print-root';
                printRoot.setAttribute('aria-hidden', 'true');
                document.body.appendChild(printRoot);
            }

            printRoot.innerHTML = '';
            var box = source.cloneNode(true);
            box.querySelectorAll('.controls, .no-print, script').forEach(function(el) {
                if (el.parentNode) el.parentNode.removeChild(el);
            });

            var rowCount = box.querySelectorAll('table tbody tr').length;
            box.classList.remove('invoice-print-dense', 'invoice-print-ultra', 'invoice-print-tall');
            if (rowCount > 22) box.classList.add('invoice-print-ultra');
            else if (rowCount > 12) box.classList.add('invoice-print-dense');

            var fitWrap = document.createElement('div');
            fitWrap.className = 'invoice-print-fit is-page-frame';
            fitWrap.appendChild(box);
            printRoot.appendChild(fitWrap);
            document.body.classList.add('printing-invoice');

            var runPrint = function() {
                var mm = 96 / 25.4;
                var pageW = Math.round(198 * mm);
                var pageH = Math.round(285 * mm);

                printRoot.style.cssText =
                    'position:fixed;left:0;top:0;width:' + pageW + 'px;height:' + pageH +
                    'px;visibility:hidden;pointer-events:none;z-index:-1;overflow:hidden;';

                fitWrap.style.cssText = '';
                fitWrap.className = 'invoice-print-fit is-page-frame';
                fitWrap.style.setProperty('width', pageW + 'px', 'important');
                fitWrap.style.setProperty('height', pageH + 'px', 'important');
                fitWrap.style.setProperty('max-width', 'none', 'important');
                fitWrap.style.setProperty('margin', '0', 'important');
                fitWrap.style.setProperty('overflow', 'hidden', 'important');
                fitWrap.style.setProperty('box-sizing', 'border-box', 'important');

                box.style.cssText = '';
                box.style.setProperty('width', pageW + 'px', 'important');
                box.style.setProperty('max-width', 'none', 'important');
                box.style.setProperty('height', 'auto', 'important');
                box.style.setProperty('margin', '0', 'important');
                box.style.setProperty('transform', 'none', 'important');
                box.style.setProperty('zoom', '1', 'important');
                box.style.setProperty('transform-origin', 'top left', 'important');
                void box.offsetHeight;

                var contentH = Math.max(box.scrollHeight, box.offsetHeight, 1);
                var contentW = Math.max(box.scrollWidth, box.offsetWidth, 1);

                if (contentH > pageH + 2) {
                    var scaleDown = Math.min(pageW / contentW, pageH / contentH);
                    scaleDown = Math.max(0.35, Math.min(1, scaleDown));
                    box.style.setProperty('transform', 'scale(' + scaleDown.toFixed(4) + ')', 'important');
                    box.style.setProperty('width', Math.round(pageW / scaleDown) + 'px', 'important');
                } else {
                    // Full-page stretch (zoom-up is ignored by many print engines / servers)
                    fitWrap.classList.add('is-fill');
                    box.classList.add('invoice-print-tall');
                    box.classList.remove('invoice-print-dense', 'invoice-print-ultra');

                    var need = pageH / Math.max(contentH, 1);
                    var padY = need > 1.5 ? '14px' : need > 1.25 ? '11px' : need > 1.08 ? '9px' : '7px';
                    box.querySelectorAll('th, td').forEach(function(el) {
                        el.style.setProperty('padding-top', padY, 'important');
                        el.style.setProperty('padding-bottom', padY, 'important');
                    });

                    box.style.setProperty('height', '100%', 'important');
                    box.style.setProperty('display', 'flex', 'important');
                    box.style.setProperty('flex-direction', 'column', 'important');

                    var sheet = box.querySelector('.sheet');
                    if (sheet) {
                        sheet.style.setProperty('height', '100%', 'important');
                        sheet.style.setProperty('min-height', '100%', 'important');
                        sheet.style.setProperty('display', 'flex', 'important');
                        sheet.style.setProperty('flex-direction', 'column', 'important');
                        sheet.style.setProperty('justify-content', 'space-between', 'important');
                        sheet.style.setProperty('box-sizing', 'border-box', 'important');
                    }

                    var items = box.querySelector('.rider-template-items') || box.querySelector('.tbl-wrap');
                    if (items) {
                        items.style.setProperty('flex', '1 1 auto', 'important');
                        items.style.setProperty('display', 'flex', 'important');
                        items.style.setProperty('flex-direction', 'column', 'important');
                        items.style.setProperty('justify-content', 'space-evenly', 'important');
                    }
                }

                window.print();
            };

            var cleanup = function() {
                document.body.classList.remove('printing-invoice');
                window.__invoicePrinting = false;
                if (printRoot) {
                    printRoot.innerHTML = '';
                    printRoot.removeAttribute('style');
                }
                window.removeEventListener('afterprint', cleanup);
            };

            window.addEventListener('afterprint', cleanup);
            setTimeout(cleanup, 5000);
            setTimeout(runPrint, 200);
        };

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
                        window.print();
                    }
                });
            });
        }

        bindPrintButtons(document);

        if (window.jQuery) {
            $(document).on('shown.bs.modal', '#rightSideModal, #modalTop', function() {
                bindPrintButtons(this);
            });
        }
    })();
</script>
@endif
