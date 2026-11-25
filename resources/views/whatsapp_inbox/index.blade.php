@extends('layouts.app')

@section('title', 'WhatsApp')

@section('content')
@php
  $companySlug = request()->route('company_slug') ?? session('company_slug');
  $waPrefsUrl = \Illuminate\Support\Facades\Route::has('whatsapp.ui_prefs.update')
    ? route('whatsapp.ui_prefs.update', absolute: false)
    : '';
  $waUiPrefs = auth()->user()
    ? \App\Support\WhatsApp\WhatsAppUiPreferences::forUser(auth()->user())
    : \App\Support\WhatsApp\WhatsAppUiPreferences::defaults();
  $waInitials = function ($label) {
    $label = trim((string) $label);
    if ($label === '') return '?';
    $parts = preg_split('/\s+/u', $label) ?: [];
    $ini = mb_substr($parts[0] ?? '', 0, 1);
    if (count($parts) > 1) $ini .= mb_substr($parts[count($parts) - 1], 0, 1);
    return mb_strtoupper($ini !== '' ? $ini : '?');
  };
@endphp
<link rel="stylesheet" href="{{ asset('css/whatsapp-ui.css') }}?v=20261125-wa-multi-select">
<style>
  /* Inbox page chrome: slim header, full-bleed body */
  .wa-inbox-page .content-header { padding-top: 8px; padding-bottom: 8px; }
  .wa-inbox-page .content-header h1 { font-size: 1.35rem; margin: 0; }
  .wa-inbox-page .content.wa-inbox-content { padding: 0; }
</style>

<section class="content-header wa-inbox-page">
  <div class="container-fluid">
    <div class="row">
      <div class="col-sm-6"><h1>{{ $menuLabels['whatsapp'] ?? 'WhatsApp' }}</h1></div>
    </div>
  </div>
</section>

<div class="content wa-inbox-content wa-inbox-page">
  <div class="wa-ui" id="waInbox"
       data-list-url="{{ route('whatsapp.inbox.index', absolute: false) }}"
       data-thread-url="/app/{{ $companySlug }}/whatsapp/inbox/thread"
       data-search-url="{{ route('whatsapp.inbox.participants', absolute: false) }}"
       data-send-url="{{ route('whatsapp.inbox.send', absolute: false) }}"
       data-media-download-url="{{ url('/app/'.$companySlug.'/whatsapp/inbox/messages') }}"
       data-transfer-url="{{ route('whatsapp.inbox.transfer', absolute: false) }}"
       data-transfer-bulk-url="{{ route('whatsapp.inbox.transfer_bulk', absolute: false) }}"
       data-delete-url="{{ route('whatsapp.inbox.destroy', absolute: false) }}"
       data-delete-bulk-url="{{ route('whatsapp.inbox.destroy_bulk', absolute: false) }}"
       data-conflict-url="{{ route('whatsapp.inbox.conflict', absolute: false) }}"
       data-csrf="{{ csrf_token() }}"
       data-wa-prefs-url="{{ $waPrefsUrl }}"
       data-wa-prefs='@json($waUiPrefs)'
       data-can-create="{{ $canCreate ? '1' : '0' }}"
       data-can-edit="{{ $canEdit ? '1' : '0' }}"
       data-can-delete="{{ $canDelete ? '1' : '0' }}">
    <div class="wa-inbox-side">
      <div class="wa-bar">
        <div class="wa-seg" id="waFilters">
          @foreach($filters as $f)
            <button type="button" data-type="{{ $f['key'] }}" class="{{ ($activeType ?? 'all') === $f['key'] ? 'on' : '' }}">
              {{ $f['label'] }} <span>{{ $f['count'] }}</span>
            </button>
          @endforeach
        </div>
        <button type="button" class="wa-icon-btn" id="waGear" title="Customize" aria-label="Customize">&#9881;</button>
      </div>
      <div class="wa-inbox-search">
        <input type="search" id="waSearch" placeholder="Search name or phone…" autocomplete="off">
        <div id="waSearchResults" class="mt-1"></div>
      </div>
      @if($canEdit || $canDelete)
      <div class="wa-bulk-bar" id="waBulkBar">
        <label class="wa-bulk-all" title="Select all">
          <input type="checkbox" id="waSelectAll" aria-label="Select all conversations">
        </label>
        <span class="wa-bulk-count" id="waBulkCount">0 selected</span>
        <div class="wa-bulk-acts">
          @if($canEdit)
          <button type="button" class="wa-bulk-btn" id="waBulkTransfer" disabled>Transfer</button>
          @endif
          @if($canDelete)
          <button type="button" class="wa-bulk-btn wa-bulk-danger" id="waBulkDelete" disabled>Delete</button>
          @endif
          <button type="button" class="wa-bulk-btn wa-bulk-clear" id="waBulkClear" disabled>Clear</button>
        </div>
      </div>
      @endif
      <div class="wa-inbox-list" id="waList">
        @forelse($conversations as $c)
          @php
            $tkey = $c['thread_key'] ?? ($c['peer_phone'].'|'.(($c['participant_type'] ?? null) ? ($c['participant_type'].'|'.$c['participant_id']) : 'unknown'));
            $unread = (int) ($c['unread_count'] ?? 0);
          @endphp
          <button type="button" class="wa-inbox-row{{ ($canEdit || $canDelete) ? ' has-check' : '' }}{{ $unread > 0 ? ' has-unread' : '' }}"
                  data-peer="{{ $c['peer_phone'] }}"
                  data-tkey="{{ $tkey }}"
                  data-ptype="{{ $c['participant_type'] ?? '' }}"
                  data-pid="{{ $c['participant_id'] ?? '' }}"
                  data-photo="{{ $c['photo'] ?? '' }}"
                  data-unread="{{ $unread }}"
                  data-last-at="{{ $c['last_at'] ?? '' }}">
            @if($canEdit || $canDelete)
            <span class="wa-row-check" data-stop-row="1">
              <input type="checkbox" class="wa-thread-check" aria-label="Select conversation" data-tkey="{{ $tkey }}">
            </span>
            @endif
            <div class="wa-av" data-ini="{{ $waInitials($c['label']) }}">
              <span class="ra-av-ini">{{ $waInitials($c['label']) }}</span>
              @if(!empty($c['photo']))
                <img src="{{ $c['photo'] }}" alt="" loading="lazy"
                     onload="this.parentElement.classList.add('has-img')"
                     onerror="this.remove()">
              @endif
            </div>
            <div class="wa-tx">
              <div class="wa-inbox-row-top">
                <b>{{ $c['label'] }}@if(!empty($c['trashed'])) <span class="text-danger">(trash)</span>@endif</b>
                @if($unread > 0)
                  <span class="wa-unread">{{ $unread > 99 ? '99+' : $unread }}</span>
                @endif
              </div>
              <small>{{ $c['last_body'] }}</small>
            </div>
          </button>
        @empty
          <div class="wa-empty">No conversations yet.</div>
        @endforelse
      </div>
    </div>

    <div class="wa-inbox-main">
      <div class="wa-ch">
        <div class="wa-av" id="waPeerAv" data-ini="WA"><span class="ra-av-ini">WA</span></div>
        <div class="who">
          <b id="waPeerLabel">Select a conversation</b>
          <small id="waPeerPhone"></small>
        </div>
        <div class="acts">
          @if($canEdit)
          <button type="button" class="wa-act-btn wa-act-transfer" id="waTransferBtn" disabled title="Transfer chat">Transfer</button>
          @endif
          @if($canDelete)
          <button type="button" class="wa-act-icon" id="waDeleteBtn" disabled title="Delete my chat" aria-label="Delete my chat">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
          @endif
        </div>
      </div>
      <div id="waConflict" class="wa-conflict" hidden></div>
      <div class="wa-msgs" id="waChat">
        <div class="wa-stack" id="waStack">
          <div class="wa-hint">Choose a chat from the left.</div>
        </div>
      </div>
      <div class="wa-comp" id="waComposer" hidden>
        <select id="waFrom" class="no-select2" data-no-select2 aria-label="Send from" title="Send from"></select>
        <label class="wa-attach" title="Attach files" aria-label="Attach files">
          <input type="file" id="waFile" hidden multiple accept="image/jpeg,image/png,image/webp,audio/*,video/mp4,video/3gpp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.5 6.5v8.75a4.75 4.75 0 1 1-9.5 0V6.25a3.25 3.25 0 0 1 6.5 0v8.5a1.75 1.75 0 1 1-3.5 0V7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </label>
        <span class="wa-attach-chips" id="waFileChips" hidden></span>
        <textarea id="waBody" rows="1" placeholder="Type a message…"></textarea>
        <button type="button" class="wa-send" id="waSend" title="Send" aria-label="Send">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 20.5V14l9-2-9-2V3.5L22 12z"/></svg>
        </button>
      </div>
    </div>

    <div class="wa-rsz" id="waRsz" title="Drag to resize · double-click to reset"></div>

    <div class="wa-cust" id="waCust" hidden>
      <div class="top"><h2>Customize</h2><button type="button" class="x" id="waCustX" aria-label="Close customize">&times;</button></div>
      <div id="waCustRows"></div>
      <div class="btns">
        <button type="button" class="reset" id="waCustReset">Reset</button>
        <button type="button" class="done" id="waCustDone">Done</button>
      </div>
    </div>
  </div>
