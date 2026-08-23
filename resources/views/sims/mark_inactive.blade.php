<style>
.mark-inactive-form .mark-inactive-hint {
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
.mark-inactive-form .mark-inactive-hint i {
    font-size: 18px;
    margin-top: 1px;
    color: #ea580c;
}
.mark-inactive-form .mark-inactive-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-bottom: 10px;
}
.mark-inactive-form .mark-inactive-search {
    position: relative;
    flex: 1 1 220px;
    min-width: 180px;
}
.mark-inactive-form .mark-inactive-search i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 16px;
    pointer-events: none;
}
.mark-inactive-form .mark-inactive-search input {
    padding-left: 36px;
    border-radius: 8px;
}
.mark-inactive-form .mark-inactive-count {
    background: #eef2ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    font-weight: 600;
    font-size: 12px;
    padding: 6px 10px;
    border-radius: 999px;
}
.mark-inactive-form .sim-pick-list {
    max-height: 320px;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #fff;
}
.mark-inactive-form .sim-pick-row {
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
.mark-inactive-form .sim-pick-row:last-child {
    border-bottom: none;
}
.mark-inactive-form .sim-pick-row:hover {
    background: #f8fafc;
}
.mark-inactive-form .sim-pick-row.is-checked {
    background: #eef2ff;
}
.mark-inactive-form .sim-pick-row.is-hidden {
    display: none;
}
.mark-inactive-form .sim-pick-row .form-check-input {
    margin: 0;
    flex-shrink: 0;
    cursor: pointer;
}
.mark-inactive-form .sim-pick-number {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
    letter-spacing: .2px;
}
.mark-inactive-form .sim-pick-company {
    margin-left: auto;
    background: #f1f5f9;
    color: #475569;
    font-size: 11px;
    font-weight: 600;
    padding: 3px 8px;
    border-radius: 999px;
    white-space: nowrap;
}
.mark-inactive-form .sim-pick-empty,
.mark-inactive-form .sim-pick-none {
    padding: 28px 16px;
    text-align: center;
    color: #64748b;
    font-size: 13px;
}
.mark-inactive-form .sim-pick-none {
    display: none;
}
</style>

{!! Form::open(['url' => route('sims.markInactive'), 'method' => 'post', 'id' => 'formajax']) !!}

<div class="card-body mark-inactive-form px-0 pb-0">
    <div class="mark-inactive-hint">
        <i class="ti ti-info-circle"></i>
        <div>Only SIMs currently <strong>in office</strong> can be marked inactive. Selected SIMs will no longer be available to assign until they are put back in use.</div>
    </div>

    @if($officeSims->isEmpty())
    <div class="sim-pick-list">
        <div class="sim-pick-empty">
            <i class="ti ti-sim-off d-block mb-2" style="font-size: 28px; color: #cbd5e1;"></i>
            No in-office SIMs available.
        </div>
    </div>
    @else
    <div class="mark-inactive-toolbar">
        <div class="mark-inactive-search">
            <i class="ti ti-search"></i>
            <input type="text" id="markInactiveSearch" class="form-control" placeholder="Search number or company" autocomplete="off">
        </div>
        <span class="mark-inactive-count" id="markInactiveCount">0 selected</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="markInactiveSelectAll">Select all</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="markInactiveClear">Clear</button>
    </div>

    <div class="sim-pick-list" id="markInactiveList">
        @foreach($officeSims as $sim)
        <label class="sim-pick-row" data-search="{{ strtolower(trim($sim->number . ' ' . ($sim->telecomCompany?->name ?? ''))) }}">
            <input class="form-check-input mark-inactive-check" type="checkbox" name="sim_ids[]" value="{{ $sim->id }}">
            <span class="sim-pick-number">{{ $sim->number }}</span>
            @if($sim->telecomCompany?->name)
            <span class="sim-pick-company">{{ $sim->telecomCompany->name }}</span>
            @endif
        </label>
        @endforeach
        <div class="sim-pick-none" id="markInactiveNone">No SIMs match your search.</div>
    </div>
    @endif
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    <button type="submit" class="btn btn-primary" id="markInactiveSubmit" @disabled($officeSims->isEmpty())>
        Mark Inactive
    </button>
</div>

{!! Form::close() !!}

<script>
(function() {
    const list = document.getElementById('markInactiveList');
    if (!list) {
        return;
    }

    const checks = Array.from(list.querySelectorAll('.mark-inactive-check'));
    const rows = Array.from(list.querySelectorAll('.sim-pick-row'));
    const search = document.getElementById('markInactiveSearch');
    const countEl = document.getElementById('markInactiveCount');
    const noneEl = document.getElementById('markInactiveNone');
    const submitBtn = document.getElementById('markInactiveSubmit');
    const selectAllBtn = document.getElementById('markInactiveSelectAll');
    const clearBtn = document.getElementById('markInactiveClear');

    function visibleRows() {
        return rows.filter(function(row) {
            return !row.classList.contains('is-hidden');
        });
    }

    function syncUi() {
        const selected = checks.filter(function(input) { return input.checked; }).length;
        countEl.textContent = selected === 1 ? '1 selected' : selected + ' selected';
        submitBtn.disabled = selected === 0;
        submitBtn.textContent = selected === 0
            ? 'Mark Inactive'
            : (selected === 1 ? 'Mark 1 SIM Inactive' : 'Mark ' + selected + ' SIMs Inactive');

        rows.forEach(function(row) {
            const input = row.querySelector('.mark-inactive-check');
            row.classList.toggle('is-checked', !!(input && input.checked));
        });
    }

    function filterRows() {
        const q = (search.value || '').trim().toLowerCase();
        let shown = 0;
        rows.forEach(function(row) {
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

    list.addEventListener('change', syncUi);
    search.addEventListener('input', filterRows);

    selectAllBtn.addEventListener('click', function() {
        visibleRows().forEach(function(row) {
            const input = row.querySelector('.mark-inactive-check');
            if (input) {
                input.checked = true;
            }
        });
        syncUi();
    });

    clearBtn.addEventListener('click', function() {
        checks.forEach(function(input) { input.checked = false; });
        syncUi();
    });

    syncUi();
})();
</script>
