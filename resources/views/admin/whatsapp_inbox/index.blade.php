@extends('layouts.app')

@section('title', 'WhatsApp Inbox (Default)')

@section('content')
<link rel="stylesheet" href="{{ asset('css/rider-activities-demo.css') }}">
<style>
  .wa-inbox { display: grid; grid-template-columns: 320px 1fr; gap: 0; min-height: 70vh; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
  .wa-inbox-side { border-right: 1px solid #e2e8f0; background: #f8fafc; overflow: auto; }
  .wa-inbox-row { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid #eef2f7; background: transparent; padding: 10px 12px; cursor: pointer; }
  .wa-inbox-row.on, .wa-inbox-row:hover { background: #ecfdf5; }
  .wa-inbox-composer { display: flex; gap: 8px; padding: 10px; border-top: 1px solid #e2e8f0; }
  .wa-conflict { margin: 8px 12px; padding: 10px; background: #fff7ed; border: 1px solid #fdba74; border-radius: 8px; }
  .wa-inbox .ra-wa-stack { align-items: stretch; }
  .wa-inbox .ra-wa-msg {
    align-self: flex-start;
    background: #fff;
    border-radius: 0 9px 9px 9px;
    border: 1px solid rgba(0, 0, 0, 0.06);
  }
  .wa-inbox .ra-wa-msg.out {
    align-self: flex-end;
    background: #d9fdd3;
    border-radius: 9px 0 9px 9px;
    border-color: transparent;
  }
</style>

<section class="content-header">
  <div class="container-fluid"><h1>WhatsApp Inbox — Default numbers</h1></div>
</section>

<div class="content px-3">
  <div class="wa-inbox ra-demo" id="waAdminInbox"
       data-list-url="{{ route('admin.whatsapp-inbox.index') }}"
       data-thread-url="{{ url('/admin/whatsapp-inbox/thread') }}"
       data-send-url="{{ route('admin.whatsapp-inbox.send') }}"
       data-conflict-url="{{ route('admin.whatsapp-inbox.conflict') }}"
       data-csrf="{{ csrf_token() }}">
    <div class="wa-inbox-side" id="waList">
      @forelse($conversations as $c)
        <button type="button" class="wa-inbox-row" data-peer="{{ $c['peer_phone'] }}">
          <b>{{ $c['label'] }}</b>
          <small class="d-block text-muted">{{ $c['last_body'] }}</small>
        </button>
      @empty
        <div class="p-3 text-muted">No default-number conversations.</div>
      @endforelse
    </div>
    <div>
      <div class="p-2 border-bottom"><strong id="waPeerLabel">Select a conversation</strong></div>
      <div id="waConflict" class="wa-conflict" hidden></div>
      <div class="ra-wa-phone" style="border:0;border-radius:0;max-width:none;">
        <div class="ra-wa-chat" id="waChat"><div class="ra-wa-stack" id="waStack"></div></div>
      </div>
      <div class="wa-inbox-composer" id="waComposer" hidden>
        <input type="text" id="waBody" class="form-control form-control-sm" placeholder="Type a message…">
        <button type="button" class="btn btn-sm btn-success" id="waSend" disabled>Send</button>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const root = document.getElementById('waAdminInbox');
  const csrf = root.dataset.csrf;
  let peer = null, lastId = 0, openWindow = false, connectionId = null, poll = null;
  const headers = () => ({'X-CSRF-TOKEN': csrf, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'});
  function esc(s){return String(s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}
  async function openThread(p, afterId){
    peer = p;
    document.querySelectorAll('.wa-inbox-row').forEach(el => el.classList.toggle('on', el.dataset.peer === p));
    const url = root.dataset.threadUrl + '/' + encodeURIComponent(p) + (afterId ? ('?after_id='+afterId) : '');
    const data = await (await fetch(url,{headers:headers()})).json();
    document.getElementById('waPeerLabel').textContent = data.peer_label || data.peer_phone;
    openWindow = !!data.open_window;
    connectionId = data.connection_id;
    document.getElementById('waComposer').hidden = false;
    document.getElementById('waSend').disabled = !openWindow;
    const stack = document.getElementById('waStack');
    if (!afterId) { stack.innerHTML=''; lastId=0; }
    (data.items||[]).forEach(it => {
      if (it.id && stack.querySelector('[data-mid="'+it.id+'"]')) return;
      if (it.id && it.id > lastId) lastId = it.id;
      const d = document.createElement('div');
      d.className = 'ra-wa-msg' + (it.direction==='out'?' out':' in');
      d.dataset.mid = it.id||'';
      const bits = [];
      if (it.occurred_at) {
        const dt = new Date(it.occurred_at);
        if (!Number.isNaN(dt.getTime())) {
          const now = new Date();
          let time;
          if (dt.toDateString() === now.toDateString()) {
            time = dt.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
          } else if (dt.getFullYear() === now.getFullYear()) {
            time = dt.toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
          } else {
            time = dt.toLocaleString(undefined, { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
          }
          bits.push(time);
        }
      }
      if (it.status) bits.push(it.status);
      const meta = bits.join(' · ');
      d.innerHTML = '<div class="txt">'+esc(it.body||'')+'</div>' +
        (meta ? ('<div class="meta"><span>'+esc(meta)+'</span></div>') : '');
      stack.appendChild(d);
    });
    const box = document.getElementById('waConflict');
    if (data.conflict && data.conflict.candidates) {
      box.hidden = false;
      box.innerHTML = '<strong>Choose participant:</strong><br>' + data.conflict.candidates.map(c =>
        '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-mid="'+data.conflict.message_id+'" data-type="'+esc(c.type)+'" data-id="'+c.id+'">'+esc(c.label)+'</button>'
      ).join('');
      box.querySelectorAll('button').forEach(b => b.onclick = async () => {
        await fetch(root.dataset.conflictUrl,{method:'POST',headers:headers(),body:JSON.stringify({message_id:+b.dataset.mid,participant_type:b.dataset.type,participant_id:+b.dataset.id})});
        openThread(peer);
      });
    } else { box.hidden = true; box.innerHTML=''; }
    if (poll) clearInterval(poll);
    poll = setInterval(()=>{ if (document.visibilityState==='hidden'||!peer) return; openThread(peer, lastId||undefined); }, 7000);
  }
  document.getElementById('waList').addEventListener('click', e => {
    const b = e.target.closest('.wa-inbox-row'); if (b) openThread(b.dataset.peer);
  });
  document.getElementById('waSend').onclick = async () => {
    const body = document.getElementById('waBody').value.trim();
    if (!body || !peer) return;
    const data = await (await fetch(root.dataset.sendUrl,{method:'POST',headers:headers(),body:JSON.stringify({peer_phone:peer,body,whatsapp_connection_id:connectionId})})).json();
    if (!data.ok) { alert(data.message||'Failed'); return; }
    document.getElementById('waBody').value='';
    openThread(peer);
  };
})();
</script>
@endsection
