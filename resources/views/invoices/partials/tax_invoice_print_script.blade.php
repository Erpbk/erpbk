@if(empty($isPdf))
<script>
    (function() {
        if (typeof window.fitInvoiceToSinglePage === 'function') {
            return;
        }

        /**
         * Scale .invoice-box so short/long invoices always occupy one A4 page.
         * @param {Document} doc
         */
        window.fitInvoiceToSinglePage = function(doc) {
            doc = doc || document;
            var box = doc.querySelector('.invoice-box');
            if (!box) {
                return;
            }

            // Reset any previous fit pass
            box.style.transform = '';
            box.style.width = '100%';
            box.style.maxWidth = 'none';
            box.style.margin = '0';
            box.style.transformOrigin = 'top left';

            var wrap = box.parentElement;
            if (wrap && wrap.classList && wrap.classList.contains('invoice-fit-wrap')) {
                wrap.style.height = '';
                wrap.style.overflow = '';
            }

            // Force layout with print-like full width first
            void box.offsetHeight;

            // A4 printable area ≈ 297mm - vertical margins, 210mm - horizontal margins
            // Use CSS px at 96dpi: 1mm ≈ 3.7795px
            var mm = 3.7795275591;
            var pageW = (210 - 12) * mm; // 6mm side margins
            var pageH = (297 - 12) * mm; // 6mm top/bottom margins

            var contentW = Math.max(box.scrollWidth, box.offsetWidth) || pageW;
            var contentH = Math.max(box.scrollHeight, box.offsetHeight) || 1;

            var scale = Math.min(pageW / contentW, pageH / contentH);
            // Short invoices: grow a bit to use the page; long: shrink to fit
            scale = Math.max(0.42, Math.min(1.18, scale));

            // Avoid tiny float noise
            scale = Math.round(scale * 1000) / 1000;

            if (!wrap || !wrap.classList || !wrap.classList.contains('invoice-fit-wrap')) {
                wrap = doc.createElement('div');
                wrap.className = 'invoice-fit-wrap';
                box.parentNode.insertBefore(wrap, box);
                wrap.appendChild(box);
            }

            wrap.style.width = '100%';
            wrap.style.overflow = 'hidden';
            wrap.style.height = Math.ceil(contentH * scale) + 'px';
            wrap.style.pageBreakInside = 'avoid';
            wrap.style.breakInside = 'avoid';

            box.style.transformOrigin = 'top left';
            box.style.transform = 'scale(' + scale + ')';
            box.style.width = (100 / scale) + '%';
            box.style.maxWidth = 'none';
            box.style.margin = '0';
            box.setAttribute('data-fit-scale', String(scale));
        };

        window.printModalContent = function() {
            var box = document.querySelector('.invoice-box');
            if (!box) {
                window.print();
                return;
            }
            var styles = '';
            document.querySelectorAll('style').forEach(function(node) {
                styles += node.outerHTML;
            });
            var title = (document.title || 'Invoice').replace(/</g, '');
            var win = window.open('', '_blank');
            if (!win) {
                window.fitInvoiceToSinglePage(document);
                window.print();
                return;
            }
            win.document.open();
            win.document.write(
                '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                '<meta name="viewport" content="width=794">' +
                '<title>' + title + '</title>' +
                styles +
                '<style>' +
                '@page{size:A4 portrait;margin:6mm;}' +
                'html,body{margin:0!important;padding:0!important;background:#fff!important;' +
                'width:100%!important;min-width:100%!important;max-width:none!important;}' +
                '.invoice-box{max-width:none!important;width:100%!important;min-width:100%!important;' +
                'margin:0!important;box-shadow:none!important;border-radius:0!important;' +
                'page-break-inside:avoid!important;break-inside:avoid!important;}' +
                '.invoice-box .sheet{padding:8px 10px!important;width:100%!important;}' +
                '.invoice-fit-wrap{width:100%!important;overflow:hidden!important;' +
                'page-break-inside:avoid!important;break-inside:avoid!important;}' +
                '@media print{' +
                'html,body,.invoice-box{width:100%!important;max-width:none!important;}' +
                '.invoice-box,.invoice-fit-wrap{page-break-inside:avoid!important;break-inside:avoid!important;}' +
                '.invoice-box,.invoice-fit-wrap{page-break-after:avoid!important;}' +
                '}' +
                '</style>' +
                '</head><body></body></html>'
            );
            win.document.close();

            // Move a clone so layout/fonts settle before measuring
            var clone = box.cloneNode(true);
            win.document.body.appendChild(clone);

            setTimeout(function() {
                try {
                    window.fitInvoiceToSinglePage(win.document);
                    win.focus();
                    win.print();
                } catch (e) {}
                win.onafterprint = function() {
                    win.close();
                };
            }, 450);
        };

        // Native Ctrl+P / browser print on the show page
        window.addEventListener('beforeprint', function() {
            window.fitInvoiceToSinglePage(document);
        });
        window.addEventListener('afterprint', function() {
            var box = document.querySelector('.invoice-box');
            if (!box) {
                return;
            }
            box.style.transform = '';
            box.style.width = '';
            box.removeAttribute('data-fit-scale');
            var wrap = box.closest('.invoice-fit-wrap');
            if (wrap && wrap.parentNode) {
                wrap.parentNode.insertBefore(box, wrap);
                wrap.parentNode.removeChild(wrap);
            }
        });

        document.querySelectorAll('.js-print-modal-content').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.printModalContent();
            });
        });
    })();
</script>
@endif