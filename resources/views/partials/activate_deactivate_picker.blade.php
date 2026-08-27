{{--
    Shared bulk activate/deactivate picker (fuel cards, SIMs).

    Expects:
      $formUrl          route the form posts to
      $idsField         request field for the selected ids (e.g. 'card_ids')
      $itemSingular     e.g. 'card'
      $itemPlural       e.g. 'cards'
      $deactivateItems  list of ['id','primary','secondary'] currently In Office
      $activateItems    list of ['id','primary','secondary'] currently Deactivated
      $deactivateHint   explanatory text for the deactivate mode
      $activateHint     explanatory text for the activate mode
--}}
@php
    $panels = [
        'deactivate' => ['items' => $deactivateItems, 'hint' => $deactivateHint, 'empty' => 'No in-office ' . $itemPlural . ' available.'],
        'activate' => ['items' => $activateItems, 'hint' => $activateHint, 'empty' => 'No deactivated ' . $itemPlural . ' available.'],
    ];
@endphp

<style>
.ad-picker .ad-hint {
    display: flex;
    gap: 10px;
    align-items: flex-start;
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 10px;
    padding: 12px 14px;
    margin-bottom: 16px;
    color: #9a3412;
    font-size: 13px;
    line-height: 1.45;
}
.ad-picker .ad-hint i {
    font-size: 18px;
    margin-top: 1px;
    color: #ea580c;
}
.ad-picker .ad-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 10px;
}
.ad-picker .ad-search {
    position: relative;
    flex: 1 1 220px;
    min-width: 180px;
}
.ad-picker .ad-search i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
    pointer-events: none;
}
.ad-picker .ad-search input {
    padding-left: 36px;
    border-radius: 8px;
}
.ad-picker .ad-count {
    background: #eef2ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    font-weight: 600;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
}
.ad-picker .ad-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
}
.ad-picker .ad-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    color: #1f2937;
    background: #fff;
    transition: background .15s ease;
}
.ad-picker .ad-row:last-of-type {
    border-bottom: none;
}
.ad-picker .ad-row:hover {
    background: #f8fafc;
}
.ad-picker .ad-row.is-checked {
    background: #eef2ff;
}
.ad-picker .ad-row.is-hidden {
    display: none;
}
.ad-picker .ad-row .form-check-input {
    margin: 0;
    flex-shrink: 0;
    cursor: pointer;
}
.ad-picker .ad-primary {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
    letter-spacing: .2px;
}
.ad-picker .ad-secondary {
    margin-left: auto;
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 999px;
    white-space: nowrap;
}
.ad-picker .ad-empty,
.ad-picker .ad-none {
    padding: 28px 16px;
    text-align: center;
    color: #64748b;
    font-size: 13px;
}
.ad-picker .ad-none {
    display: none;
}
.ad-picker .ad-panel {
    display: none;
}
.ad-picker .ad-panel.is-active {
    display: block;
}
.ad-picker .ad-placeholder {
    padding: 32px 16px;
    text-align: center;
    color: #64748b;
    font-size: 13px;
    border: 1px dashed #e5e7eb;
    border-radius: 10px;
}
</style>

{!! Form::open(['url' => $formUrl, 'method' => 'post', 'id' => 'formajax']) !!}

<div class="card-body ad-picker px-0 pb-0">
    <div class="mb-3">
        <label for="adMode" class="form-label">Action <span class="text-danger">*</span></label>
        <select name="mode" id="adMode" class="form-control" required>
            <option value="">Select an action</option>
            <option value="deactivate">Deactivate {{ $itemPlural }}</option>
            <option value="activate">Activate {{ $itemPlural }}</option>
        </select>
        <small class="text-muted">Choose an action to load the {{ $itemPlural }} eligible for it.</small>
    </div>

    <div class="ad-placeholder" id="adPlaceholder">
        Select an action above to choose {{ $itemPlural }}.
    </div>

    @foreach($panels as $mode => $panel)
    <div class="ad-panel" data-mode="{{ $mode }}">
        <div class="ad-hint">
            <i class="ti ti-info-circle"></i>
            <div>{{ $panel['hint'] }}</div>
        </div>

        @if(count($panel['items']) === 0)
        <div class="ad-list">
            <div class="ad-empty">
                <i class="ti ti-inbox d-block mb-2" style="font-size: 28px; color: #cbd5e1;"></i>
                {{ $panel['empty'] }}
            </div>
        </div>
        @else
        <div class="ad-toolbar">
            <div class="ad-search">
                <i class="ti ti-search"></i>
                <input type="text" class="form-control ad-search-input" placeholder="Search" autocomplete="off">
            </div>
            <span class="ad-count">0 selected</span>
            <button type="button" class="btn btn-sm btn-outline-primary ad-select-all">Select all</button>
            <button type="button" class="btn btn-sm btn-outline-secondary ad-clear">Clear</button>
        </div>

        <div class="ad-list">
            @foreach($panel['items'] as $item)
            <label class="ad-row" data-search="{{ strtolower(trim($item['primary'] . ' ' . ($item['secondary'] ?? ''))) }}">
                <input class="form-check-input ad-check" type="checkbox" name="{{ $idsField }}[]" value="{{ $item['id'] }}" disabled>
                <span class="ad-primary">{{ $item['primary'] }}</span>
                @if(!empty($item['secondary']))
                <span class="ad-secondary">{{ $item['secondary'] }}</span>
                @endif
            </label>
            @endforeach
            <div class="ad-none">No {{ $itemPlural }} match your search.</div>
        </div>
        @endif
    </div>
    @endforeach
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary" id="adSubmit" disabled>Save</button>
</div>

