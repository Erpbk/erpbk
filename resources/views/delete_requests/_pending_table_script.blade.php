@php
    $pendingDeletionIds = $pendingDeletionIds ?? [];
    if ($pendingDeletionIds === [] && isset($items) && $items) {
        $first = is_iterable($items) ? collect($items)->first() : null;
        if ($first instanceof \Illuminate\Database\Eloquent\Model) {
            $pendingDeletionIds = pending_deletion_ids_for(get_class($first));
        }
    }
@endphp
@if(!empty($pendingDeletionIds))
<script>
(function () {
    var pending = @json(array_values(array_map('intval', $pendingDeletionIds)));
    if (!pending.length) return;
    var set = {};
    pending.forEach(function (id) { set[String(id)] = true; });

    function extractId(tr) {
        if (tr.getAttribute('data-id')) return tr.getAttribute('data-id');
        if (tr.getAttribute('data-record-id')) return tr.getAttribute('data-record-id');
        var btn = tr.querySelector('[id^="actiondropdown_"]');
        if (btn) return btn.id.replace('actiondropdown_', '');
        var html = tr.innerHTML;
        var m = html.match(/actiondropdown_(\d+)/);
        if (m) return m[1];
        m = html.match(/data-delete-url="[^"]*?\/(\d+)(?:\?|")/);
        if (m) return m[1];
        m = html.match(/class="[^"]*invoice-checkbox[^"]*"[^>]*value="(\d+)"|value="(\d+)"[^>]*class="[^"]*invoice-checkbox/);
        if (m) return m[1] || m[2];
        m = html.match(/data-action="[^"]*?\/(\d+)(?:\?|")/);
        if (m) return m[1];
        m = html.match(/confirmDelete(?:Employee)?\(['"][^'"]*?\/(\d+)/);
        if (m) return m[1];
        m = html.match(/delete-assignment-(\d+)/);
        if (m) return m[1];
        m = html.match(/data-id=\"(\d+)\"/);
        if (m) return m[1];
        m = html.match(/\/(?:edit|show|destroy|delete|ledger)\/(\d+)/);
        if (m) return m[1];
        return null;
    }

    function lockRow(tr) {
        tr.classList.add('table-warning');
        var firstTd = tr.querySelector('td');
        if (firstTd && !firstTd.querySelector('.pending-deletion-badge')) {
            firstTd.insertAdjacentHTML(
                'beforeend',
                ' <span class="badge bg-warning text-dark pending-deletion-badge" title="Awaiting administrator approval"><i class="ti ti-lock me-1"></i>Pending Deletion</span>'
            );
        }
        tr.querySelectorAll('.dropdown, .btn-group').forEach(function (el) {
            var td = el.closest('td');
            if (!td || td.querySelector('.pending-deletion-badge')) {
                if (td && !td.querySelector('.pending-deletion-lock')) {
                    td.innerHTML = '<span class="text-muted small pending-deletion-lock"><i class="ti ti-lock me-1"></i>Locked</span>';
                }
                return;
            }
            td.innerHTML = '<span class="text-muted small pending-deletion-lock"><i class="ti ti-lock me-1"></i>Locked</span>';
        });
    }

    function run() {
        var tables = document.querySelectorAll('table#dataTableBuilder, table.table');
        tables.forEach(function (table) {
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var id = extractId(tr);
                if (id && set[String(id)]) {
                    lockRow(tr);
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', run);
    } else {
        run();
    }
})();
</script>
@endif
