(function () {
    var processTimer = null;
    function cfg() { return window.SIM_INVOICE_IMPORT || { items: [], indexUrl: '/' }; }
    function setProgress(pct, label) {
        pct = Math.max(0, Math.min(100, Math.round(pct)));
        $('#importProgressBar').css('width', pct + '%').attr('aria-valuenow', pct).text(pct + '%');
        $('#importProgressPct').text(pct + '%');
        if (label) $('#importProgressLabel').text(label);
    }
    function startProcessingAnimation() {
        var pct = 55;
        setProgress(pct, 'Processing SIM invoice rows...');
        clearInterval(processTimer);
        processTimer = setInterval(function () {
            if (pct >= 92) return;
            pct += Math.max(0.4, (92 - pct) * 0.04);
            setProgress(pct, 'Processing SIM invoice rows...');
        }, 400);
    }
    function stopProgress(success, detailMessage) {
        clearInterval(processTimer); processTimer = null;
        setProgress(100, success ? 'Import complete' : 'Import failed');
        $('#importProgressBar').removeClass('progress-bar-animated').toggleClass('bg-success', !!success).toggleClass('bg-danger', !success);
        if (detailMessage) $('#importProgressHint').text(detailMessage);
    }
    function resetProgressUi() {
        clearInterval(processTimer); processTimer = null;
        $('#importProgressWrap').hide();
        $('#importProgressBar').addClass('progress-bar-animated progress-bar-striped').removeClass('bg-danger').addClass('bg-success');
        setProgress(0, 'Uploading file...');
        $('#importProgressHint').text('Please keep this page open until import finishes.');
    }
    $(function () {
        var $c = $('#sim_invoice_import_company_id');
        if ($.fn.select2 && $c.length) {
            if (!$c.parent().hasClass('position-relative')) $c.wrap('<div class="position-relative"></div>');
            $c.select2({ placeholder: 'Select company', allowClear: true, width: '100%', dropdownParent: $c.parent() });
        }
    });
    $('#simInvoiceImportForm').on('submit', function (e) {
        e.preventDefault();
        var formData = new FormData(this);
        var invoiceIndexUrl = cfg().indexUrl;
        $('#importBtn').prop('disabled', true);
        $('#importResult').hide().empty();
        resetProgressUi();
        $('#importProgressWrap').show();
        setProgress(0, 'Uploading file...');
        $.ajax({
            url: $(this).attr('action'), type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            xhr: function () {
                var xhr = $.ajaxSettings.xhr();
                if (xhr.upload) {
                    xhr.upload.addEventListener('progress', function (event) {
                        if (!event.lengthComputable) return;
                        setProgress((event.loaded / event.total) * 50, 'Uploading file...');
                        if (event.loaded >= event.total) startProcessingAnimation();
                    }, false);
                }
                return xhr;
            },
            beforeSend: function () { setTimeout(function () { if (!processTimer) startProcessingAnimation(); }, 800); },
            success: function (response) {
                var imported = parseInt((response && response.imported_count) || 0, 10) || 0;
                var skipped = parseInt((response && response.skipped_count) || 0, 10) || 0;
                var skippedLog = Array.isArray(response && response.skipped_log) ? response.skipped_log : [];
                stopProgress(true, imported + ' SIM(s) imported' + (skipped ? (', ' + skipped + ' skipped') : ''));
                var message = (response && response.message) || ('Import finished. Imported: ' + imported + '.');
                var html = '<div class="alert alert-success mb-2">' + $('<div>').text(message).html() + '</div>';
                if (skippedLog.length) {
                    html += '<div class="alert alert-warning mb-2"><strong>Skipped:</strong><ul class="mb-0 mt-2 pl-3" style="max-height:240px;overflow:auto;">';
                    skippedLog.forEach(function (line) { html += '<li>' + $('<div>').text(line).html() + '</li>'; });
                    html += '</ul></div><div class="text-center"><a href="' + invoiceIndexUrl + '" class="btn btn-primary">Back to SIM Invoices</a></div>';
                }
                $('#importProgressWrap').hide();
                $('#importResult').html(html).show();
                $('#importBtn').prop('disabled', false);
                if (!skippedLog.length) setTimeout(function () { window.location.href = invoiceIndexUrl; }, 1500);
            },
            error: function (xhr) {
                stopProgress(false, 'Import failed');
                var message = (xhr.responseJSON && xhr.responseJSON.message) || 'Import failed';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    var firstKey = Object.keys(xhr.responseJSON.errors)[0];
                    var firstError = xhr.responseJSON.errors[firstKey];
                    message = Array.isArray(firstError) ? (firstError[0] || message) : (firstError || message);
                }
                $('#importProgressWrap').hide();
                $('#importResult').html('<div class="alert alert-danger">' + $('<div>').text(message).html() + '</div>').show();
                $('#importBtn').prop('disabled', false);
            }
        });
    });
})();

