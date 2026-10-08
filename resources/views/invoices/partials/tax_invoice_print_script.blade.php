@if(empty($isPdf))
<script>
    (function() {
        // Fit the full invoice on a single A4 page (scale down when tall; natural spacing when short).
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
                // A4 content box @ 96dpi — matches @page { margin: 4mm }
                var mm = 96 / 25.4;
                var pageW = Math.round(202 * mm);
                var pageH = Math.round(289 * mm);

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

                // Force full-page width so wide tables don't shrink the whole sheet
                box.querySelectorAll('.sheet, .tbl-wrap, .parties, .desc, table').forEach(function(el) {
                    el.style.setProperty('width', '100%', 'important');
                    el.style.setProperty('max-width', 'none', 'important');
                });
                box.querySelectorAll('.tbl-wrap, .rider-template-items').forEach(function(el) {
                    el.style.setProperty('overflow', 'hidden', 'important');
                });
                void box.offsetHeight;

                var contentH = Math.max(box.scrollHeight, box.offsetHeight, 1);

                if (contentH > pageH + 2) {
                    // Long invoice: scale by HEIGHT only, keep layout width so print fills page width
                    var scaleDown = Math.max(0.35, Math.min(1, pageH / contentH));
                    box.style.setProperty('width', Math.round(pageW / scaleDown) + 'px', 'important');
                    box.style.setProperty('transform', 'scale(' + scaleDown.toFixed(4) + ')', 'important');
                } else {
                    // Short invoice: stretch to full page height + width
                    fitWrap.classList.add('is-fill');
                    box.classList.add('invoice-print-tall');
                    box.classList.remove('invoice-print-dense', 'invoice-print-ultra');

                    box.style.setProperty('width', pageW + 'px', 'important');
                    box.style.setProperty('height', '100%', 'important');
                    box.style.setProperty('display', 'flex', 'important');
                    box.style.setProperty('flex-direction', 'column', 'important');

                    var sheet = box.querySelector('.sheet');
                    if (sheet) {
                        sheet.style.setProperty('height', '100%', 'important');
                        sheet.style.setProperty('min-height', '100%', 'important');
                        sheet.style.setProperty('width', '100%', 'important');
                        sheet.style.setProperty('display', 'flex', 'important');
                        sheet.style.setProperty('flex-direction', 'column', 'important');
                        sheet.style.setProperty('justify-content', 'flex-start', 'important');
                        sheet.style.setProperty('box-sizing', 'border-box', 'important');
                        sheet.style.setProperty('padding', '8px 4px', 'important');
                    }

                    var items = box.querySelector('.rider-template-items') || box.querySelector('.tbl-wrap');
                    if (items) {
                        items.style.setProperty('flex', '1 1 auto', 'important');
                        items.style.setProperty('width', '100%', 'important');
                    }

                    var foot = box.querySelector('.inv-footnotes') || box.querySelector('.inv-note-box') || box.querySelector('.foot');
                    if (foot) {
                        foot.style.setProperty('margin-top', 'auto', 'important');
                        foot.style.setProperty('width', '100%', 'important');
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
