@if(empty($isPdf))
<script>
    (function() {
        if (typeof window.printModalContent === 'function') {
            return;
        }
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
                '@page{size:A4 portrait;margin:8mm 10mm;}' +
                'html,body{margin:0!important;padding:0!important;background:#fff!important;' +
                'width:100%!important;min-width:100%!important;max-width:none!important;}' +
                '.invoice-box{max-width:none!important;width:100%!important;min-width:100%!important;' +
                'margin:0!important;box-shadow:none!important;border-radius:0!important;' +
                'page-break-inside:auto!important;break-inside:auto!important;}' +
                '.invoice-box .sheet{padding:0!important;width:100%!important;}' +
                '@media print{' +
                'html,body,.invoice-box{width:100%!important;max-width:none!important;}' +
                '.invoice-box{page-break-inside:auto!important;break-inside:auto!important;}' +
                '}' +
                '</style>' +
                '</head><body>' + box.outerHTML + '</body></html>'
            );
            win.document.close();
            setTimeout(function() {
                try {
                    win.focus();
                    win.print();
                } catch (e) {}
                win.onafterprint = function() {
                    win.close();
                };
            }, 400);
        };
        document.querySelectorAll('.js-print-modal-content').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.printModalContent();
            });
        });
    })();
</script>
@endif