</div>

@if($canEdit)
<div class="modal fade" id="waTransferModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title" id="waTransferTitle">Transfer chat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <label class="form-label">To user</label>
        <select id="waTransferUser" class="form-select mb-2">
          @foreach($transferUsers as $u)
            <option value="{{ $u['id'] }}">{{ $u['name'] }}</option>
          @endforeach
        </select>
        <label class="form-label">History</label>
        <select id="waTransferMode" class="form-select mb-2">
          <option value="from_now">From now on</option>
          <option value="full">Full history</option>
        </select>
        <label class="form-label">Reason</label>
        <textarea id="waTransferReason" class="form-control" rows="3" required></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn wa-transfer-confirm" id="waTransferConfirm">Transfer</button>
      </div>
    </div>
  </div>
</div>
@endif

<script>
(function () {
  const root = document.getElementById('waInbox');
  if (!root) return;
  const csrf = root.dataset.csrf;
  let activeType = @json($activeType ?: 'all');
  let peer = null;
  let participantType = null;
  let participantId = null;
  let threadKey = null;
  let pollTimer = null;
  let lastId = 0;
  const canSelectThreads = root.dataset.canEdit === '1' || root.dataset.canDelete === '1';
  const selectedThreadKeys = new Set();
  let transferTargets = null;
  let openByConn = {};
  let pollInFlight = false;
  let listSig = '';
  let filtersSig = '';
  let searchQuery = '';
  let searchParticipantId = null;
  /** @type {Map<string, {id:string,body:string,threadKey:string,connectionId:number,status:'pending'|'failed'}>} */
  const optimistic = new Map();
  let optimisticSeq = 0;
  /** @type {File[]} */
  let pendingFiles = [];
  const MAX_ATTACHMENTS = 10;

  function headers() {
    return { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  }

  function sameOriginUrl(pathOrUrl) {
    return new URL(pathOrUrl, window.location.origin);
  }

  function threadRefFromRow(el) {
    const nameEl = el.querySelector('.wa-inbox-row-top b');
    return {
      peer_phone: el.dataset.peer || '',
      participant_type: el.dataset.ptype || null,
      participant_id: el.dataset.pid ? +el.dataset.pid : null,
      thread_key: el.dataset.tkey || null,
      photo: el.dataset.photo || '',
      label: nameEl ? nameEl.textContent.replace(/\s*\(trash\)\s*$/i, '').trim() : ''
    };
  }

  function makeThreadKey(peerPhone, pType, pId) {
    if (pType && pId) return peerPhone + '|' + pType + '|' + pId;
    return peerPhone + '|unknown';
  }

  function participantPayload() {
    const payload = {};
    if (participantType && participantId) {
      payload.participant_type = participantType;
      payload.participant_id = participantId;
    }
    return payload;
  }

  function conversationSig(conversations) {
    return (conversations || []).map(c => {
      const tkey = c.thread_key || makeThreadKey(c.peer_phone, c.participant_type, c.participant_id);
      return tkey + ':' + (c.last_at || '') + ':' + (c.unread_count || 0);
    }).join('|');
  }

  function filterSig(filters) {
    return (filters || []).map(f => f.key + ':' + f.count).join('|');
  }

  function renderFilters(filters) {
    const box = document.getElementById('waFilters');
    if (!box) return;
    const next = filterSig(filters);
    if (next === filtersSig && box.children.length) return;
    filtersSig = next;
    let prevActive = activeType || 'all';
    if (prevActive !== 'all' && !(filters || []).some(f => f.key === prevActive)) {
      prevActive = 'all';
      activeType = 'all';
    }
    box.innerHTML = '';
    (filters || []).forEach(f => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.dataset.type = f.key;
      if (prevActive === f.key) btn.classList.add('on');
      btn.innerHTML = escapeHtml(f.label) + ' <span>' + escapeHtml(String(f.count)) + '</span>';
      box.appendChild(btn);
    });
  }

  function avatarMarkup(label, photo) {
    const ini = initialsOf(label);
    let html = '<div class="wa-av" data-ini="' + escapeHtml(ini) + '"><span class="ra-av-ini">' + escapeHtml(ini) + '</span>';
    if (photo) {
      html += '<img src="' + escapeHtml(photo) + '" alt="" loading="lazy"'
        + ' onload="this.parentElement.classList.add(\'has-img\')"'
        + ' onerror="this.remove()">';
    }
    return html + '</div>';
  }

  function setPeerAvatar(label, photo) {
    const avEl = document.getElementById('waPeerAv');
    if (!avEl) return;
    const ini = initialsOf(label);
    avEl.setAttribute('data-ini', ini);
    avEl.classList.remove('has-img');
    avEl.innerHTML = '<span class="ra-av-ini">' + escapeHtml(ini) + '</span>';
    if (photo) {
      const img = document.createElement('img');
      img.src = photo;
      img.alt = '';
      img.loading = 'lazy';
      img.onload = () => avEl.classList.add('has-img');
      img.onerror = () => img.remove();
      avEl.appendChild(img);
    }
  }

  function renderConversationRow(c) {
    const btn = document.createElement('button');
    btn.type = 'button';
    const tkey = c.thread_key || makeThreadKey(c.peer_phone, c.participant_type, c.participant_id);
    const unread = Math.max(0, parseInt(c.unread_count || 0, 10) || 0);
    // Open thread is treated as read in the UI immediately.
    const showUnread = (threadKey === tkey) ? 0 : unread;
    const selected = selectedThreadKeys.has(tkey);
    btn.className = 'wa-inbox-row'
      + (canSelectThreads ? ' has-check' : '')
      + (threadKey === tkey ? ' on' : '')
      + (selected ? ' is-selected' : '')
      + (showUnread > 0 ? ' has-unread' : '');
    btn.dataset.peer = c.peer_phone;
    btn.dataset.tkey = tkey;
    btn.dataset.ptype = c.participant_type || '';
    btn.dataset.pid = c.participant_id || '';
    btn.dataset.photo = c.photo || '';
    btn.dataset.unread = String(showUnread);
    btn.dataset.lastAt = c.last_at || '';
    const unreadHtml = showUnread > 0
      ? ('<span class="wa-unread">' + (showUnread > 99 ? '99+' : showUnread) + '</span>')
      : '';
    const checkHtml = canSelectThreads
      ? ('<span class="wa-row-check" data-stop-row="1"><input type="checkbox" class="wa-thread-check" aria-label="Select conversation" data-tkey="'
        + escapeHtml(tkey) + '"' + (selected ? ' checked' : '') + '></span>')
      : '';
    btn.innerHTML = checkHtml + avatarMarkup(c.label, c.photo || '')
      + '<div class="wa-tx"><div class="wa-inbox-row-top"><b>' + escapeHtml(c.label)
      + (c.trashed ? ' <span class="text-danger">(trash)</span>' : '')
      + '</b>' + unreadHtml + '</div><small>' + escapeHtml(c.last_body || '') + '</small></div>';
    return btn;
  }

  function threadPayloadFromRow(el) {
    const pType = el.dataset.ptype || '';
    const pId = el.dataset.pid || '';
    const payload = { peer_phone: el.dataset.peer || '' };
    if (pType && pType !== 'unknown' && pId) {
      payload.participant_type = pType;
      payload.participant_id = +pId;
    }
    return payload;
  }

  function selectedThreadPayloads() {
    const list = document.getElementById('waList');
    if (!list) return [];
    const out = [];
    list.querySelectorAll('.wa-inbox-row[data-tkey]').forEach(el => {
      if (!selectedThreadKeys.has(el.dataset.tkey)) return;
      out.push(threadPayloadFromRow(el));
    });
    return out;
  }

  function actionThreadPayloads() {
    const selected = selectedThreadPayloads();
    if (selected.length) return selected;
    if (!peer) return [];
    return [Object.assign({ peer_phone: peer }, participantPayload())];
  }

  function syncHeaderActions() {
    const hasTargets = selectedThreadKeys.size > 0 || !!peer;
    const transferBtn = document.getElementById('waTransferBtn');
    const deleteBtn = document.getElementById('waDeleteBtn');
    if (transferBtn) transferBtn.disabled = !hasTargets;
    if (deleteBtn) deleteBtn.disabled = !hasTargets;
  }

  function syncBulkBar() {
    const bar = document.getElementById('waBulkBar');
    if (!bar) return;
    const count = selectedThreadKeys.size;
    const countEl = document.getElementById('waBulkCount');
    if (countEl) countEl.textContent = count + ' selected';
    const transfer = document.getElementById('waBulkTransfer');
    const del = document.getElementById('waBulkDelete');
    const clear = document.getElementById('waBulkClear');
    if (transfer) transfer.disabled = count === 0;
    if (del) del.disabled = count === 0;
    if (clear) clear.disabled = count === 0;

    const selectAll = document.getElementById('waSelectAll');
    const rows = document.querySelectorAll('#waList .wa-inbox-row[data-tkey]');
    if (selectAll) {
      const total = rows.length;
      selectAll.checked = total > 0 && count > 0 && count === total;
      selectAll.indeterminate = count > 0 && count < total;
    }
    syncHeaderActions();
  }

  function setThreadSelected(tkey, on) {
    if (!tkey || !canSelectThreads) return;
    if (on) selectedThreadKeys.add(tkey);
    else selectedThreadKeys.delete(tkey);
    const row = document.querySelector('#waList .wa-inbox-row[data-tkey="' + CSS.escape(tkey) + '"]');
    if (row) {
      row.classList.toggle('is-selected', on);
      const check = row.querySelector('.wa-thread-check');
      if (check) check.checked = on;
    }
    syncBulkBar();
  }

  function clearThreadSelection() {
    selectedThreadKeys.clear();
    document.querySelectorAll('#waList .wa-inbox-row.is-selected').forEach(el => el.classList.remove('is-selected'));
    document.querySelectorAll('#waList .wa-thread-check').forEach(el => { el.checked = false; });
    syncBulkBar();
  }

  function openTransferForThreads(threads) {
    if (!threads.length) return;
    transferTargets = threads;
    const title = document.getElementById('waTransferTitle');
    if (title) title.textContent = threads.length > 1 ? ('Transfer ' + threads.length + ' chats') : 'Transfer chat';
    new bootstrap.Modal(document.getElementById('waTransferModal')).show();
  }

  async function deleteThreads(threads) {
    if (!threads.length) return;
    const msg = threads.length > 1
      ? ('Delete messages in ' + threads.length + ' chats (keeps latest stub in each)?')
      : 'Delete your messages in this chat (keeps latest stub)?';
    if (!confirm(msg)) return;

    const url = root.dataset.deleteBulkUrl || root.dataset.deleteUrl;
    const res = await fetch(url, {
      method: 'DELETE',
      headers: Object.assign({'Content-Type':'application/json'}, headers()),
      body: JSON.stringify({ threads })
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) {
      alert((data && data.message) || 'Delete failed.');
      return;
    }

    const openGone = peer && threads.some(t => {
      const tkey = makeThreadKey(t.peer_phone, t.participant_type || null, t.participant_id || null);
      return tkey === threadKey;
    });
    clearThreadSelection();
    listSig = '';
    await loadList(activeType);
    if (openGone) {
      openThread({ peer_phone: peer, participant_type: participantType, participant_id: participantId, thread_key: threadKey });
    }
  }

  function captureListAnchor() {
    const list = document.getElementById('waList');
    if (!list) return { mode: 'top' };
    const nearTop = list.scrollTop <= 8;
    if (nearTop) return { mode: 'top' };
    const listRect = list.getBoundingClientRect();
    const rows = list.querySelectorAll('.wa-inbox-row[data-tkey]');
    for (let i = 0; i < rows.length; i++) {
      const el = rows[i];
      const r = el.getBoundingClientRect();
      if (r.bottom <= listRect.top + 2) continue;
      return {
        mode: 'anchor',
        key: el.dataset.tkey,
        offset: r.top - listRect.top,
        scrollTop: list.scrollTop
      };
    }
    return { mode: 'pos', scrollTop: list.scrollTop };
  }

  function applyListScroll(anchor) {
    const list = document.getElementById('waList');
    if (!list) return;
    if (!anchor || anchor.mode === 'top') {
      list.scrollTop = 0;
      return;
    }
    if (anchor.mode === 'anchor' && anchor.key) {
      const el = list.querySelector('.wa-inbox-row[data-tkey="' + CSS.escape(anchor.key) + '"]');
      if (el) {
        const listRect = list.getBoundingClientRect();
        const r = el.getBoundingClientRect();
        list.scrollTop += (r.top - listRect.top) - (anchor.offset || 0);
        return;
      }
    }
    if (anchor.scrollTop != null) list.scrollTop = anchor.scrollTop;
  }

  async function loadList(type, q, participantIdFilter, options) {
    const opts = options || {};
    const url = sameOriginUrl(root.dataset.listUrl);
    if (type && type !== 'all') url.searchParams.set('type', type);
    if (q) url.searchParams.set('q', q);
    if (participantIdFilter) url.searchParams.set('participant_id', participantIdFilter);
    // Polls skip the distinct filter-count scan unless explicitly requested.
    if (opts.includeFilters === false) url.searchParams.set('include_filters', '0');
    const res = await fetch(url, { headers: headers(), credentials: 'same-origin' });
    if (!res.ok) return;
    const data = await res.json();
    if (data.filters) renderFilters(data.filters);

    const conversations = data.conversations || [];
    const nextSig = conversationSig(conversations);
    if (opts.skipIfUnchanged && nextSig === listSig) return;
    listSig = nextSig;

    const list = document.getElementById('waList');
    // Poll: keep place. Filter/search change: jump to top.
    const scrollMode = opts.scrollMode || 'top';
    const anchor = scrollMode === 'preserve' ? captureListAnchor() : { mode: 'top' };
    list.innerHTML = '';
    conversations.forEach(c => list.appendChild(renderConversationRow(c)));
    if (!conversations.length) {
      list.innerHTML = '<div class="wa-empty">No conversations.</div>';
    }
    // Drop selections for threads that disappeared after transfer/delete/filter.
    const visible = new Set(conversations.map(c => c.thread_key || makeThreadKey(c.peer_phone, c.participant_type, c.participant_id)));
    Array.from(selectedThreadKeys).forEach(key => {
      if (!visible.has(key)) selectedThreadKeys.delete(key);
    });
    syncBulkBar();
    applyListScroll(anchor);
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  }

  function initialsOf(label) {
    const parts = String(label || '').trim().split(/\s+/).filter(Boolean);
    if (!parts.length) return '?';
    let ini = Array.from(parts[0])[0] || '';
    if (parts.length > 1) ini += Array.from(parts[parts.length - 1])[0] || '';
    return (ini || '?').toUpperCase();
  }

  async function openThread(ref, afterId, options) {
    const opts = options || {};
    const peerPhone = typeof ref === 'string' ? ref : (ref.peer_phone || '');
    const pType = typeof ref === 'string' ? participantType : (ref.participant_type || null);
    const pId = typeof ref === 'string' ? participantId : (ref.participant_id ? +ref.participant_id : null);
    const tkey = typeof ref === 'string'
      ? makeThreadKey(peerPhone, pType, pId)
      : (ref.thread_key || makeThreadKey(peerPhone, pType, pId));

    peer = peerPhone;
    participantType = pType || null;
    participantId = pId || null;
    threadKey = tkey;

    // Only seed header from a list row that already has a name — never from poll
    // refs that only carry phone/ids (those would wipe name + photo).
    if (typeof ref !== 'string' && ref.label) {
      document.getElementById('waPeerLabel').textContent = ref.label;
      setPeerAvatar(ref.label, ref.photo || '');
    }

    let clearedUnread = false;
    document.querySelectorAll('.wa-inbox-row').forEach(el => {
      const isOn = el.dataset.tkey === threadKey;
      el.classList.toggle('on', isOn);
      if (isOn) {
        if ((parseInt(el.dataset.unread || '0', 10) || 0) > 0) clearedUnread = true;
        el.classList.remove('has-unread');
        el.dataset.unread = '0';
        const badge = el.querySelector('.wa-unread');
        if (badge) badge.remove();
      }
    });
    if (clearedUnread) {
      try { window.dispatchEvent(new CustomEvent('wa:unread-changed')); } catch (e) {}
    }
    syncHeaderActions();
    const url = sameOriginUrl(root.dataset.threadUrl.replace(/\/$/, '') + '/' + encodeURIComponent(peerPhone));
    if (afterId) url.searchParams.set('after_id', String(afterId));
    if (participantType && participantId) {
      url.searchParams.set('participant_type', participantType);
      url.searchParams.set('participant_id', String(participantId));
    }
    let data;
    try {
      const res = await fetch(url, { headers: headers(), credentials: 'same-origin' });
      if (!res.ok) return;
      data = await res.json();
    } catch (e) {
      return;
    }
    // Ignore stale responses if the user switched chats mid-flight.
    if (threadKey !== tkey) return;
    openByConn = data.open_window_by_connection || {};
    // Incremental polls keep the existing header; full opens refresh peer meta.
    if (!afterId) {
      document.getElementById('waPeerLabel').textContent = data.peer_label || peerPhone;
      const phoneEl = document.getElementById('waPeerPhone');
      if (phoneEl) phoneEl.textContent = peerPhone;
      setPeerAvatar(data.peer_label || peerPhone, data.photo || '');
      renderConflict(data.conflict);
    } else if (data.conflict) {
      renderConflict(data.conflict);
    }
    // Poll/background refresh: keep place if user scrolled up. User open/send: stick to end.
    const scrollMode = opts.scrollMode || 'end';
    const anchor = scrollMode === 'preserve' ? captureChatAnchor() : { mode: 'end' };
    if (!afterId) {
      lastId = 0;
      renderItems(data.items || [], true, anchor);
    } else {
      appendItems(data.items || [], anchor);
    }
    (data.items || []).forEach(it => { if (it.id && it.id > lastId) lastId = it.id; });
    setupComposer(data);
    if (opts.restartPoll !== false) startPoll();
  }

  function renderConflict(conflict) {
    const box = document.getElementById('waConflict');
    if (!conflict || !conflict.candidates) { box.hidden = true; box.innerHTML = ''; return; }
    box.hidden = false;
    box.innerHTML = '<div class="mb-2"><strong>Multiple recipients matched this number. Choose one:</strong></div>' +
      (conflict.candidates || []).map(c =>
        '<button type="button" class="btn btn-sm btn-outline-primary me-1 mb-1" data-mid="' + conflict.message_id + '" data-type="' + escapeHtml(c.type) + '" data-id="' + c.id + '">' +
        escapeHtml(c.label) + (c.trashed ? ' (trash)' : '') + '</button>'
      ).join('');
    box.querySelectorAll('button').forEach(btn => btn.addEventListener('click', async () => {
      await fetch(root.dataset.conflictUrl, {
        method: 'POST', headers: Object.assign({'Content-Type':'application/json'}, headers()),
        body: JSON.stringify({ message_id: +btn.dataset.mid, participant_type: btn.dataset.type, participant_id: +btn.dataset.id })
      });
      openThread({ peer_phone: peer, participant_type: participantType, participant_id: participantId, thread_key: threadKey });
    }));
  }

  function scrollChatToEnd() {
    const chat = document.getElementById('waChat');
    chat.scrollTop = chat.scrollHeight;
  }

  function isChatNearBottom(thresholdPx) {
    const chat = document.getElementById('waChat');
    if (!chat) return true;
    const gap = chat.scrollHeight - chat.scrollTop - chat.clientHeight;
    return gap <= (thresholdPx == null ? 80 : thresholdPx);
  }

  /** Snapshot scroll so a full re-render can restore place (or stick to end if already there). */
  function captureChatAnchor() {
    const chat = document.getElementById('waChat');
    if (!chat || isChatNearBottom()) return { mode: 'end' };
    const chatRect = chat.getBoundingClientRect();
    const nodes = chat.querySelectorAll('[data-mid], [data-optimistic]');
    for (let i = 0; i < nodes.length; i++) {
      const el = nodes[i];
      const r = el.getBoundingClientRect();
      if (r.bottom <= chatRect.top + 2) continue;
      const key = el.dataset.optimistic
        ? ('o:' + el.dataset.optimistic)
        : ('m:' + el.dataset.mid);
      return { mode: 'anchor', key, offset: r.top - chatRect.top, scrollTop: chat.scrollTop };
    }
    return { mode: 'pos', scrollTop: chat.scrollTop };
  }

  function applyChatScroll(anchor) {
    const chat = document.getElementById('waChat');
    if (!chat) return;
    if (!anchor || anchor.mode === 'end') {
      chat.scrollTop = chat.scrollHeight;
      return;
    }
    if (anchor.mode === 'anchor' && anchor.key) {
      let el = null;
      if (anchor.key.indexOf('o:') === 0) {
        el = chat.querySelector('[data-optimistic="' + anchor.key.slice(2) + '"]');
      } else if (anchor.key.indexOf('m:') === 0) {
        el = chat.querySelector('[data-mid="' + anchor.key.slice(2) + '"]');
      }
      if (el) {
        const chatRect = chat.getBoundingClientRect();
        const r = el.getBoundingClientRect();
        chat.scrollTop += (r.top - chatRect.top) - (anchor.offset || 0);
        return;
      }
    }
    if (anchor.scrollTop != null) chat.scrollTop = anchor.scrollTop;
  }

  function optimisticMatchesServer(entry, it) {
    if (!it || it.direction !== 'out' || !it.id || it.send_state) return false;
    if (Number(it.id) <= Number(entry.lastIdAtSend || 0)) return false;

    const serverBody = String(it.body || '').trim();
    const caption = String(entry.caption || '').trim();
    const filename = entry.file && entry.file.name ? String(entry.file.name).trim() : '';
    const mediaType = entry.mediaType || (entry.file ? attachKindFromFile(entry.file) : '');
    const serverType = String(it.message_type || '');

    // Text: exact body match (legacy optimistic text sends).
    if (!entry.file && !mediaType) {
      return serverBody !== '' && serverBody === String(entry.body || '').trim();
    }

    // Media: server body is caption (or filename fallback), not "caption · filename".
    const isMediaType = ['image', 'audio', 'video', 'document'].includes(serverType)
      || ['image', 'audio', 'video', 'document'].includes(mediaType);
    if (!isMediaType) return false;
    if (mediaType && serverType && mediaType !== serverType) return false;

    if (caption && serverBody === caption) return true;
    if (!caption && filename && (serverBody === filename || serverBody === '' || serverBody.toLowerCase() === mediaType)) return true;
    if (filename && it.media && String(it.media.filename || '') === filename) return true;
    if (serverBody && serverBody === String(entry.body || '').trim()) return true;

    return false;
  }

  function reconcileOptimistic(items) {
    // Match each sent/pending bubble to at most one *new* server outbound after send.
    // Skip queued (not yet POSTed) so later files cannot steal an earlier match.
    const available = [];
    (items || []).forEach(it => {
      if (it && it.direction === 'out' && it.id && !it.send_state) {
        available.push(it);
      }
    });
    const entries = [];
    optimistic.forEach((entry) => {
      if (entry.threadKey === threadKey && entry.status !== 'queued') entries.push(entry);
    });
    entries.sort((a, b) => a.seq - b.seq);
    entries.forEach((entry) => {
      const idx = available.findIndex(it => optimisticMatchesServer(entry, it));
      if (idx === -1) return;
      available.splice(idx, 1);
      optimistic.delete(entry.id);
      const el = document.querySelector('[data-optimistic="' + entry.id + '"]');
      if (el) el.remove();
    });
  }

  function optimisticItemPayload(entry) {
    const status = entry.status || 'queued';
    return {
      type: 'message',
      direction: 'out',
      body: entry.body,
      message_type: entry.mediaType || undefined,
      status: status === 'failed' ? 'failed' : 'sending',
      occurred_at: entry.occurredAt || new Date().toISOString(),
      optimistic_id: entry.id,
      send_state: status
    };
  }

  function restoreOptimisticBubbles() {
    const stack = document.getElementById('waStack');
    if (!stack || !threadKey) return;
    const entries = [];
    optimistic.forEach((entry) => {
      if (entry.threadKey === threadKey) entries.push(entry);
    });
    entries.sort((a, b) => a.seq - b.seq);
    entries.forEach((entry) => {
      if (stack.querySelector('[data-optimistic="' + entry.id + '"]')) return;
      stack.appendChild(itemEl(optimisticItemPayload(entry)));
    });
  }

  function renderItems(items, replace, anchor) {
    const stack = document.getElementById('waStack');
    if (replace) stack.innerHTML = '';
    items.forEach(it => stack.appendChild(itemEl(it)));
    reconcileOptimistic(items);
    restoreOptimisticBubbles();
    applyChatScroll(anchor || { mode: 'end' });
  }
  function appendItems(items, anchor) {
    const stack = document.getElementById('waStack');
    let added = 0;
    items.forEach(it => {
      if (it.type === 'message' && stack.querySelector('[data-mid="' + it.id + '"]')) return;
      stack.appendChild(itemEl(it));
      added++;
    });
    reconcileOptimistic(items);
    if (anchor) {
      applyChatScroll(anchor);
    } else if (added > 0 && isChatNearBottom()) {
      scrollChatToEnd();
    }
  }
  function formatLocalTime(iso) {
    if (!iso) return '';
    const dt = new Date(iso);
    if (Number.isNaN(dt.getTime())) return '';
    const now = new Date();
    const sameDay = dt.toDateString() === now.toDateString();
    const sameYear = dt.getFullYear() === now.getFullYear();
    if (sameDay) {
      return dt.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
    }
    if (sameYear) {
      return dt.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    }
    return dt.toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
  }

  function mediaCaption(it) {
    const mtype = (it.message_type || '').toLowerCase();
    const body = String(it.body || '').trim();
    if (!body) return '';
    // Ignore type placeholders ("Image", "Audio", …) — only real captions.
    if (mtype && body.toLowerCase() === mtype) return '';
    if (['image', 'audio', 'video', 'document', 'sticker', 'media'].includes(body.toLowerCase())) return '';
    return body;
  }

  function mediaBodyHtml(it) {
    const media = it.media || null;
    const mtype = it.message_type || '';
    const caption = mediaCaption(it);
    if (!media) {
      return '<div class="txt">' + escapeHtml(it.body || '') + '</div>';
    }
    if (media.ready && media.url) {
      if (mtype === 'image') {
        return '<div class="wa-media wa-media-image"><a href="' + escapeHtml(media.url) + '" target="_blank" rel="noopener"><img src="' + escapeHtml(media.url) + '" alt=""></a>'
          + (caption ? ('<div class="txt">' + escapeHtml(caption) + '</div>') : '') + '</div>';
      }
      if (mtype === 'audio') {
        return '<div class="wa-media wa-media-audio"><audio controls preload="metadata" controlsList="nodownload" src="' + escapeHtml(media.url) + '"></audio>'
          + (caption ? ('<div class="txt">' + escapeHtml(caption) + '</div>') : '') + '</div>';
      }
      if (mtype === 'video') {
        return '<div class="wa-media wa-media-video"><video controls preload="metadata" src="' + escapeHtml(media.url) + '"></video>'
          + (caption ? ('<div class="txt">' + escapeHtml(caption) + '</div>') : '') + '</div>';
      }
      if (mtype === 'document') {
        const name = media.filename || caption || 'Document';
        return '<div class="wa-media wa-media-doc"><a class="wa-doc-link" href="' + escapeHtml(media.url) + '" target="_blank" rel="noopener">' + escapeHtml(name) + '</a>'
          + (caption && caption !== name ? ('<div class="txt">' + escapeHtml(caption) + '</div>') : '') + '</div>';
      }
    }
    if (media.downloadable) {
      const label = media.filename || caption || (mtype ? (mtype.charAt(0).toUpperCase() + mtype.slice(1)) : 'Media');
      return '<div class="wa-media wa-media-pending">'
        + '<div class="txt">' + escapeHtml(label) + '</div>'
        + '<button type="button" class="wa-media-dl" data-mid="' + escapeHtml(String(it.id || '')) + '" title="Download" aria-label="Download">'
        + '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        + '</button>'
        + '</div>';
    }
    return '<div class="txt">' + escapeHtml(it.body || '') + '</div>';
  }

  const mediaDlIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  function bindMediaDownload(rootEl) {
    rootEl.querySelectorAll('.wa-media-dl').forEach(btn => {
      if (btn.dataset.bound === '1') return;
      btn.dataset.bound = '1';
      btn.addEventListener('click', async (e) => {
        e.preventDefault();
        e.stopPropagation();
        const mid = btn.dataset.mid;
        if (!mid || !root.dataset.mediaDownloadUrl) return;
        btn.disabled = true;
        btn.classList.add('is-loading');
        try {
          const res = await fetch(root.dataset.mediaDownloadUrl.replace(/\/$/, '') + '/' + encodeURIComponent(mid) + '/media/download', {
            method: 'POST',
            headers: Object.assign({}, headers(), { 'Content-Type': 'application/json' })
          });
          const data = await res.json().catch(() => ({}));
          if (!res.ok || !data.ok) {
            alert((data && data.message) || 'Media no longer available from WhatsApp.');
            btn.disabled = false;
            btn.classList.remove('is-loading');
            btn.innerHTML = mediaDlIcon;
            return;
          }
          const bub = btn.closest('.wa-bub');
          if (bub && data.media) {
            const mtype = bub.dataset.mtype || '';
            const rawTxt = String((bub.querySelector('.txt') || {}).textContent || '').trim();
            const body = (rawTxt && rawTxt.toLowerCase() !== mtype.toLowerCase()
              && !['image', 'audio', 'video', 'document', 'sticker', 'media'].includes(rawTxt.toLowerCase()))
              ? rawTxt
              : '';
            const rebuilt = itemEl({
              type: 'message',
              id: +mid,
              direction: bub.classList.contains('out') ? 'out' : 'in',
              message_type: mtype,
              body: body,
              media: Object.assign({ ready: true, downloadable: false }, data.media),
              status: '',
              occurred_at: new Date().toISOString()
            });
            bub.replaceWith(rebuilt);
          } else {
            refreshOpenThreadSoon();
          }
        } catch (err) {
          alert('Download failed.');
          btn.disabled = false;
          btn.classList.remove('is-loading');
          btn.innerHTML = mediaDlIcon;
        }
      });
    });
  }

  function itemEl(it) {
    if (it.type === 'gap') {
      const d = document.createElement('div');
      d.className = 'wa-gap';
      d.textContent = 'chat exists with ' + it.user_name + ' from ' + it.from + ' to ' + it.to;
      return d;
    }
    if (it.is_system) {
      const d = document.createElement('div');
      d.className = 'wa-sys';
      d.textContent = it.body || '';
      return d;
    }
    const d = document.createElement('div');
    const sendState = it.send_state || null;
    d.className = 'wa-bub' + (it.direction === 'out' ? ' out' : ' in')
      + (sendState === 'queued' ? ' is-queued' : '')
      + (sendState === 'pending' ? ' is-pending' : '')
      + (sendState === 'failed' ? ' is-failed' : '');
    d.dataset.mid = it.id || '';
    if (it.message_type) d.dataset.mtype = it.message_type;
    if (it.optimistic_id) d.dataset.optimistic = it.optimistic_id;
    const bits = [];
    const time = formatLocalTime(it.occurred_at);
    if (time) bits.push(time);
    if (sendState === 'queued') {
      bits.push('<span class="wa-msg-queued" title="Queued">…</span>');
    } else if (sendState === 'pending') {
      bits.push('<span class="wa-msg-spin" title="Sending"></span>');
    } else if (sendState === 'failed') {
      bits.push('failed');
      bits.push('<button type="button" class="wa-msg-resend" title="Resend" aria-label="Resend">↻</button>');
    } else if (it.status) {
      bits.push(escapeHtml(it.status));
    }
    const meta = bits.join(' · ');
    d.innerHTML = replyToHtml(it) + mediaBodyHtml(it) +
      (meta ? ('<div class="meta"><span>' + meta + '</span></div>') : '');
    const resendBtn = d.querySelector('.wa-msg-resend');
    if (resendBtn && it.optimistic_id) {
      resendBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        resendOptimistic(it.optimistic_id);
      });
    }
    const replyBtn = d.querySelector('.wa-reply[data-jump]');
    if (replyBtn) {
      replyBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const jumpId = replyBtn.getAttribute('data-jump');
        if (!jumpId) return;
        const target = document.querySelector('#waStack .wa-bub[data-mid="' + jumpId + '"]');
        if (!target) return;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        target.classList.add('wa-flash');
        setTimeout(() => target.classList.remove('wa-flash'), 1200);
      });
    }
    bindMediaDownload(d);
    return d;
  }

  function replyToHtml(it) {
    const r = it.reply_to;
    if (!r) return '';
    const label = r.direction === 'out' ? 'You' : (r.direction === 'in' ? 'Contact' : 'Reply');
    const preview = String(r.body || r.message_type || 'Original message').trim();
    const jump = r.id ? (' data-jump="' + escapeHtml(String(r.id)) + '"') : '';
    return '<button type="button" class="wa-reply"' + jump + ' title="Go to message">'
      + '<span class="wa-reply-bar" aria-hidden="true"></span>'
      + '<span class="wa-reply-content">'
      + '<span class="wa-reply-who">' + escapeHtml(label) + '</span>'
      + '<span class="wa-reply-text">' + escapeHtml(preview) + '</span>'
      + '</span></button>';
  }

  function setOptimisticState(id, status) {
    const entry = optimistic.get(id);
    if (!entry) return;
    entry.status = status;
    optimistic.set(id, entry);
    const el = document.querySelector('[data-optimistic="' + id + '"]');
    if (!el) return;
    el.replaceWith(itemEl(optimisticItemPayload(entry)));
  }

  function refreshOpenThreadSoon() {
    if (!peer || !threadKey) return;
    const ref = {
      peer_phone: peer,
      participant_type: participantType,
      participant_id: participantId,
      thread_key: threadKey
    };
    // Job is async; nudge a couple of refreshes so the spinner clears without waiting for the 5s poll.
    // Keep at bottom after send (user initiated).
    setTimeout(() => openThread(ref, null, { restartPoll: false, scrollMode: 'end' }), 700);
    setTimeout(() => openThread(ref, null, { restartPoll: false, scrollMode: 'end' }), 2200);
  }

  async function postSend(body, connectionId, optimisticId, file) {
    try {
      let res;
      if (file) {
        const fd = new FormData();
        fd.append('peer_phone', peer);
        fd.append('whatsapp_connection_id', String(+connectionId));
        if (body) fd.append('body', body);
        fd.append('file', file);
        const part = participantPayload();
        if (part.participant_type) fd.append('participant_type', part.participant_type);
        if (part.participant_id) fd.append('participant_id', String(part.participant_id));
        res = await fetch(root.dataset.sendUrl, {
          method: 'POST',
          headers: headers(),
          body: fd
        });
      } else {
        res = await fetch(root.dataset.sendUrl, {
          method: 'POST', headers: Object.assign({'Content-Type':'application/json'}, headers()),
          body: JSON.stringify(Object.assign({
            peer_phone: peer,
            whatsapp_connection_id: +connectionId,
            body
          }, participantPayload()))
        });
      }
      const data = await res.json().catch(() => ({}));
      if (!res.ok || !data.ok) {
        setOptimisticState(optimisticId, 'failed');
        return;
      }
      // Stay pending until poll/thread refresh sees the real outbound row.
      setOptimisticState(optimisticId, 'pending');
      listSig = '';
      refreshOpenThreadSoon();
    } catch (e) {
      setOptimisticState(optimisticId, 'failed');
    }
  }

  function enqueueOptimistic(body, connectionId, file, initialStatus) {
    const id = 'tmp-' + (++optimisticSeq);
    const mediaType = file ? attachKindFromFile(file) : '';
    const label = file
      ? (body || (file.name || (mediaType ? (mediaType.charAt(0).toUpperCase() + mediaType.slice(1)) : 'Attachment')))
      : body;
    const status = initialStatus || 'queued';
    const occurredAt = new Date().toISOString();
    optimistic.set(id, {
      id,
      seq: optimisticSeq,
      body: label,
      caption: body || '',
      file: file || null,
      mediaType: mediaType || null,
      threadKey: threadKey,
      connectionId: +connectionId,
      status,
      occurredAt,
      lastIdAtSend: lastId || 0
    });
    const stack = document.getElementById('waStack');
    stack.appendChild(itemEl(optimisticItemPayload(optimistic.get(id))));
    return id;
  }

  async function queueOutgoing(body, connectionId, file) {
    const id = enqueueOptimistic(body, connectionId, file, 'pending');
    scrollChatToEnd();
    await postSend(body, connectionId, id, file || null);
  }

  function resendOptimistic(id) {
    const entry = optimistic.get(id);
    if (!entry || !peer) return;
    setOptimisticState(id, 'pending');
    postSend(entry.caption || entry.body, entry.connectionId, id, entry.file || null);
  }

  function attachKindFromFile(file) {
    if (!file) return 'document';
    const mime = String(file.type || '').toLowerCase();
    const name = String(file.name || '').toLowerCase();
    if (mime.startsWith('image/') || /\.(jpe?g|png|webp|gif)$/i.test(name)) return 'image';
    if (mime.startsWith('audio/') || /\.(aac|m4a|mp3|amr|ogg|opus)$/i.test(name)) return 'audio';
    if (mime.startsWith('video/') || /\.(mp4|3gp|mov)$/i.test(name)) return 'video';
    return 'document';
  }

  function attachKindIcon(kind) {
    if (kind === 'image') {
      return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="8.5" cy="10" r="1.5" fill="currentColor"/><path d="m21 16-5.5-5.5L7 19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
    if (kind === 'audio') {
      return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18V6l10-2v12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="18" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="16" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
    }
    if (kind === 'video') {
      return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="6" width="13" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 10 5-2.5v9L16 14z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>';
    }
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v6h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>';
  }

  function renderAttachChips() {
    const wrap = document.getElementById('waFileChips');
    if (!wrap) return;
    wrap.innerHTML = '';
    wrap.hidden = pendingFiles.length === 0;
    pendingFiles.forEach((file, idx) => {
      const kind = attachKindFromFile(file);
      const chip = document.createElement('span');
      chip.className = 'wa-attach-chip';
      chip.dataset.kind = kind;
      chip.title = file.name || kind;
      chip.innerHTML = '<span class="wa-attach-chip-icon" aria-hidden="true">' + attachKindIcon(kind) + '</span>'
        + '<button type="button" class="wa-attach-chip-x" data-idx="' + idx + '" title="Remove attachment" aria-label="Remove attachment">&times;</button>';
      wrap.appendChild(chip);
    });
    wrap.querySelectorAll('.wa-attach-chip-x').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        const i = Number(btn.dataset.idx);
        if (Number.isNaN(i) || i < 0) return;
        pendingFiles.splice(i, 1);
        renderAttachChips();
      });
    });
  }

  function addPendingFiles(fileList) {
    const incoming = Array.from(fileList || []);
    for (const file of incoming) {
      if (pendingFiles.length >= MAX_ATTACHMENTS) break;
      pendingFiles.push(file);
    }
    renderAttachChips();
  }

  function clearAttach() {
    const fileEl = document.getElementById('waFile');
    if (fileEl) fileEl.value = '';
    pendingFiles = [];
    renderAttachChips();
  }

  async function sendComposerMessage() {
    const bodyEl = document.getElementById('waBody');
    const body = bodyEl.value.trim();
    const files = pendingFiles.slice();
    const connectionId = document.getElementById('waFrom').value;
    const sendBtn = document.getElementById('waSend');
    if ((!body && files.length === 0) || !peer || !connectionId || sendBtn.disabled) return;
    bodyEl.value = '';
    bodyEl.style.height = '';
    if (document.activeElement === bodyEl) {
      bodyEl.classList.add('is-expanded');
    } else {
      bodyEl.classList.remove('is-expanded');
    }
    clearAttach();
    sendBtn.disabled = true;

    // Show every optimistic bubble immediately; process sends one-by-one.
    const jobs = [];
    if (files.length > 0) {
      for (let i = 0; i < files.length; i++) {
        const caption = i === 0 ? body : '';
        const id = enqueueOptimistic(caption, connectionId, files[i], 'queued');
        jobs.push({ id, body: caption, file: files[i] });
      }
    } else {
      const id = enqueueOptimistic(body, connectionId, null, 'pending');
      jobs.push({ id, body, file: null });
    }
    scrollChatToEnd();

    try {
      for (let i = 0; i < jobs.length; i++) {
        const job = jobs[i];
        if (!optimistic.has(job.id)) continue;
        if (jobs.length > 1 || job.file) setOptimisticState(job.id, 'pending');
        await postSend(job.body, connectionId, job.id, job.file);
      }
    } finally {
      updateSendEnabled();
      bodyEl.focus();
    }
  }

  function setupComposer(data) {
    const composer = document.getElementById('waComposer');
    const from = document.getElementById('waFrom');
    from.innerHTML = '';
    (data.send_from || []).forEach(c => {
      const opt = document.createElement('option');
      opt.value = c.id; opt.textContent = c.label;
      from.appendChild(opt);
    });
    const can = root.dataset.canCreate === '1' && (data.send_from || []).length > 0;
    composer.hidden = !can;
    updateSendEnabled();
    from.onchange = updateSendEnabled;
  }
  function updateSendEnabled() {
    const fromId = document.getElementById('waFrom').value;
    const open = !!(openByConn[fromId] || openByConn[+fromId]);
    document.getElementById('waSend').disabled = !open || !peer;
  }

  let pollTick = 0;
  function startPoll() {
    stopPoll();
    // Sidebar + incremental thread deltas. Full filter counts every ~30s.
    pollTimer = setInterval(async () => {
      if (document.visibilityState === 'hidden' || pollInFlight) return;
      pollInFlight = true;
      pollTick += 1;
      try {
        await loadList(activeType, searchQuery || null, searchParticipantId, {
          skipIfUnchanged: true,
          scrollMode: 'preserve',
          includeFilters: (pollTick % 6) === 0
        });
        if (peer && threadKey) {
          await openThread({
            peer_phone: peer,
            participant_type: participantType,
            participant_id: participantId,
            thread_key: threadKey
          }, lastId > 0 ? lastId : null, { restartPoll: false, scrollMode: 'preserve' });
        }
      } finally {
        pollInFlight = false;
      }
    }, 5000);
  }
  function stopPoll() { if (pollTimer) clearInterval(pollTimer); pollTimer = null; }

  document.getElementById('waFilters').addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-type]');
    if (!btn) return;
    document.querySelectorAll('#waFilters button').forEach(b => b.classList.remove('on'));
    btn.classList.add('on');
    activeType = btn.dataset.type;
    searchQuery = '';
    searchParticipantId = null;
    const searchInput = document.getElementById('waSearch');
    if (searchInput) searchInput.value = '';
    document.getElementById('waSearchResults').innerHTML = '';
    listSig = '';
    loadList(activeType);
  });

  let searchTimer = null;
  document.getElementById('waSearch').addEventListener('input', (e) => {
    clearTimeout(searchTimer);
    const q = e.target.value.trim();
    searchTimer = setTimeout(async () => {
      const box = document.getElementById('waSearchResults');
      searchParticipantId = null;

      if (q === '') {
        box.innerHTML = '';
        searchQuery = '';
        listSig = '';
        loadList(activeType);
        return;
      }

      if (q.length < 2) {
        box.innerHTML = '';
        return;
      }

      searchQuery = q;
      listSig = '';
      // Filter the thread list for All and for a concrete type.
      loadList(activeType, searchQuery);

      // Suggestion chips only make sense for a concrete recipient type.
      if (!activeType || activeType === 'all' || activeType === 'unknown') {
        box.innerHTML = '';
        return;
      }

      const url = new URL(root.dataset.searchUrl, window.location.origin);
      url.searchParams.set('type', activeType);
      url.searchParams.set('q', q);
      const res = await fetch(url, { headers: headers() });
      const data = await res.json();
      box.innerHTML = (data.results || []).map(r =>
        '<button type="button" class="btn btn-sm btn-light d-block mb-1 w-100 text-start" data-pid="' + r.id + '">' + escapeHtml(r.label) + (r.trashed ? ' (trash)' : '') + '</button>'
      ).join('') || '<small class="text-muted">No matches</small>';
      box.querySelectorAll('button').forEach(b => b.addEventListener('click', () => {
        searchParticipantId = b.dataset.pid;
        searchQuery = '';
        listSig = '';
        loadList(activeType, null, searchParticipantId);
      }));
    }, 300);
  });

  document.getElementById('waList').addEventListener('click', (e) => {
    const stop = e.target.closest('[data-stop-row]');
    if (stop) {
      e.stopPropagation();
      // Native checkbox click is handled by the change listener.
      if (e.target.classList && e.target.classList.contains('wa-thread-check')) return;
      e.preventDefault();
      const check = stop.querySelector('.wa-thread-check');
      if (!check) return;
      check.checked = !check.checked;
      const row = stop.closest('.wa-inbox-row');
      setThreadSelected(check.dataset.tkey || (row && row.dataset.tkey), check.checked);
      return;
    }
    const btn = e.target.closest('.wa-inbox-row');
    if (btn) openThread(threadRefFromRow(btn));
  });
  document.getElementById('waList').addEventListener('change', (e) => {
    const check = e.target.closest('.wa-thread-check');
    if (!check) return;
    e.stopPropagation();
    const row = check.closest('.wa-inbox-row');
    setThreadSelected(check.dataset.tkey || (row && row.dataset.tkey), check.checked);
  });

  document.getElementById('waSend').addEventListener('click', () => sendComposerMessage());

  const waFile = document.getElementById('waFile');
  if (waFile) {
    waFile.addEventListener('change', () => {
      if (waFile.files && waFile.files.length) {
        addPendingFiles(waFile.files);
      }
      waFile.value = '';
    });
  }

  const waBody = document.getElementById('waBody');
  waBody.addEventListener('focus', () => waBody.classList.add('is-expanded'));
  waBody.addEventListener('blur', () => {
    if (!waBody.value.trim()) waBody.classList.remove('is-expanded');
  });
  waBody.addEventListener('keydown', (e) => {
    if (e.key !== 'Enter' || e.shiftKey || e.isComposing) return; // Shift+Enter = new line
    e.preventDefault();
    sendComposerMessage();
  });
  waBody.addEventListener('input', () => {
    waBody.style.height = 'auto';
    waBody.style.height = Math.min(waBody.scrollHeight, 140) + 'px';
  });

  const transferBtn = document.getElementById('waTransferBtn');
  if (transferBtn) {
    transferBtn.addEventListener('click', () => {
      const threads = actionThreadPayloads();
      if (!threads.length) return;
      openTransferForThreads(threads);
    });
    document.getElementById('waTransferConfirm').addEventListener('click', async () => {
      const reason = document.getElementById('waTransferReason').value.trim();
      if (!reason) return;
      const threads = (transferTargets && transferTargets.length)
        ? transferTargets
        : actionThreadPayloads();
      if (!threads.length) return;

      const confirmBtn = document.getElementById('waTransferConfirm');
      confirmBtn.disabled = true;
      try {
        const res = await fetch(root.dataset.transferBulkUrl || root.dataset.transferUrl, {
          method: 'POST',
          headers: Object.assign({'Content-Type':'application/json'}, headers()),
          body: JSON.stringify({
            threads,
            to_user_id: +document.getElementById('waTransferUser').value,
            reason,
            history_mode: document.getElementById('waTransferMode').value
          })
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.ok === false) {
          alert((data && data.message) || 'Transfer failed.');
          return;
        }
        if ((data.failed || 0) > 0) {
          alert('Transferred ' + (data.transferred || 0) + ', failed ' + data.failed + '.');
        }
        bootstrap.Modal.getInstance(document.getElementById('waTransferModal')).hide();
        document.getElementById('waTransferReason').value = '';
        transferTargets = null;
        clearThreadSelection();
        listSig = '';
        await loadList(activeType);
        if (peer) {
          openThread({ peer_phone: peer, participant_type: participantType, participant_id: participantId, thread_key: threadKey });
        }
      } finally {
        confirmBtn.disabled = false;
      }
    });
  }

  const deleteBtn = document.getElementById('waDeleteBtn');
  if (deleteBtn) {
    deleteBtn.addEventListener('click', async () => {
      const threads = actionThreadPayloads();
      if (!threads.length) return;
      await deleteThreads(threads);
    });
  }

  const bulkTransfer = document.getElementById('waBulkTransfer');
  if (bulkTransfer) {
    bulkTransfer.addEventListener('click', () => {
      const threads = selectedThreadPayloads();
      if (!threads.length) return;
      openTransferForThreads(threads);
    });
  }
  const bulkDelete = document.getElementById('waBulkDelete');
  if (bulkDelete) {
    bulkDelete.addEventListener('click', async () => {
      const threads = selectedThreadPayloads();
      if (!threads.length) return;
      await deleteThreads(threads);
    });
  }
  const bulkClear = document.getElementById('waBulkClear');
  if (bulkClear) {
    bulkClear.addEventListener('click', () => clearThreadSelection());
  }
  const selectAll = document.getElementById('waSelectAll');
  if (selectAll) {
    selectAll.addEventListener('change', () => {
      const on = selectAll.checked;
      document.querySelectorAll('#waList .wa-inbox-row[data-tkey]').forEach(el => {
        setThreadSelected(el.dataset.tkey, on);
      });
    });
  }
  syncBulkBar();

  // Seed signatures from server-rendered markup so the first poll can no-op.
  filtersSig = Array.from(document.querySelectorAll('#waFilters button')).map(b => {
    const cntEl = b.querySelector('span');
    const count = (cntEl && cntEl.textContent.trim()) || '0';
    return (b.dataset.type || '') + ':' + count;
  }).join('|');
  listSig = Array.from(document.querySelectorAll('#waList .wa-inbox-row')).map(el => {
    return (el.dataset.tkey || '') + ':' + (el.dataset.lastAt || '') + ':' + (el.dataset.unread || '0');
  }).join('|');

  /* ---- UI prefs (same keys as alerts drawer; per-user, saved in DB) ---- */
  const WA_DEF = { accent: '#00a884', bubble: '#d9fdd3', chat: '#efeae2', doodle: true, panel: '#ffffff', head: '#ffffff', lw: 34, ns: 17, av: 50, rh: 74, mf: 15, mw: 62 };
  const WA_FIELDS = [
    ['accent', 'Main color (buttons)', 'c'], ['bubble', 'Message bubble', 'c'], ['chat', 'Chat background', 'c'],
    ['doodle', 'Doodle pattern', 'b'], ['panel', 'Left panel background', 'c'], ['head', 'Chat header', 'c'],
    ['lw', 'List width', 's', 18, 65, '%'], ['ns', 'Name size', 's', 12, 24, 'px'], ['av', 'Photo size', 's', 30, 72, 'px'],
    ['rh', 'Row height', 's', 50, 110, 'px'], ['mf', 'Message text', 's', 11, 22, 'px'], ['mw', 'Message width', 's', 35, 95, '%']
  ];
  const WA_LS_KEY = 'waInboxUiPrefs';
  let waC = Object.assign({}, WA_DEF);
  try {
    const serverPrefs = root.getAttribute('data-wa-prefs');
    if (serverPrefs) {
      Object.assign(waC, JSON.parse(serverPrefs));
    } else {
      Object.assign(waC, JSON.parse(localStorage.getItem(WA_LS_KEY) || '{}'));
    }
  } catch (e) {}
  let waPrefsTimer = null;
  const waRsz = document.getElementById('waRsz');
  const waSide = root.querySelector('.wa-inbox-side');
  const waCust = document.getElementById('waCust');
  const waCustRows = document.getElementById('waCustRows');

  function waPlaceRsz() {
    if (!waRsz || !waSide) return;
    requestAnimationFrame(() => {
      const a = root.getBoundingClientRect();
      const l = waSide.getBoundingClientRect();
      waRsz.style.left = (l.right - a.left) + 'px';
    });
  }

  function waApplyPrefs(mode) {
    const s = root.style;
    s.setProperty('--accent', waC.accent);
    s.setProperty('--bubble', waC.bubble);
    s.setProperty('--chat', waC.chat);
    s.setProperty('--panel', waC.panel);
    s.setProperty('--head', waC.head);
    s.setProperty('--lw', waC.lw + '%');
    s.setProperty('--ns', waC.ns + 'px');
    s.setProperty('--av', waC.av + 'px');
    s.setProperty('--rh', waC.rh + 'px');
    s.setProperty('--mf', waC.mf + 'px');
    s.setProperty('--mw', waC.mw + '%');
    // Modal lives outside #waInbox — expose accent for Transfer confirm.
    document.documentElement.style.setProperty('--wa-accent', waC.accent);
    const confirmBtn = document.getElementById('waTransferConfirm');
    if (confirmBtn) {
      confirmBtn.style.backgroundColor = waC.accent;
      confirmBtn.style.borderColor = waC.accent;
    }
    root.classList.toggle('nodoodle', !waC.doodle);
    waPlaceRsz();
    if (mode === 'nosave') return;
    try { localStorage.setItem(WA_LS_KEY, JSON.stringify(waC)); } catch (e) {}
    waQueuePrefsSave();
  }

  function waQueuePrefsSave() {
    const url = root.getAttribute('data-wa-prefs-url');
    if (!url) return;
    clearTimeout(waPrefsTimer);
    waPrefsTimer = setTimeout(() => {
      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: Object.assign({ 'Content-Type': 'application/json' }, headers()),
        body: JSON.stringify({ prefs: waC })
      }).catch(() => {});
    }, 600);
  }

  function waBuildCust() {
    if (!waCustRows) return;
    waCustRows.innerHTML = WA_FIELDS.map(f => {
      const k = f[0], l = f[1], t = f[2];
      if (t === 'c') return '<div class="r"><span>' + l + '</span><input type="color" data-k="' + k + '" value="' + waC[k] + '"></div>';
      if (t === 'b') return '<div class="r"><span>' + l + '</span><input type="checkbox" data-k="' + k + '"' + (waC[k] ? ' checked' : '') + '></div>';
      return '<div class="r"><span>' + l + '</span><input type="range" data-k="' + k + '" min="' + f[3] + '" max="' + f[4] + '" value="' + waC[k] + '"><span class="v" id="v_' + k + '">' + waC[k] + f[5] + '</span></div>';
    }).join('');
  }

  if (waCustRows) {
    waCustRows.addEventListener('input', (e) => {
      const k = e.target.getAttribute('data-k');
      if (!k) return;
      const f = WA_FIELDS.filter(x => x[0] === k)[0];
      if (!f) return;
      waC[k] = e.target.type === 'checkbox' ? e.target.checked : (e.target.type === 'range' ? +e.target.value : e.target.value);
      if (f[2] === 's') {
        const v = document.getElementById('v_' + k);
        if (v) v.textContent = waC[k] + f[5];
      }
      waApplyPrefs();
    });
  }
  const waGear = document.getElementById('waGear');
  if (waGear && waCust) {
    waGear.addEventListener('click', () => {
      waBuildCust();
      waCust.hidden = !waCust.hidden;
    });
  }
  const waCustX = document.getElementById('waCustX');
  const waCustDone = document.getElementById('waCustDone');
  const waCustReset = document.getElementById('waCustReset');
  if (waCustX && waCust) waCustX.addEventListener('click', () => { waCust.hidden = true; });
  if (waCustDone && waCust) waCustDone.addEventListener('click', () => { waCust.hidden = true; });
  if (waCustReset) {
    waCustReset.addEventListener('click', () => {
      waC = Object.assign({}, WA_DEF);
      waApplyPrefs();
      waBuildCust();
    });
  }
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && waCust && !waCust.hidden) waCust.hidden = true;
  });

  // List | chat resizer
  function waSetListWidth(p) {
    p = Math.round(Math.max(18, Math.min(65, p)));
    waC.lw = p;
    waApplyPrefs();
    const v = document.getElementById('v_lw');
    if (v) v.textContent = p + '%';
    const i = document.querySelector('#waCustRows [data-k=lw]');
    if (i) i.value = p;
  }
  let waDrag = false;
  function waStopDrag() {
    if (!waDrag) return;
    waDrag = false;
    if (waRsz) waRsz.classList.remove('drag');
    document.body.classList.remove('wa-dragging');
  }
  if (waRsz) {
    waRsz.addEventListener('pointerdown', (e) => {
      waDrag = true;
      waRsz.classList.add('drag');
      document.body.classList.add('wa-dragging');
      waRsz.setPointerCapture(e.pointerId);
    });
    waRsz.addEventListener('pointermove', (e) => {
      if (!waDrag) return;
      const a = root.getBoundingClientRect();
      waSetListWidth((e.clientX - a.left) / a.width * 100);
    });
    waRsz.addEventListener('pointerup', waStopDrag);
    waRsz.addEventListener('pointercancel', waStopDrag);
    waRsz.addEventListener('dblclick', () => waSetListWidth(WA_DEF.lw));
  }
  window.addEventListener('resize', waPlaceRsz);
  if (window.ResizeObserver) new ResizeObserver(waPlaceRsz).observe(root);
  waApplyPrefs('nosave');

  // Keep sidebar/filters fresh even when no chat is open.
  startPoll();
})();
</script>
@endsection
