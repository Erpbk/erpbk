@if(empty($isPdf))
<script>
    (function() {
        // Ensure full-page print fit is available even if server-cached custom.js is stale.
        // custom.js also defines this; this copy wins when the invoice view/modal loads.
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

                if (contentH < pageH * 0.92 && rowCount <= 12) {
                    box.classList.add('invoice-print-tall');
                    var need = pageH / contentH;
                    var padY = need > 1.45 ? '12px' : need > 1.2 ? '9px' : '7px';
                    box.querySelectorAll('th, td').forEach(function(el) {
                        el.style.setProperty('padding-top', padY, 'important');
                        el.style.setProperty('padding-bottom', padY, 'important');
                    });
                    void box.offsetHeight;
                    contentH = Math.max(box.scrollHeight, box.offsetHeight, 1);
                    contentW = Math.max(box.scrollWidth, box.offsetWidth, 1);
                }

                var scale = Math.min(pageW / contentW, pageH / contentH);
                if (scale > 1) scale = Math.min(scale, 1.9);
                else scale = Math.max(scale, 0.35);

                if (scale > 1.01) {
                    box.style.setProperty('zoom', scale.toFixed(4), 'important');
                    box.style.setProperty('transform', 'none', 'important');
                    box.style.setProperty('width', pageW + 'px', 'important');
                    void box.offsetHeight;
                    var zoomedH = Math.max(box.scrollHeight, box.offsetHeight, 1);
                    var zoomedW = Math.max(box.scrollWidth, box.offsetWidth, 1);
                    if (zoomedH > pageH + 2 || zoomedW > pageW + 2) {
                        var fix = Math.min(pageW / zoomedW, pageH / zoomedH, 1);
                        box.style.setProperty('zoom', (scale * fix).toFixed(4), 'important');
                    }
                } else if (scale < 0.999) {
                    box.style.setProperty('transform', 'scale(' + scale.toFixed(4) + ')', 'important');
                    box.style.setProperty('width', Math.round(pageW / scale) + 'px', 'important');
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