{!! Form::close() !!}

<script>
(function() {
    const root = document.querySelector('.ad-picker');
    if (!root) {
        return;
    }

    const modeSelect = root.querySelector('#adMode');
    const placeholder = root.querySelector('#adPlaceholder');
    const panels = Array.from(root.querySelectorAll('.ad-panel'));
    const submitBtn = document.getElementById('adSubmit');
    const itemSingular = @json($itemSingular);
    const itemPlural = @json($itemPlural);

    function activePanel() {
        return panels.find(function(panel) {
            return panel.classList.contains('is-active');
        }) || null;
    }

    function checksIn(panel) {
        return Array.from(panel.querySelectorAll('.ad-check'));
    }

    function syncUi() {
        const panel = activePanel();
        if (!panel) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Save';
            return;
        }

        const verb = panel.getAttribute('data-mode') === 'activate' ? 'Activate' : 'Deactivate';
        const selected = checksIn(panel).filter(function(input) { return input.checked; }).length;
        const countEl = panel.querySelector('.ad-count');
        if (countEl) {
            countEl.textContent = selected === 1 ? '1 selected' : selected + ' selected';
        }

        submitBtn.disabled = selected === 0;
        submitBtn.textContent = selected === 0
            ? verb
            : verb + ' ' + selected + ' ' + (selected === 1 ? itemSingular : itemPlural);

        panel.querySelectorAll('.ad-row').forEach(function(row) {
            const input = row.querySelector('.ad-check');
            row.classList.toggle('is-checked', !!(input && input.checked));
        });
    }

    function filterRows(panel) {
        const search = panel.querySelector('.ad-search-input');
        const noneEl = panel.querySelector('.ad-none');
        const q = ((search && search.value) || '').trim().toLowerCase();
        let shown = 0;

        panel.querySelectorAll('.ad-row').forEach(function(row) {
            const match = !q || (row.getAttribute('data-search') || '').indexOf(q) !== -1;
            row.classList.toggle('is-hidden', !match);
            if (match) {
                shown += 1;
            }
        });

        if (noneEl) {
            noneEl.style.display = shown === 0 ? 'block' : 'none';
        }
    }

    // Inputs in inactive panels stay disabled so they are never submitted.
    modeSelect.addEventListener('change', function() {
        const mode = modeSelect.value;
        placeholder.style.display = mode ? 'none' : 'block';

        panels.forEach(function(panel) {
            const isActive = panel.getAttribute('data-mode') === mode;
            panel.classList.toggle('is-active', isActive);
            checksIn(panel).forEach(function(input) {
                input.disabled = !isActive;
                if (!isActive) {
                    input.checked = false;
                }
            });
        });

        syncUi();
    });

    panels.forEach(function(panel) {
        panel.addEventListener('change', function(event) {
            if (event.target.classList.contains('ad-check')) {
                syncUi();
            }
        });

        const search = panel.querySelector('.ad-search-input');
        if (search) {
            search.addEventListener('input', function() { filterRows(panel); });
        }

        const selectAll = panel.querySelector('.ad-select-all');
        if (selectAll) {
            selectAll.addEventListener('click', function() {
                panel.querySelectorAll('.ad-row').forEach(function(row) {
                    if (row.classList.contains('is-hidden')) {
                        return;
                    }
                    const input = row.querySelector('.ad-check');
                    if (input) {
                        input.checked = true;
                    }
                });
                syncUi();
            });
        }

        const clear = panel.querySelector('.ad-clear');
        if (clear) {
            clear.addEventListener('click', function() {
                checksIn(panel).forEach(function(input) { input.checked = false; });
                syncUi();
            });
        }
    });

    syncUi();
})();
</script>
