@extends('layouts.app')

@section('title', 'WhatsApp')

@section('content')
@php
  $companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp
<link rel="stylesheet" href="{{ asset('css/rider-activities-demo.css') }}">
<style>
  .wa-inbox {
    display: grid;
    grid-template-columns: 320px 1fr;
    gap: 0;
    height: calc(100vh - 180px);
    min-height: 420px;
    max-height: calc(100vh - 140px);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
  }
  .wa-inbox-side {
    border-right: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    min-height: 0;
    min-width: 0;
    background: #f8fafc;
  }
  .wa-inbox-filters { display: flex; flex-wrap: wrap; gap: 6px; padding: 12px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
  .wa-inbox-filters button { border: 1px solid #cbd5e1; background: #fff; border-radius: 999px; padding: 4px 10px; font-size: 12px; }
  .wa-inbox-filters button.on { background: #16a34a; border-color: #16a34a; color: #fff; }
  .wa-inbox-search { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
  .wa-inbox-list { overflow-y: auto; overflow-x: hidden; flex: 1 1 auto; min-height: 0; -webkit-overflow-scrolling: touch; }
  .wa-inbox-row { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid #eef2f7; background: transparent; padding: 10px 12px; cursor: pointer; }
  .wa-inbox-row:hover, .wa-inbox-row.on { background: #ecfdf5; }
  .wa-inbox-row-top { display: flex; align-items: center; gap: 8px; }
  .wa-inbox-row b { display: block; font-size: 13px; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .wa-inbox-row small { color: #64748b; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .wa-unread {
    flex-shrink: 0;
    min-width: 18px;
    height: 18px;
    padding: 0 5px;
    border-radius: 999px;
    background: #16a34a;
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    line-height: 18px;
    text-align: center;
  }
  .wa-inbox-row.has-unread b { font-weight: 700; }
  .wa-inbox-main { display: flex; flex-direction: column; min-width: 0; min-height: 0; height: 100%; overflow: hidden; }
  .wa-toolbar { display: flex; gap: 8px; padding: 8px 12px; border-bottom: 1px solid #e2e8f0; align-items: center; flex-shrink: 0; }
  .wa-conflict { margin: 8px 12px; padding: 10px; background: #fff7ed; border: 1px solid #fdba74; border-radius: 8px; flex-shrink: 0; }
  .wa-inbox-main .ra-wa-phone {
    flex: 1 1 auto;
    min-height: 0;
    margin: 0;
    border: 0;
    border-radius: 0;
    max-width: none;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
  .wa-inbox-main .ra-wa-chat {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
  }
  .wa-inbox-composer {
    display: flex;
    gap: 8px;
    padding: 10px;
    border-top: 1px solid #e2e8f0;
    align-items: flex-end;
    flex-shrink: 0;
    background: #fff;
  }
  .wa-inbox-composer select { max-width: 220px; flex: 0 1 220px; }
  .wa-inbox-composer #waBody {
    flex: 1;
    min-height: 38px;
    max-height: 140px;
    resize: none;
    overflow-y: auto;
    line-height: 1.4;
    transition: min-height 0.15s ease;
  }
  .wa-inbox-composer #waBody.is-expanded { min-height: 72px; }
  .wa-inbox-composer #waSend {
    width: 38px;
    height: 38px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .wa-inbox-composer #waSend svg { width: 18px; height: 18px; fill: currentColor; }
  .wa-gap { text-align: center; color: #64748b; font-size: 12px; margin: 10px 12px; padding: 6px 10px; background: #f1f5f9; border-radius: 8px; }
  .wa-sys { text-align: center; color: #475569; font-size: 12px; margin: 8px; font-style: italic; }
  /* Incoming left/white, outgoing right/green (override rider-activities defaults) */
  .wa-inbox-main .ra-wa-stack { align-items: stretch; }
  .wa-inbox-main .ra-wa-msg {
    align-self: flex-start;
    background: #fff;
    border-radius: 0 9px 9px 9px;
    border: 1px solid rgba(0, 0, 0, 0.06);
  }
  .wa-inbox-main .ra-wa-msg.out {
    align-self: flex-end;
    background: #d9fdd3;
    border-radius: 9px 0 9px 9px;
    border-color: transparent;
  }
  .wa-inbox-main .ra-wa-msg.is-pending { opacity: 0.85; }
  .wa-inbox-main .ra-wa-msg.is-failed { background: #fee2e2; border: 1px solid #fca5a5; }
  .wa-msg-spin {
    display: inline-block;
    width: 10px;
    height: 10px;
    border: 2px solid #94a3b8;
    border-top-color: transparent;
    border-radius: 50%;
    animation: wa-spin 0.7s linear infinite;
    vertical-align: -1px;
  }
  .wa-msg-resend {
    border: 0;
    background: transparent;
    color: #b91c1c;
    padding: 0 2px;
    cursor: pointer;
    line-height: 1;
    font-size: 14px;
  }
  .wa-msg-resend:hover { color: #7f1d1d; }
  @keyframes wa-spin { to { transform: rotate(360deg); } }
  @media (max-width: 900px) {
    .wa-inbox { grid-template-columns: 1fr; height: auto; max-height: none; min-height: 70vh; }
    .wa-inbox-side { max-height: 40vh; }
    .wa-inbox-main { min-height: 50vh; }
  }
</style>

<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6"><h1>{{ $menuLabels['whatsapp'] ?? 'WhatsApp' }}</h1></div>
    </div>
  </div>
</section>

<div class="content px-3">
  <div class="wa-inbox ra-demo" id="waInbox"
       data-list-url="{{ route('whatsapp.inbox.index', absolute: false) }}"
       data-thread-url="/app/{{ $companySlug }}/whatsapp/inbox/thread"
       data-search-url="{{ route('whatsapp.inbox.participants', absolute: false) }}"
       data-send-url="{{ route('whatsapp.inbox.send', absolute: false) }}"
       data-transfer-url="{{ route('whatsapp.inbox.transfer', absolute: false) }}"
       data-delete-url="{{ route('whatsapp.inbox.destroy', absolute: false) }}"
       data-conflict-url="{{ route('whatsapp.inbox.conflict', absolute: false) }}"
       data-csrf="{{ csrf_token() }}"
       data-can-create="{{ $canCreate ? '1' : '0' }}"
       data-can-edit="{{ $canEdit ? '1' : '0' }}"
       data-can-delete="{{ $canDelete ? '1' : '0' }}">
    <div class="wa-inbox-side">
      <div class="wa-inbox-filters" id="waFilters">
        @foreach($filters as $f)
          <button type="button" data-type="{{ $f['key'] }}" class="{{ ($activeType ?? 'all') === $f['key'] ? 'on' : '' }}">
            {{ $f['label'] }} <span>({{ $f['count'] }})</span>
          </button>
        @endforeach
      </div>
      <div class="wa-inbox-search">
        <input type="search" id="waSearch" class="form-control form-control-sm" placeholder="Search name or phone…">
        <div id="waSearchResults" class="mt-1"></div>
      </div>
      <div class="wa-inbox-list" id="waList">
        @forelse($conversations as $c)
          @php
            $tkey = $c['thread_key'] ?? ($c['peer_phone'].'|'.(($c['participant_type'] ?? null) ? ($c['participant_type'].'|'.$c['participant_id']) : 'unknown'));
            $unread = (int) ($c['unread_count'] ?? 0);
          @endphp
          <button type="button" class="wa-inbox-row{{ $unread > 0 ? ' has-unread' : '' }}"
                  data-peer="{{ $c['peer_phone'] }}"
                  data-tkey="{{ $tkey }}"
                  data-ptype="{{ $c['participant_type'] ?? '' }}"
                  data-pid="{{ $c['participant_id'] ?? '' }}"
                  data-unread="{{ $unread }}"
                  data-last-at="{{ $c['last_at'] ?? '' }}">
            <div class="wa-inbox-row-top">
              <b>{{ $c['label'] }}@if(!empty($c['trashed'])) <span class="text-danger">(trash)</span>@endif</b>
              @if($unread > 0)
                <span class="wa-unread">{{ $unread > 99 ? '99+' : $unread }}</span>
              @endif
            </div>
            <small>{{ $c['last_body'] }}</small>
          </button>
        @empty
          <div class="p-3 text-muted">No conversations yet.</div>
        @endforelse
      </div>
    </div>
    <div class="wa-inbox-main">
      <div class="wa-toolbar">
        <strong id="waPeerLabel">Select a conversation</strong>
        <div class="ms-auto d-flex gap-2">
          @if($canEdit)
          <button type="button" class="btn btn-sm btn-outline-secondary" id="waTransferBtn" disabled>Transfer</button>
          @endif
          @if($canDelete)
          <button type="button" class="btn btn-sm btn-outline-danger" id="waDeleteBtn" disabled>Delete my chat</button>
          @endif
        </div>
      </div>
      <div id="waConflict" class="wa-conflict" hidden></div>
      <div class="ra-wa-phone">
        <div class="ra-wa-chat" id="waChat">
          <div class="ra-wa-stack" id="waStack">
            <div class="text-muted p-3">Choose a chat from the left.</div>
          </div>
        </div>
      </div>
      <div class="wa-inbox-composer" id="waComposer" hidden>
        <select id="waFrom" class="form-select form-select-sm"></select>
        <textarea id="waBody" class="form-control form-control-sm" rows="1" placeholder="Type a message…"></textarea>
        <button type="button" class="btn btn-sm btn-success" id="waSend" title="Send" aria-label="Send">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>
        </button>
      </div>
    </div>
  </div>
</div>

@if($canEdit)
<div class="modal fade" id="waTransferModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Transfer chat</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
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
        <button type="button" class="btn btn-primary" id="waTransferConfirm">Transfer</button>
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
  let openByConn = {};
  let pollInFlight = false;
  let listSig = '';
  let filtersSig = '';
  let searchQuery = '';
  let searchParticipantId = null;
  /** @type {Map<string, {id:string,body:string,threadKey:string,connectionId:number,status:'pending'|'failed'}>} */
  const optimistic = new Map();
  let optimisticSeq = 0;

  function headers() {
    return { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
  }

  function sameOriginUrl(pathOrUrl) {
    return new URL(pathOrUrl, window.location.origin);
  }

  function threadRefFromRow(el) {
    return {
      peer_phone: el.dataset.peer || '',
      participant_type: el.dataset.ptype || null,
      participant_id: el.dataset.pid ? +el.dataset.pid : null,
      thread_key: el.dataset.tkey || null
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
      btn.innerHTML = escapeHtml(f.label) + ' <span>(' + escapeHtml(String(f.count)) + ')</span>';
      box.appendChild(btn);
    });
  }

  function renderConversationRow(c) {
    const btn = document.createElement('button');
    btn.type = 'button';
    const tkey = c.thread_key || makeThreadKey(c.peer_phone, c.participant_type, c.participant_id);
    const unread = Math.max(0, parseInt(c.unread_count || 0, 10) || 0);
    // Open thread is treated as read in the UI immediately.
    const showUnread = (threadKey === tkey) ? 0 : unread;
    btn.className = 'wa-inbox-row'
      + (threadKey === tkey ? ' on' : '')
      + (showUnread > 0 ? ' has-unread' : '');
    btn.dataset.peer = c.peer_phone;
    btn.dataset.tkey = tkey;
    btn.dataset.ptype = c.participant_type || '';
    btn.dataset.pid = c.participant_id || '';
    btn.dataset.unread = String(showUnread);
    btn.dataset.lastAt = c.last_at || '';
    const unreadHtml = showUnread > 0
      ? ('<span class="wa-unread">' + (showUnread > 99 ? '99+' : showUnread) + '</span>')
      : '';
    btn.innerHTML = '<div class="wa-inbox-row-top"><b>' + escapeHtml(c.label)
      + (c.trashed ? ' <span class="text-danger">(trash)</span>' : '')
      + '</b>' + unreadHtml + '</div><small>' + escapeHtml(c.last_body || '') + '</small>';
    btn.addEventListener('click', () => openThread(c));
    return btn;
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
      list.innerHTML = '<div class="p-3 text-muted">No conversations.</div>';
    }
    applyListScroll(anchor);
  }

  function escapeHtml(s) {
    return String(s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
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

    document.querySelectorAll('.wa-inbox-row').forEach(el => {
      const isOn = el.dataset.tkey === threadKey;
      el.classList.toggle('on', isOn);
      if (isOn) {
        el.classList.remove('has-unread');
        el.dataset.unread = '0';
        const badge = el.querySelector('.wa-unread');
        if (badge) badge.remove();
      }
    });
    document.getElementById('waTransferBtn') && (document.getElementById('waTransferBtn').disabled = false);
    document.getElementById('waDeleteBtn') && (document.getElementById('waDeleteBtn').disabled = false);
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
    document.getElementById('waPeerLabel').textContent = data.peer_label || peerPhone;
    openByConn = data.open_window_by_connection || {};
    renderConflict(data.conflict);
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

  function reconcileOptimistic(items) {
    // Match each pending bubble to at most one *new* server outbound (id > lastId at send) with same body.
    const available = [];
    (items || []).forEach(it => {
      if (it && it.direction === 'out' && it.body && it.id && !it.send_state) {
        available.push(it);
      }
    });
    const entries = [];
    optimistic.forEach((entry) => {
      if (entry.threadKey === threadKey) entries.push(entry);
    });
    entries.sort((a, b) => a.seq - b.seq);
    entries.forEach((entry) => {
      const idx = available.findIndex(it =>
        String(it.body) === entry.body && Number(it.id) > Number(entry.lastIdAtSend || 0)
      );
      if (idx === -1) return;
      available.splice(idx, 1);
      optimistic.delete(entry.id);
      const el = document.querySelector('[data-optimistic="' + entry.id + '"]');
      if (el) el.remove();
    });
  }

  function restoreOptimisticBubbles() {
    const stack = document.getElementById('waStack');
    if (!stack || !threadKey) return;
    optimistic.forEach((entry) => {
      if (entry.threadKey !== threadKey) return;
      if (stack.querySelector('[data-optimistic="' + entry.id + '"]')) return;
      stack.appendChild(itemEl({
        type: 'message',
        direction: 'out',
        body: entry.body,
        status: entry.status === 'failed' ? 'failed' : 'sending',
        occurred_at: new Date().toISOString(),
        optimistic_id: entry.id,
        send_state: entry.status
      }));
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
    d.className = 'ra-wa-msg' + (it.direction === 'out' ? ' out' : ' in')
      + (sendState === 'pending' ? ' is-pending' : '')
      + (sendState === 'failed' ? ' is-failed' : '');
    d.dataset.mid = it.id || '';
    if (it.optimistic_id) d.dataset.optimistic = it.optimistic_id;
    const bits = [];
    const time = formatLocalTime(it.occurred_at);
    if (time) bits.push(time);
    if (sendState === 'pending') {
      bits.push('<span class="wa-msg-spin" title="Sending"></span>');
    } else if (sendState === 'failed') {
      bits.push('failed');
      bits.push('<button type="button" class="wa-msg-resend" title="Resend" aria-label="Resend">↻</button>');
    } else if (it.status) {
      bits.push(escapeHtml(it.status));
    }
    const meta = bits.join(' · ');
    d.innerHTML = '<div class="txt">' + escapeHtml(it.body || '') + '</div>' +
      (meta ? ('<div class="meta"><span>' + meta + '</span></div>') : '');
    const resendBtn = d.querySelector('.wa-msg-resend');
    if (resendBtn && it.optimistic_id) {
      resendBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        resendOptimistic(it.optimistic_id);
      });
    }
    return d;
  }

  function setOptimisticState(id, status) {
    const entry = optimistic.get(id);
    if (!entry) return;
    entry.status = status;
    optimistic.set(id, entry);
    const el = document.querySelector('[data-optimistic="' + id + '"]');
    if (!el) return;
    el.replaceWith(itemEl({
      type: 'message',
      direction: 'out',
      body: entry.body,
      status: status === 'failed' ? 'failed' : 'sending',
      occurred_at: new Date().toISOString(),
      optimistic_id: id,
      send_state: status
    }));
    scrollChatToEnd();
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

  async function postSend(body, connectionId, optimisticId) {
    try {
      const res = await fetch(root.dataset.sendUrl, {
        method: 'POST', headers: Object.assign({'Content-Type':'application/json'}, headers()),
        body: JSON.stringify(Object.assign({
          peer_phone: peer,
          whatsapp_connection_id: +connectionId,
          body
        }, participantPayload()))
      });
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

  function queueOutgoing(body, connectionId) {
    const id = 'tmp-' + (++optimisticSeq);
    optimistic.set(id, {
      id,
      seq: optimisticSeq,
      body,
      threadKey: threadKey,
      connectionId: +connectionId,
      status: 'pending',
      lastIdAtSend: lastId || 0
    });
    const stack = document.getElementById('waStack');
    stack.appendChild(itemEl({
      type: 'message',
      direction: 'out',
      body,
      status: 'sending',
      occurred_at: new Date().toISOString(),
      optimistic_id: id,
      send_state: 'pending'
    }));
    scrollChatToEnd();
    postSend(body, connectionId, id);
  }

  function resendOptimistic(id) {
    const entry = optimistic.get(id);
    if (!entry || !peer) return;
    setOptimisticState(id, 'pending');
    postSend(entry.body, entry.connectionId, id);
  }

  function sendComposerMessage() {
    const bodyEl = document.getElementById('waBody');
    const body = bodyEl.value.trim();
    const connectionId = document.getElementById('waFrom').value;
    const sendBtn = document.getElementById('waSend');
    if (!body || !peer || !connectionId || sendBtn.disabled) return;
    bodyEl.value = '';
    bodyEl.style.height = '';
    if (document.activeElement === bodyEl) {
      bodyEl.classList.add('is-expanded');
    } else {
      bodyEl.classList.remove('is-expanded');
    }
    queueOutgoing(body, connectionId);
    bodyEl.focus();
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

  function startPoll() {
    stopPoll();
    // Always refresh sidebar/filters; also refresh the open thread.
    // List uses batched queries; skip DOM rewrite when signature unchanged.
    pollTimer = setInterval(async () => {
      if (document.visibilityState === 'hidden' || pollInFlight) return;
      pollInFlight = true;
      try {
        await loadList(activeType, searchQuery || null, searchParticipantId, {
          skipIfUnchanged: true,
          scrollMode: 'preserve'
        });
        if (peer && threadKey) {
          await openThread({
            peer_phone: peer,
            participant_type: participantType,
            participant_id: participantId,
            thread_key: threadKey
          }, null, { restartPoll: false, scrollMode: 'preserve' });
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
    const btn = e.target.closest('.wa-inbox-row');
    if (btn) openThread(threadRefFromRow(btn));
  });

  document.getElementById('waSend').addEventListener('click', () => sendComposerMessage());

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
      if (!peer) return;
      new bootstrap.Modal(document.getElementById('waTransferModal')).show();
    });
    document.getElementById('waTransferConfirm').addEventListener('click', async () => {
      const reason = document.getElementById('waTransferReason').value.trim();
      if (!reason) return;
      await fetch(root.dataset.transferUrl, {
        method: 'POST', headers: Object.assign({'Content-Type':'application/json'}, headers()),
        body: JSON.stringify(Object.assign({
          peer_phone: peer,
          to_user_id: +document.getElementById('waTransferUser').value,
          reason,
          history_mode: document.getElementById('waTransferMode').value
        }, participantPayload()))
      });
      bootstrap.Modal.getInstance(document.getElementById('waTransferModal')).hide();
      openThread({ peer_phone: peer, participant_type: participantType, participant_id: participantId, thread_key: threadKey });
      listSig = '';
      loadList(activeType);
    });
  }

  const deleteBtn = document.getElementById('waDeleteBtn');
  if (deleteBtn) {
    deleteBtn.addEventListener('click', async () => {
      if (!peer || !confirm('Delete your messages in this chat (keeps latest stub)?')) return;
      await fetch(root.dataset.deleteUrl, {
        method: 'DELETE', headers: Object.assign({'Content-Type':'application/json'}, headers()),
        body: JSON.stringify(Object.assign({ peer_phone: peer }, participantPayload()))
      });
      openThread({ peer_phone: peer, participant_type: participantType, participant_id: participantId, thread_key: threadKey });
      listSig = '';
      loadList(activeType);
    });
  }

  // Seed signatures from server-rendered markup so the first poll can no-op.
  filtersSig = Array.from(document.querySelectorAll('#waFilters button')).map(b => {
    const count = (b.textContent.match(/\((\d+)\)/) || [])[1] || '0';
    return (b.dataset.type || '') + ':' + count;
  }).join('|');
  listSig = Array.from(document.querySelectorAll('#waList .wa-inbox-row')).map(el => {
    return (el.dataset.tkey || '') + ':' + (el.dataset.lastAt || '') + ':' + (el.dataset.unread || '0');
  }).join('|');

  // Keep sidebar/filters fresh even when no chat is open.
  startPoll();
})();
</script>
@endsection