(function () {
    var SIM_ITEMS = (window.SIM_INVOICE_IMPORT && window.SIM_INVOICE_IMPORT.items) ? window.SIM_INVOICE_IMPORT.items : [];
    var FIELDS = [
        { key: 'sim_number', label: 'SIM Number', required: true, match: [/sim/, /msisdn/, /mobile/, /\bnumber\b/] }
    ];
    var TOTAL_REQUIRED = FIELDS.filter(function (f) { return f.required; }).length;
    var MAX_COLS = 60;
    var PREVIEW_ROWS = 20;
    var itemRowIndex = 0;

    var previewActive = false;
    var headers = [];
    var colCount = 0;

    function colLetter(n) {
        var s = '';
        while (n > 0) {
            var m = (n - 1) % 26;
            s = String.fromCharCode(65 + m) + s;
            n = Math.floor((n - 1) / 26);
        }
        return s;
    }

    function normalize(text) {
        return String(text || '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function truncate(text, len) {
        text = String(text == null ? '' : text);
        return text.length > len ? text.slice(0, len) + '…' : text;
    }

    function findItemById(id) {
        id = parseInt(id, 10);
        for (var i = 0; i < SIM_ITEMS.length; i++) {
            if (parseInt(SIM_ITEMS[i].id, 10) === id) {
                return SIM_ITEMS[i];
            }
        }
        return null;
    }

    function findItemByName(name) {
        var n = normalize(name);
        if (!n) return null;
        for (var i = 0; i < SIM_ITEMS.length; i++) {
            if (normalize(SIM_ITEMS[i].name) === n) {
                return SIM_ITEMS[i];
            }
        }
        return null;
    }

    function columnOptionsHtml(selected) {
        var html = '<option value="">Select column…</option>';
        for (var c = 1; c <= colCount; c++) {
            var label = colLetter(c);
            var h = String(headers[c - 1] || '').trim();
            if (h) {
                label += ' — ' + truncate(h, 28);
            }
            html += '<option value="' + c + '"' + (selected == c ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
        }
        return html;
    }

    function itemOptionsHtml(selectedId) {
        var html = '<option value="">Select item…</option>';
        SIM_ITEMS.forEach(function (item) {
            html += '<option value="' + item.id + '"'
                + ' data-price="' + item.price + '"'
                + ' data-vat="' + item.vat + '"'
                + (selectedId && parseInt(selectedId, 10) === parseInt(item.id, 10) ? ' selected' : '')
                + '>' + escapeHtml(item.name) + '</option>';
        });
        return html;
    }

    function addItemMapRow(opts) {
        opts = opts || {};
        var idx = itemRowIndex++;
        var rate = opts.rate != null ? opts.rate : '';
        var html = ''
            + '<div class="item-map-row" data-index="' + idx + '">'
            + '  <div class="row align-items-center mb-2">'
            + '    <div class="form-group col-md-8 mb-0">'
            + '      <select name="item_map[' + idx + '][item_id]" class="form-control item-select" ' + (previewActive ? '' : 'disabled') + '>'
            + itemOptionsHtml(opts.item_id)
            + '      </select>'
            + '    </div>'
            + '    <div class="col-md-4 mb-0 text-right">'
            + '      <button type="button" class="btn btn-link text-danger p-0 btn-remove-item-row" title="Remove">'
            + '        <i class="fas fa-trash"></i>'
            + '      </button>'
            + '    </div>'
            + '  </div>'
            + '  <div class="row">'
            + '    <div class="form-group col-md-6 mb-0">'
            + '      <label>Qty Column</label>'
            + '      <select name="item_map[' + idx + '][col]" class="form-control item-col-select" ' + (previewActive ? '' : 'disabled') + '>'
            + (previewActive ? columnOptionsHtml(opts.col) : '<option value="">Select a file first…</option>')
            + '      </select>'
            + '    </div>'
            + '    <div class="form-group col-md-6 mb-0">'
            + '      <label>Rate</label>'
            + '      <input type="number" step="any" name="item_map[' + idx + '][rate]" class="form-control item-rate" value="' + escapeHtml(rate) + '">'
            + '    </div>'
            + '  </div>'
            + '</div>';
        $('#itemMapRows').append(html);
        updateUi();
    }

    function getMapping() {
        var mapping = {};
        FIELDS.forEach(function (f) {
            var v = $('#col_' + f.key).val();
            mapping[f.key] = v ? parseInt(v, 10) : null;
        });
        return mapping;
    }

    function getItemMappings() {
        var items = [];
        $('#itemMapRows .item-map-row').each(function () {
            var itemId = $(this).find('.item-select').val();
            var col = $(this).find('.item-col-select').val();
            var rate = $(this).find('.item-rate').val();
            items.push({
                item_id: itemId ? parseInt(itemId, 10) : null,
                col: col ? parseInt(col, 10) : null,
                rate: rate === '' ? null : parseFloat(rate),
                $row: $(this)
            });
        });
        return items;
    }

    function autoMap() {
        var mapping = {};
        var assigned = {};
        FIELDS.forEach(function (f) { mapping[f.key] = null; });

        FIELDS.forEach(function (f) {
            for (var i = 0; i < f.match.length && !mapping[f.key]; i++) {
                for (var c = 1; c <= colCount; c++) {
                    if (assigned[c]) continue;
                    var h = normalize(headers[c - 1]);
                    if (h && f.match[i].test(h)) {
                        mapping[f.key] = c;
                        assigned[c] = f.key;
                        break;
                    }
                }
            }
        });
        return mapping;
    }

    function autoAddItemRows(assignedCols) {
        $('#itemMapRows').empty();
        itemRowIndex = 0;
        for (var c = 1; c <= colCount; c++) {
            if (assignedCols[c]) continue;
            var item = findItemByName(headers[c - 1]);
            if (!item) continue;
            addItemMapRow({
                item_id: item.id,
                col: c,
                rate: item.price
            });
            assignedCols[c] = 'item:' + item.id;
        }
        if (!$('#itemMapRows .item-map-row').length) {
            addItemMapRow();
        }
    }

    function buildSelects(mapping) {
        $('.map-field').each(function () {
            var $wrap = $(this);
            var key = $wrap.data('field');
            var field = FIELDS.filter(function (f) { return f.key === key; })[0];
            var $sel = $wrap.find('select.map-select');
            $sel.empty().prop('disabled', false);
            $sel.append($('<option>', { value: '', text: field.required ? 'Select column…' : '— Not mapped —' }));
            for (var c = 1; c <= colCount; c++) {
                var label = colLetter(c);
                var h = String(headers[c - 1] || '').trim();
                if (h) {
                    label += ' — ' + truncate(h, 28);
                }
                $sel.append($('<option>', { value: c, text: label }));
            }
            $sel.val(mapping[key] ? String(mapping[key]) : '');
        });
    }

    function resetSelects() {
        $('.map-field').each(function () {
            $(this).find('select.map-select')
                .empty()
                .append($('<option>', { value: '', text: 'Select a file first…' }))
                .prop('disabled', true);
            $(this).removeClass('is-unmapped');
        });
    }

    function renderPreview(rows) {
        var html = '<thead><tr><th class="text-muted text-center" style="width:36px;">#</th>';
        for (var c = 1; c <= colCount; c++) {
            html += '<th class="text-center col-head" data-col="' + c + '">'
                + '<div>' + colLetter(c) + '</div>'
                + '<div class="map-badges"></div>'
                + '</th>';
        }
        html += '</tr></thead><tbody>';
        rows.forEach(function (row, idx) {
            html += '<tr><th class="text-muted text-center">' + (idx + 1) + '</th>';
            for (var c = 1; c <= colCount; c++) {
                html += '<td>' + escapeHtml(truncate(row[c - 1], 40)) + '</td>';
            }
            html += '</tr>';
        });
        html += '</tbody>';
        $('#filePreviewTable').html(html);
    }

    function collectDuplicates(mapping, itemMaps) {
        var byCol = {};
        FIELDS.forEach(function (f) {
            if (!mapping[f.key]) return;
            var c = mapping[f.key];
            (byCol[c] = byCol[c] || []).push({ type: 'header', label: f.label });
        });
        itemMaps.forEach(function (item, i) {
            if (!item.col) return;
            (byCol[item.col] = byCol[item.col] || []).push({ type: 'item', label: 'Item qty #' + (i + 1) });
        });
        var dups = [];
        Object.keys(byCol).forEach(function (c) {
            if (byCol[c].length > 1) {
                dups.push({ col: parseInt(c, 10), fields: byCol[c] });
            }
        });
        return dups;
    }

    function updateUi() {
        if (!previewActive) {
            $('#importBtn').prop('disabled', true);
            return;
        }

        var mapping = getMapping();
        var itemMaps = getItemMappings();
        var dups = collectDuplicates(mapping, itemMaps);
        var dupCols = {};
        dups.forEach(function (d) { dupCols[d.col] = true; });

        var itemIdCounts = {};
        var validItems = 0;
        itemMaps.forEach(function (item) {
            if (item.item_id) {
                itemIdCounts[item.item_id] = (itemIdCounts[item.item_id] || 0) + 1;
            }
        });
        itemMaps.forEach(function (item) {
            var ok = item.item_id && item.col && item.rate !== null && !isNaN(item.rate);
            var dupItem = item.item_id && itemIdCounts[item.item_id] > 1;
            item.$row.toggleClass('is-invalid-row', !ok || dupItem);
            if (ok && !dupItem) validItems++;
        });
        var duplicateItems = Object.keys(itemIdCounts).some(function (id) {
            return itemIdCounts[id] > 1;
        });

        $('#filePreviewTable .col-head').each(function () {
            var c = parseInt($(this).attr('data-col'), 10);
            var $badges = $(this).find('.map-badges').empty();
            var mapped = false;
            FIELDS.forEach(function (f) {
                if (mapping[f.key] !== c) return;
                mapped = true;
                var cls = dupCols[c] ? 'bg-danger' : (f.required ? 'bg-primary' : 'bg-secondary');
                $badges.append('<span class="badge ' + cls + ' text-white">' + f.label + '</span>');
            });
            itemMaps.forEach(function (item, i) {
                if (item.col !== c) return;
                mapped = true;
                var itemMeta = findItemById(item.item_id);
                var label = itemMeta ? truncate(itemMeta.name, 16) : ('Item #' + (i + 1));
                $badges.append('<span class="badge ' + (dupCols[c] ? 'bg-danger' : 'bg-info') + ' text-white">' + escapeHtml(label) + '</span>');
            });
            $(this).toggleClass('mapped', mapped && !dupCols[c]);
            $(this).toggleClass('dup-col', !!dupCols[c]);
        });

        $('.map-field[data-required="1"]').each(function () {
            var key = $(this).data('field');
            $(this).toggleClass('is-unmapped', !mapping[key]);
        });

        var mappedRequired = FIELDS.filter(function (f) { return f.required && mapping[f.key]; }).length;
        var complete = mappedRequired === TOTAL_REQUIRED && dups.length === 0 && validItems > 0 && !duplicateItems;
        $('#mappingStatus')
            .text(mappedRequired + ' of ' + TOTAL_REQUIRED + ' required + ' + validItems + ' item(s)')
            .toggleClass('complete', complete)
            .toggleClass('incomplete', !complete);

        var warnings = [];
        if (dups.length) {
            var parts = dups.map(function (d) {
                var names = d.fields.map(function (f) { return f.label; }).join(' and ');
                return 'column ' + colLetter(d.col) + ' is assigned to ' + names;
            });
            warnings.push('Duplicate mapping: ' + parts.join('; ') + '.');
        }
        if (duplicateItems) {
            warnings.push('Each item can only be mapped once.');
        }
        if (validItems === 0) {
            warnings.push('Add at least one complete item column mapping.');
        }
        if (warnings.length) {
            $('#mappingWarning').text(warnings.join(' ')).show();
        } else {
            $('#mappingWarning').hide().empty();
        }

        $('#importBtn').prop('disabled', !complete);
    }

    function showPreviewMode(fileName, rows) {
        previewActive = true;
        $('#fileReadError').hide();
        $('#previewFileName').text('— ' + fileName);
        $('#previewEmpty').hide();
        $('#previewTableWrap').show();
        renderPreview(rows);
        var mapping = autoMap();
        buildSelects(mapping);
        var assigned = {};
        Object.keys(mapping).forEach(function (k) {
            if (mapping[k]) assigned[mapping[k]] = k;
        });
        autoAddItemRows(assigned);
        $('#addItemMapRow').prop('disabled', false);
        updateUi();
    }

    function resetMappingUi(showError) {
        previewActive = false;
        resetSelects();
        $('#itemMapRows').empty();
        itemRowIndex = 0;
        $('#addItemMapRow').prop('disabled', true);
        $('#previewFileName').text('');
        $('#mappingStatus').text('').removeClass('complete incomplete');
        $('#filePreviewTable').empty();
        $('#previewTableWrap').hide();
        $('#previewEmpty').css('display', 'flex');
        $('#mappingWarning').hide().empty();
        $('#importBtn').prop('disabled', true);
        $('#fileReadError').toggle(!!showError);
    }

    $(document).on('change', '.map-select', updateUi);
    $(document).on('change input', '.item-select, .item-col-select, .item-rate', updateUi);

    $(document).on('change', '.item-select', function () {
        var $row = $(this).closest('.item-map-row');
        var item = findItemById($(this).val());
        if (!item) return;
        $row.find('.item-rate').val(item.price);
        updateUi();
    });

    $('#addItemMapRow').on('click', function () {
        if (!previewActive) return;
        addItemMapRow();
    });

    $(document).on('click', '.btn-remove-item-row', function () {
        $(this).closest('.item-map-row').remove();
        updateUi();
    });

    $('#toggleMappingHelp').on('click', function () {
        $('#mappingHelpBox').slideToggle(150);
    });

    $('#file').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) {
            resetMappingUi(false);
            return;
        }
        var reader = new FileReader();
        reader.onload = function (e) {
            try {
                var workbook = XLSX.read(new Uint8Array(e.target.result), { type: 'array', sheetRows: PREVIEW_ROWS + 1 });
                var sheet = workbook.Sheets[workbook.SheetNames[0]];
                var rows = XLSX.utils.sheet_to_json(sheet, { header: 1, raw: false, defval: '' });
                colCount = 0;
                rows.forEach(function (row) { colCount = Math.max(colCount, row.length); });
                colCount = Math.min(colCount, MAX_COLS);
                if (!rows.length || !colCount) {
                    throw new Error('Empty sheet');
                }
                headers = (rows[0] || []).slice(0, colCount);
                showPreviewMode(file.name, rows.slice(0, PREVIEW_ROWS));
            } catch (err) {
                resetMappingUi(true);
            }
        };
        reader.onerror = function () {
            resetMappingUi(true);
        };
        reader.readAsArrayBuffer(file);
    });

})();
