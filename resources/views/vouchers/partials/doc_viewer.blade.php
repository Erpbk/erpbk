{{-- In-page attachment viewer (PDF/image). Safe to include on any page; @once prevents duplicates. --}}
@once('vc-doc-viewer')
<style id="vc-docv-critical">
  .vc-docv.open,.vc-docv.open[hidden]{display:flex!important;visibility:visible!important;opacity:1!important;position:fixed!important;inset:0!important;z-index:200000!important;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,.55);box-sizing:border-box}
  .vc-docv-panel{width:min(1120px,96vw);height:min(920px,94vh);max-height:94vh;background:#fff;border-radius:14px;box-shadow:0 24px 64px rgba(15,23,42,.28);display:flex;flex-direction:column;overflow:hidden;position:relative}
  .vc-docv-hd{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:12px 16px;border-bottom:1px solid #e2e8f0;background:#fff;flex:none}
  .vc-docv-hd-l{display:flex;align-items:center;gap:10px;min-width:0}
  .vc-docv-ico{width:36px;height:36px;border-radius:9px;background:#eff6ff;color:#2563eb;display:grid;place-items:center;flex:none}
  .vc-docv-titles{min-width:0}
  .vc-docv-name{font:700 14px/1.25 Inter,system-ui,sans-serif;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .vc-docv-meta{margin-top:2px;font:500 11.5px/1.35 Inter,system-ui,sans-serif;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .vc-docv-hd-r{display:flex;align-items:center;gap:8px;flex:none}
  .vc-docv-dl{display:inline-flex;align-items:center;gap:6px;border:1px solid #3b82f6;color:#2563eb;background:#fff;border-radius:8px;padding:7px 12px;font:600 12.5px Inter,system-ui,sans-serif;text-decoration:none}
  .vc-docv-x{width:34px;height:34px;border:0;border-radius:8px;background:transparent;color:#64748b;font-size:22px;line-height:1;cursor:pointer}
  .vc-docv-body{flex:1 1 auto;min-height:0;height:100%;background:#525659;position:relative;overflow:auto;-webkit-overflow-scrolling:touch;overscroll-behavior:contain}
  .vc-docv-body iframe,.vc-docv-body embed,.vc-docv-body object{position:absolute;inset:0;width:100%;height:100%;min-height:100%;border:0;display:block;background:#525659}
  .vc-docv-img-wrap{min-height:100%;display:block;padding:16px;box-sizing:border-box;background:#0f172a}
  .vc-docv-body img{display:block;width:auto;max-width:100%;height:auto;margin:0 auto;background:#fff}
  .vc-docv-fallback{color:#fff;text-align:center;padding:48px 20px;font:500 14px Inter,system-ui,sans-serif}
  .vc-docv-fallback a{color:#93c5fd}
  .vc-docv-note{position:absolute;left:50%;bottom:18px;transform:translateX(-50%);background:#1e3a8a;color:#fff;border-radius:999px;padding:9px 16px;font:600 12.5px Inter,system-ui,sans-serif;box-shadow:0 8px 24px rgba(15,23,42,.28);z-index:2;white-space:nowrap}
  body.vc-docv-open{overflow:hidden}
</style>
@if(!isset($omitDocViewerMarkup) || !$omitDocViewerMarkup)
<div class="vc-docv" id="vcDocViewer" hidden aria-hidden="true">
  <div class="vc-docv-panel" role="dialog" aria-modal="true" aria-labelledby="vcDocVTitle">
    <div class="vc-docv-hd">
      <div class="vc-docv-hd-l">
        <span class="vc-docv-ico" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
            <path d="M14 3v5h5"/>
            <path d="M9 13h6M9 17h4"/>
          </svg>
        </span>
        <div class="vc-docv-titles">
          <div class="vc-docv-name" id="vcDocVTitle">Document</div>
          <div class="vc-docv-meta" id="vcDocVMeta"></div>
        </div>
      </div>
      <div class="vc-docv-hd-r">
        <a href="#" class="vc-docv-dl" id="vcDocVDownload" download target="_blank" rel="noopener">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12M7 10l5 5 5-5M5 21h14"/></svg>
          Download
        </a>
        <button type="button" class="vc-docv-x" id="vcDocVClose" aria-label="Close">&times;</button>
      </div>
    </div>
    <div class="vc-docv-body" id="vcDocVBody"></div>
    <div class="vc-docv-note" id="vcDocVNote" hidden></div>
  </div>
</div>
@endif
<script>
(function () {
  if (window.__vcDocViewerBound) return;
  window.__vcDocViewerBound = true;

  var noteTimer = null;

  function extOf(name) {
    var m = String(name || '').toLowerCase().match(/\.([a-z0-9]+)(?:\?|#|$)/);
    return m ? m[1] : '';
  }

  function closeViewer() {
    var ov = document.getElementById('vcDocViewer');
    if (!ov) return;
    ov.classList.remove('open');
    ov.setAttribute('hidden', 'hidden');
    ov.setAttribute('aria-hidden', 'true');
    ov.style.display = 'none';
    var body = document.getElementById('vcDocVBody');
    if (body) body.innerHTML = '';
    var note = document.getElementById('vcDocVNote');
    if (note) {
      note.setAttribute('hidden', 'hidden');
      note.textContent = '';
    }
    if (noteTimer) {
      clearTimeout(noteTimer);
      noteTimer = null;
    }
    document.body.classList.remove('vc-docv-open');
  }

  function openViewer(opts) {
    var ov = document.getElementById('vcDocViewer');
    if (!ov) return;
    if (ov.parentElement !== document.body) {
      document.body.appendChild(ov);
    }
    var url = String((opts && opts.url) || '');
    if (!url) return;
    var name = String((opts && opts.name) || 'Document');
    var meta = String((opts && opts.meta) || '');
    var context = String((opts && opts.context) || 'this voucher');
    var ext = extOf(name) || extOf(url);
    var isImg = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].indexOf(ext) !== -1;
    var isPdf = ext === 'pdf';
    var safeUrl = url.replace(/"/g, '&quot;');
    var safeName = name.replace(/"/g, '&quot;');

    var title = document.getElementById('vcDocVTitle');
    var metaEl = document.getElementById('vcDocVMeta');
    var dl = document.getElementById('vcDocVDownload');
    var body = document.getElementById('vcDocVBody');
    if (title) title.textContent = name;
    if (metaEl) metaEl.textContent = meta;
    if (dl) {
      dl.setAttribute('href', url);
      dl.setAttribute('download', name);
    }
    if (!body) return;
    body.innerHTML = '';
    if (isImg) {
      body.innerHTML = '<div class="vc-docv-img-wrap"><img src="' + safeUrl + '" alt="' + safeName + '"></div>';
    } else if (isPdf) {
      var src = url + (url.indexOf('#') === -1 ? '#toolbar=1&navpanes=1&scrollbar=1&view=FitH' : '');
      body.innerHTML = '<iframe title="Document preview" src="' + src.replace(/"/g, '&quot;') + '"></iframe>';
    } else {
      body.innerHTML = '<div class="vc-docv-fallback"><div>Preview is not available for this file type.</div><a href="' + safeUrl + '" target="_blank" rel="noopener">Open / Download</a></div>';
    }

    var note = document.getElementById('vcDocVNote');
    if (note) {
      note.removeAttribute('hidden');
      note.textContent = '1 document(s) attached to ' + context;
      if (noteTimer) clearTimeout(noteTimer);
      noteTimer = setTimeout(function () {
        note.setAttribute('hidden', 'hidden');
      }, 4200);
    }

    ov.removeAttribute('hidden');
    ov.classList.add('open');
    ov.setAttribute('aria-hidden', 'false');
    ov.style.cssText = 'position:fixed;inset:0;z-index:200000;display:flex;align-items:center;justify-content:center;padding:18px;background:rgba(15,23,42,0.55);box-sizing:border-box;';
    document.body.classList.add('vc-docv-open');
  }

  window.vcOpenDocViewer = openViewer;
  window.vcCloseDocViewer = closeViewer;

  document.addEventListener('click', function (e) {
    var closeBtn = e.target.closest && e.target.closest('#vcDocVClose');
    if (closeBtn) {
      e.preventDefault();
      closeViewer();
      return;
    }
    if (e.target && e.target.id === 'vcDocViewer') {
      closeViewer();
      return;
    }
    var a = e.target.closest && e.target.closest('.js-vc-doc-view, a.vc-fbtn');
    if (!a) return;
    var url = a.getAttribute('data-file-url') || a.getAttribute('href');
    if (!url || url === '#' || url.indexOf('javascript:') === 0) return;
    e.preventDefault();
    e.stopPropagation();
    openViewer({
      url: url,
      name: a.getAttribute('data-file-name') || a.getAttribute('title') || a.textContent.trim() || 'Document',
      meta: a.getAttribute('data-file-meta') || '',
      context: a.getAttribute('data-file-context') || 'this voucher'
    });
  }, true);

  document.addEventListener('keydown', function (e) {
    var ov = document.getElementById('vcDocViewer');
    if (e.key === 'Escape' && ov && ov.classList.contains('open')) {
      closeViewer();
    }
  });
})();
</script>
@endonce
