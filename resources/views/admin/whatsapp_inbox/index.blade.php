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
  .wa-inbox .ra-wa-msg:has(.wa-media-audio) { min-width: 260px; }
  .wa-inbox .wa-media-audio { min-width: 240px; width: 100%; }
  .wa-inbox .wa-media audio { display: block; width: 100%; min-width: 220px; height: 40px; }
  .wa-inbox .wa-reply {
    display: flex; gap: 8px; align-items: stretch; width: 100%; margin: 0 0 6px;
    padding: 6px 8px; border: 0; border-radius: 6px; background: rgba(0,0,0,.06);
    text-align: left; cursor: pointer; color: inherit; font: inherit;
  }
  .wa-inbox .wa-reply-bar { width: 4px; border-radius: 4px; background: #53bdeb; flex: 0 0 auto; }
  .wa-inbox .ra-wa-msg.out .wa-reply-bar { background: #06cf9c; }
  .wa-inbox .wa-reply-content { display: grid; gap: 1px; min-width: 0; flex: 1; }
  .wa-inbox .wa-reply-who { font-size: 12px; font-weight: 600; color: #027eb5; }
  .wa-inbox .ra-wa-msg.out .wa-reply-who { color: #1fa855; }
  .wa-inbox .wa-reply-text { font-size: 12.5px; color: #667781; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
  .wa-inbox .ra-wa-msg.wa-flash { outline: 2px solid #53bdeb; outline-offset: 2px; }
  .wa-inbox .wa-media-dl {
    margin-top: 6px;
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #94a3b8;
    background: #fff;
    border-radius: 50%;
    padding: 0;
    cursor: pointer;
  }
  .wa-inbox .wa-media-dl svg { width: 16px; height: 16px; display: block; }
  .wa-inbox .wa-media-dl:disabled { opacity: 0.6; cursor: wait; }
  .wa-attach-btn { border: 1px solid #cbd5e1; background: #fff; border-radius: 6px; padding: 4px 8px; cursor: pointer; }
  .wa-attach-chip {
    display: inline-flex; align-items: center; gap: 2px; height: 32px; padding: 0 4px 0 8px;
    border-radius: 16px; background: #fff; border: 1px solid #cbd5e1; color: #54656f;
  }
  .wa-attach-chip[data-kind="image"] { color: #0284c7; }
  .wa-attach-chip[data-kind="audio"] { color: #7c3aed; }
  .wa-attach-chip[data-kind="video"] { color: #db2777; }
  .wa-attach-chip[data-kind="document"] { color: #b45309; }
  .wa-attach-chip-icon { width: 18px; height: 18px; display: inline-flex; }
  .wa-attach-chip-icon svg { width: 18px; height: 18px; display: block; }
  .wa-attach-chip-x { border: 0; background: transparent; color: #64748b; cursor: pointer; font-size: 16px; line-height: 1; padding: 0 4px; }
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
        <span class="wa-attach-chip" id="waFileChip" hidden title="">
          <span class="wa-attach-chip-icon" id="waFileChipIcon" aria-hidden="true"></span>
          <button type="button" class="wa-attach-chip-x" id="waFileClear" title="Remove attachment" aria-label="Remove attachment">&times;</button>
        </span>
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
  function replyToHtml(it){
    const r = it.reply_to;
    if (!r) return '';
    const label = r.direction === 'out' ? 'You' : (r.direction === 'in' ? 'Contact' : 'Reply');
    const preview = String(r.body || r.message_type || 'Original message').trim();
    const jump = r.id ? (' data-jump="'+esc(String(r.id))+'"') : '';
    return '<button type="button" class="wa-reply"'+jump+' title="Go to message">'
      + '<span class="wa-reply-bar" aria-hidden="true"></span>'
      + '<span class="wa-reply-content">'
      + '<span class="wa-reply-who">'+esc(label)+'</span>'
      + '<span class="wa-reply-text">'+esc(preview)+'</span>'
      + '</span></button>';
  }
  function mediaCaption(it){
    const mtype = (it.message_type||'').toLowerCase();
    const body = String(it.body||'').trim();
    if (!body) return '';
    if (mtype && body.toLowerCase() === mtype) return '';
    if (['image','audio','video','document','sticker','media'].includes(body.toLowerCase())) return '';
    return body;
  }
  function mediaHtml(it){
    const media = it.media || null;
    const mtype = it.message_type || '';
    const caption = mediaCaption(it);
    if (!media) return '<div class="txt">'+esc(it.body||'')+'</div>';
    if (media.ready && media.url) {
      if (mtype === 'image') return '<div class="wa-media wa-media-image"><a href="'+esc(media.url)+'" target="_blank" rel="noopener"><img src="'+esc(media.url)+'" alt=""></a>'+(caption?('<div class="txt">'+esc(caption)+'</div>'):'')+'</div>';
      if (mtype === 'audio') return '<div class="wa-media wa-media-audio"><audio controls preload="metadata" src="'+esc(media.url)+'"></audio>'+(caption?('<div class="txt">'+esc(caption)+'</div>'):'')+'</div>';
      if (mtype === 'video') return '<div class="wa-media wa-media-video"><video controls preload="metadata" src="'+esc(media.url)+'"></video>'+(caption?('<div class="txt">'+esc(caption)+'</div>'):'')+'</div>';
      if (mtype === 'document') return '<div class="wa-media"><a href="'+esc(media.url)+'" target="_blank" rel="noopener">'+esc(media.filename||caption||'Document')+'</a></div>';
    }
    if (media.downloadable) {
      return '<div class="wa-media"><div class="txt">'+esc(media.filename||caption||mtype)+'</div><button type="button" class="wa-media-dl" data-mid="'+esc(it.id)+'" data-mtype="'+esc(mtype)+'" title="Download" aria-label="Download"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button></div>';
    }
    return '<div class="txt">'+esc(it.body||'')+'</div>';
  }
  const mediaDlIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v10m0 0 4-4m-4 4-4-4M5 17v2a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-2" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  function bindDl(scope){
    scope.querySelectorAll('.wa-media-dl').forEach(btn => {
      if (btn.dataset.bound==='1') return;
      btn.dataset.bound='1';
      btn.onclick = async () => {
        btn.disabled = true;
        try {
          const res = await fetch(root.dataset.mediaDownloadUrl.replace(/\/$/,'') + '/' + encodeURIComponent(btn.dataset.mid) + '/media/download', {
            method:'POST', headers:Object.assign({'Content-Type':'application/json'}, headers())
          });
          const data = await res.json().catch(()=>({}));
          if (!res.ok || !data.ok) { alert((data&&data.message)||'Media no longer available from WhatsApp.'); btn.disabled=false; btn.innerHTML=mediaDlIcon; return; }
          openThread(peer);
        } catch(e) { alert('Download failed.'); btn.disabled=false; btn.innerHTML=mediaDlIcon; }
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
      d.innerHTML = replyToHtml(it) + mediaHtml(it) +
        (meta ? ('<div class="meta"><span>'+esc(meta)+'</span></div>') : '');
      stack.appendChild(d);
      const jump = d.querySelector('.wa-reply[data-jump]');
      if (jump) {
        jump.onclick = (e) => {
          e.preventDefault();
          const target = stack.querySelector('[data-mid="'+jump.getAttribute('data-jump')+'"]');
          if (!target) return;
          target.scrollIntoView({ behavior: 'smooth', block: 'center' });
          target.classList.add('wa-flash');
          setTimeout(() => target.classList.remove('wa-flash'), 1200);
        };
      }
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
  function attachKindFromFile(file){
    if (!file) return 'document';
    const mime = String(file.type||'').toLowerCase();
    const name = String(file.name||'').toLowerCase();
    if (mime.startsWith('image/') || /\.(jpe?g|png|webp|gif)$/i.test(name)) return 'image';
    if (mime.startsWith('audio/') || /\.(aac|m4a|mp3|amr|ogg|opus)$/i.test(name)) return 'audio';
    if (mime.startsWith('video/') || /\.(mp4|3gp|mov)$/i.test(name)) return 'video';
    return 'document';
  }
  function attachKindIcon(kind){
    if (kind === 'image') return '<svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="8.5" cy="10" r="1.5" fill="currentColor"/><path d="m21 16-5.5-5.5L7 19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    if (kind === 'audio') return '<svg viewBox="0 0 24 24"><path d="M9 18V6l10-2v12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="7" cy="18" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/><circle cx="17" cy="16" r="2.5" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
    if (kind === 'video') return '<svg viewBox="0 0 24 24"><rect x="3" y="6" width="13" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="m16 10 5-2.5v9L16 14z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>';
    return '<svg viewBox="0 0 24 24"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M14 3v6h6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>';
  }
  function setAttachPreview(file){
    const chip = document.getElementById('waFileChip');
    const icon = document.getElementById('waFileChipIcon');
    if (!chip || !icon) return;
    if (!file) { chip.hidden = true; chip.removeAttribute('data-kind'); chip.title=''; icon.innerHTML=''; return; }
    const kind = attachKindFromFile(file);
    chip.hidden = false; chip.dataset.kind = kind; chip.title = file.name || kind; icon.innerHTML = attachKindIcon(kind);
  }
  document.getElementById('waFile').addEventListener('change', () => {
    const f = document.getElementById('waFile').files[0];
    setAttachPreview(f || null);
  });
  document.getElementById('waFileClear').onclick = (e) => {
    e.preventDefault();
    document.getElementById('waFile').value = '';
    setAttachPreview(null);
  };
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
    setAttachPreview(null);
    openThread(peer);
  };
})();
</script>
@endsection
