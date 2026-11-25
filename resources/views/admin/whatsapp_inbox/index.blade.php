@extends('layouts.app')

@section('title', 'WhatsApp Inbox (Default)')

@section('content')
<link rel="stylesheet" href="{{ asset('css/rider-activities-demo.css') }}">
<style>
  .wa-inbox { display: grid; grid-template-columns: 320px 1fr; gap: 0; min-height: 70vh; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
  .wa-inbox-side { border-right: 1px solid #e2e8f0; background: #f8fafc; overflow: auto; }
  .wa-inbox-row { display: block; width: 100%; text-align: left; border: 0; border-bottom: 1px solid #eef2f7; background: transparent; padding: 10px 12px; cursor: pointer; }
  .wa-inbox-row.on, .wa-inbox-row:hover { background: #ecfdf5; }
  .wa-inbox-composer { display: flex; gap: 8px; padding: 10px; border-top: 1px solid #e2e8f0; align-items: center; }
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
  .wa-inbox .wa-media img, .wa-inbox .wa-media video { max-width: 240px; max-height: 240px; border-radius: 8px; display: block; }
  .wa-inbox .wa-media audio { width: 220px; }
  .wa-inbox .wa-media-dl {
    margin-top: 6px;
    border: 1px solid #94a3b8;
    background: #fff;
    border-radius: 6px;
    padding: 4px 10px;
    font-size: 12px;
    cursor: pointer;
  }
  .wa-attach-btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; padding: 4px 8px; cursor: pointer; }
  .wa-attach-name { font-size: 12px; color: #64748b; max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>

<section class="content-header">
  <div class="container-fluid"><h1>WhatsApp Inbox — Default numbers</h1></div>
</section>

<div class="content px-3">
  <div class="wa-inbox ra-demo" id="waAdminInbox"
       data-list-url="{{ route('admin.whatsapp-inbox.index') }}"
       data-thread-url="{{ url('/admin/whatsapp-inbox/thread') }}"
       data-send-url="{{ route('admin.whatsapp-inbox.send') }}"
       data-media-download-url="{{ url('/admin/whatsapp-inbox/messages') }}"
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
        <label class="wa-attach-btn" title="Attach">
          <input type="file" id="waFile" hidden accept="image/jpeg,image/png,image/webp,audio/*,video/mp4,video/3gpp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt">
          Attach
        </label>
        <span class="wa-attach-name" id="waFileName" hidden></span>
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
  const headers = () => ({'X-CSRF-TOKEN': csrf, 'Accept':'application/json','X-Requested-With':'XMLHttpRequest'});
  function esc(s){return String(s||'').replace(/[&<>"']/g,m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[m]));}
  function mediaHtml(it){
    const media = it.media || null;
    const mtype = it.message_type || '';
    if (!media) return '<div class="txt">'+esc(it.body||'')+'</div>';
    if (media.ready && media.url) {
      if (mtype === 'image') return '<div class="wa-media"><a href="'+esc(media.url)+'" target="_blank" rel="noopener"><img src="'+esc(media.url)+'" alt=""></a>'+(it.body?('<div class="txt">'+esc(it.body)+'</div>'):'')+'</div>';
      if (mtype === 'audio') return '<div class="wa-media"><audio controls src="'+esc(media.url)+'"></audio></div>';
      if (mtype === 'video') return '<div class="wa-media"><video controls src="'+esc(media.url)+'"></video>'+(it.body?('<div class="txt">'+esc(it.body)+'</div>'):'')+'</div>';
      if (mtype === 'document') return '<div class="wa-media"><a href="'+esc(media.url)+'" target="_blank" rel="noopener">'+esc(media.filename||it.body||'Document')+'</a></div>';
    }
    if (media.downloadable) {
      return '<div class="wa-media"><div class="txt">'+esc(media.filename||it.body||mtype)+'</div><button type="button" class="wa-media-dl" data-mid="'+esc(it.id)+'" data-mtype="'+esc(mtype)+'">Download</button></div>';
    }
    return '<div class="txt">'+esc(it.body||'')+'</div>';
  }
  function bindDl(scope){
    scope.querySelectorAll('.wa-media-dl').forEach(btn => {
      if (btn.dataset.bound==='1') return;
      btn.dataset.bound='1';
      btn.onclick = async () => {
        btn.disabled = true; btn.textContent = 'Downloading…';
        try {
          const res = await fetch(root.dataset.mediaDownloadUrl.replace(/\/$/,'') + '/' + encodeURIComponent(btn.dataset.mid) + '/media/download', {
            method:'POST', headers:Object.assign({'Content-Type':'application/json'}, headers())
          });
          const data = await res.json().catch(()=>({}));
          if (!res.ok || !data.ok) { alert((data&&data.message)||'Media no longer available from WhatsApp.'); btn.disabled=false; btn.textContent='Download'; return; }
          openThread(peer);
        } catch(e) { alert('Download failed.'); btn.disabled=false; btn.textContent='Download'; }
      };
    });
  }
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
      if (it.message_type) d.dataset.mtype = it.message_type;
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
      d.innerHTML = mediaHtml(it) +
        (meta ? ('<div class="meta"><span>'+esc(meta)+'</span></div>') : '');
      stack.appendChild(d);
      bindDl(d);
    });
    const box = document.getElementById('waConflict');
    if (data.conflict && data.conflict.candidates) {
      box.hidden = false;
      box.innerHTML = '<strong>Choose participant:</strong><br>' + data.conflict.candidates.map(c =>
        '<button type="button" class="btn btn-sm btn-outline-primary me-1" data-mid="'+data.conflict.message_id+'" data-type="'+esc(c.type)+'" data-id="'+c.id+'">'+esc(c.label)+'</button>'
      ).join('');
      box.querySelectorAll('button').forEach(b => b.onclick = async () => {
        await fetch(root.dataset.conflictUrl,{method:'POST',headers:Object.assign({'Content-Type':'application/json'},headers()),body:JSON.stringify({message_id:+b.dataset.mid,participant_type:b.dataset.type,participant_id:+b.dataset.id})});
        openThread(peer);
      });
    } else { box.hidden = true; box.innerHTML=''; }
    if (poll) clearInterval(poll);
    poll = setInterval(()=>{ if (document.visibilityState==='hidden'||!peer) return; openThread(peer, lastId||undefined); }, 7000);
  }
  document.getElementById('waList').addEventListener('click', e => {
    const b = e.target.closest('.wa-inbox-row'); if (b) openThread(b.dataset.peer);
  });
  document.getElementById('waFile').addEventListener('change', () => {
    const f = document.getElementById('waFile').files[0];
    const n = document.getElementById('waFileName');
    if (f) { n.hidden = false; n.textContent = f.name; } else { n.hidden = true; n.textContent = ''; }
  });
  document.getElementById('waSend').onclick = async () => {
    const body = document.getElementById('waBody').value.trim();
    const fileEl = document.getElementById('waFile');
    const file = fileEl.files && fileEl.files[0] ? fileEl.files[0] : null;
    if ((!body && !file) || !peer) return;
    let res;
    if (file) {
      const fd = new FormData();
      fd.append('peer_phone', peer);
      if (body) fd.append('body', body);
      if (connectionId) fd.append('whatsapp_connection_id', String(connectionId));
      fd.append('file', file);
      res = await fetch(root.dataset.sendUrl,{method:'POST',headers:headers(),body:fd});
    } else {
      res = await fetch(root.dataset.sendUrl,{method:'POST',headers:Object.assign({'Content-Type':'application/json'},headers()),body:JSON.stringify({peer_phone:peer,body,whatsapp_connection_id:connectionId})});
    }
    const data = await res.json().catch(()=>({}));
    if (!data.ok) { alert(data.message||'Failed'); return; }
    document.getElementById('waBody').value='';
    fileEl.value='';
    document.getElementById('waFileName').hidden = true;
    document.getElementById('waFileName').textContent = '';
    openThread(peer);
  };
})();
</script>
@endsection
