@once
<style>
  .document-scan-field {
    position: relative;
  }

  .document-scan-field .document-scan-native-file {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
    opacity: 0;
  }

  .document-scan-field .document-scan-selected.is-set {
    color: #1e4b8e;
    font-weight: 600;
  }

  #documentScanModal .modal-dialog {
    max-width: min(1200px, 96vw);
    width: 96vw;
  }

  #documentScanModal .modal-body {
    max-height: calc(100vh - 10rem);
    overflow-y: auto;
  }

  .document-scan-video-wrap {
    position: relative;
    background: #0f172a;
    border-radius: 0.75rem;
    overflow: hidden;
    min-height: 420px;
  }

  #documentScanModal video {
    width: 100%;
    max-height: 62vh;
    display: block;
    object-fit: contain;
    background: #0f172a;
  }

  #documentScanModal .document-scan-overlay {
    pointer-events: none;
    position: absolute;
    inset: 10%;
    border: 2px dashed rgba(255, 255, 255, 0.55);
    border-radius: 0.5rem;
  }

  #documentScanModal .document-scan-status {
    min-height: 1.25rem;
  }

  #documentScanModal .document-scan-source-toggle .btn.active {
    background-color: #1e4b8e;
    border-color: #1e4b8e;
    color: #fff;
  }

  #documentScanModal .document-scan-pages {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }

  #documentScanModal .document-scan-page-thumb {
    position: relative;
    width: 72px;
    height: 96px;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    overflow: hidden;
    background: #f8f9fa;
  }

  #documentScanModal .document-scan-page-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  #documentScanModal .document-scan-page-thumb .btn-remove-page {
    position: absolute;
    top: 2px;
    right: 2px;
    padding: 0;
    width: 1.25rem;
    height: 1.25rem;
    line-height: 1;
    font-size: 0.7rem;
  }

  #documentScanModal .document-scan-page-num {
    position: absolute;
    left: 2px;
    bottom: 2px;
    background: rgba(15, 23, 42, 0.75);
    color: #fff;
    font-size: 0.65rem;
    padding: 0 4px;
    border-radius: 0.25rem;
  }

  #documentScanModal .document-scan-scanner-layout {
    display: flex;
    gap: 1.25rem;
    min-height: 520px;
  }

  #documentScanModal .document-scan-scanner-sidebar {
    width: 280px;
    flex-shrink: 0;
  }

  #documentScanModal .document-scan-scanner-preview {
    flex: 1;
    background: #1a1a1a;
    border-radius: 0.5rem;
    min-height: 520px;
    height: min(62vh, 640px);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    position: relative;
  }

  #documentScanModal .document-scan-scanner-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    display: block;
  }

  #documentScanModal .document-scan-scanner-empty {
    color: #94a3b8;
    text-align: center;
    padding: 1.5rem;
    font-size: 0.9rem;
  }

  #documentScanModal .document-scan-no-device {
    border: 1px solid #f5c2c7;
    background: #f8d7da;
    color: #842029;
    border-radius: 0.5rem;
    padding: 0.75rem 0.9rem;
    font-size: 0.875rem;
  }

  @media (max-width: 767.98px) {
    #documentScanModal .document-scan-scanner-layout {
      flex-direction: column;
    }
    #documentScanModal .document-scan-scanner-sidebar {
      width: 100%;
    }
  }

  /* Sit above nested upload modals (#modalTop / Bootstrap defaults ~1050/1055) */
  #documentScanModal {
    z-index: 11060000 !important;
  }

  .modal-backdrop.document-scan-backdrop {
    z-index: 11050 !important;
  }

  /* Device-aware option visibility */
  html[data-doc-scan-device="desktop"] .document-scan-mobile-only,
  html:not([data-doc-scan-device="mobile"]) .document-scan-mobile-only {
    display: none !important;
  }

  html[data-doc-scan-device="mobile"] .document-scan-desktop-only {
    display: none !important;
  }
</style>

<div class="modal fade" id="documentScanModal" tabindex="-1" aria-labelledby="documentScanModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-xl document-scan-modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="documentScanModalLabel">
          <i class="ti ti-scan me-1"></i>Scan Document
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="btn-group document-scan-source-toggle mb-3" role="group" aria-label="Scan source">
          <button type="button" class="btn btn-outline-primary btn-sm document-scan-mobile-only" id="documentScanSourceCamera" data-source="camera">
            <i class="ti ti-camera me-1"></i>Scan with Camera
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm document-scan-desktop-only" id="documentScanSourceScanner" data-source="scanner">
            <i class="ti ti-scanner me-1"></i>Document Scanner
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm" id="documentScanSourceFile" data-source="file">
            <i class="ti ti-upload me-1"></i>Upload File
          </button>
        </div>

        <p class="text-muted small mb-2" id="documentScanHelp">
          Align the document inside the frame, then capture. You can add multiple pages.
        </p>

        <div id="documentScanNoDeviceAlert" class="document-scan-no-device mb-3" hidden>
          No scanner device is connected. Please connect a scanner device and try again.
        </div>

        <!-- Scanner-only UI (never opens camera) -->
        <div id="documentScanScannerPanel" hidden>
          <div class="document-scan-scanner-layout">
            <div class="document-scan-scanner-sidebar">
              <div class="mb-3">
                <label class="form-label small mb-1" for="documentScanDeviceSelect">Scanner</label>
                <select id="documentScanDeviceSelect" class="form-select form-select-sm">
                  <option value="">Detecting devices…</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small mb-1" for="documentScanSourceType">Source</label>
                <select id="documentScanSourceType" class="form-select form-select-sm">
                  <option value="flatbed" selected>Flatbed</option>
                  <option value="adf">Feeder (ADF)</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label small mb-1" for="documentScanFileType">File type</label>
                <select id="documentScanFileType" class="form-select form-select-sm">
                  <option value="pdf" selected>PDF</option>
                  <option value="jpeg">JPEG</option>
                  <option value="png">PNG</option>
                </select>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary btn-sm flex-fill" id="documentScanPreviewBtn" disabled>
                  <i class="ti ti-zoom-scan me-1"></i>Preview
                </button>
                <button type="button" class="btn btn-primary btn-sm flex-fill" id="documentScanAcquireBtn" disabled>
                  <i class="ti ti-scanner me-1"></i>Scan
                </button>
              </div>
            </div>
            <div class="document-scan-scanner-preview" id="documentScanScannerPreview">
              <div class="document-scan-scanner-empty" id="documentScanScannerEmpty">
                Place the document on the scanner, then click Scan.
              </div>
              <img id="documentScanScannerPreviewImg" alt="Scan preview" hidden>
            </div>
          </div>
        </div>

        <!-- Camera-only UI -->
        <div id="documentScanCameraPanel">
          <div class="mb-2" id="documentScanCameraDeviceWrap" hidden>
            <label class="form-label small mb-1" for="documentScanCameraDeviceSelect">Camera</label>
            <select id="documentScanCameraDeviceSelect" class="form-select form-select-sm">
              <option value="">Detecting devices…</option>
            </select>
          </div>

          <div class="document-scan-video-wrap mb-2" id="documentScanVideoWrap">
            <video id="documentScanVideo" autoplay playsinline muted></video>
            <div class="document-scan-overlay" aria-hidden="true"></div>
          </div>
        </div>

        <canvas id="documentScanCanvas" class="d-none"></canvas>

        <div class="mb-2" id="documentScanPagesWrap" hidden>
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label small mb-0">Scanned pages <span class="text-muted" id="documentScanPageCount">(0)</span></label>
            <button type="button" class="btn btn-link btn-sm text-danger p-0" id="documentScanClearPagesBtn" hidden>Clear all</button>
          </div>
          <div class="document-scan-pages" id="documentScanPages"></div>
        </div>

        <div class="document-scan-status small text-muted" id="documentScanStatus">Starting…</div>
      </div>
      <div class="modal-footer flex-wrap gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-outline-primary" id="documentScanFallbackBtn">
          <i class="ti ti-camera me-1"></i>Use phone camera
        </button>
        <button type="button" class="btn btn-outline-primary" id="documentScanCaptureBtn" disabled>
          <i class="ti ti-camera-check me-1"></i>Capture page
        </button>
        <button type="button" class="btn btn-primary" id="documentScanConfirmBtn" disabled>
          <i class="ti ti-check me-1"></i>Use scanned document
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    if (window.__documentScanHelpersBound) {
      return;
    }
    window.__documentScanHelpersBound = true;

    var activeFileInputId = null;
    var activeCameraInputId = null;
    var activeScannerInputId = null;
    var mediaStream = null;
    var modalEl = null;
    var modalInstance = null;
    var currentSource = 'scanner';
    var videoDevices = [];
    var scannerDevices = [];
    var scannedPages = [];
    var jsPdfLoader = null;
    var isMobile = false;
    var SCANNER_LABEL_RE = /scan|scanner|document|twain|wia|epson|brother|canon|fujitsu|kodak|plustek|mustek|hp\s*scan|adf|flatbed|pixma|g\d{4}/i;

    function $(id) {
      return document.getElementById(id);
    }

    function detectMobileDevice() {
      try {
        var ua = navigator.userAgent || navigator.vendor || '';
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Mobile/i.test(ua)) {
          return true;
        }
        // iPadOS 13+ reports as Macintosh but is touch-first
        if (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1) {
          return true;
        }
        if (window.matchMedia) {
          var coarse = window.matchMedia('(pointer: coarse)').matches;
          var noHover = window.matchMedia('(hover: none)').matches;
          var narrow = window.matchMedia('(max-width: 991.98px)').matches;
          if (coarse && noHover) {
            return true;
          }
          if (narrow && (('ontouchstart' in window) || (navigator.maxTouchPoints || 0) > 0)) {
            return true;
          }
        }
      } catch (e) {}
      return false;
    }

    function applyDeviceMode() {
      isMobile = detectMobileDevice();
      document.documentElement.setAttribute('data-doc-scan-device', isMobile ? 'mobile' : 'desktop');
      return isMobile;
    }

    applyDeviceMode();
    currentSource = defaultScanSource();
    if (window.matchMedia) {
      try {
        window.matchMedia('(max-width: 991.98px)').addEventListener('change', applyDeviceMode);
      } catch (e) {
        // Older Safari
        try {
          window.matchMedia('(max-width: 991.98px)').addListener(applyDeviceMode);
        } catch (e2) {}
      }
    }

    function defaultScanSource() {
      return isMobile ? 'camera' : 'scanner';
    }

    function elevateAboveParentModals() {
      if (!modalEl) return;
      var maxZ = 1055;
      document.querySelectorAll('.modal.show, .modal.showing').forEach(function(el) {
        if (el === modalEl) return;
        var z = parseInt(window.getComputedStyle(el).zIndex, 10);
        if (!isNaN(z) && z > maxZ) maxZ = z;
      });
      document.querySelectorAll('.modal-backdrop').forEach(function(el) {
        var z = parseInt(window.getComputedStyle(el).zIndex, 10);
        if (!isNaN(z) && z > maxZ) maxZ = z;
      });
      modalEl.style.zIndex = String(maxZ + 20);
      setTimeout(function() {
        var backdrops = document.querySelectorAll('.modal-backdrop');
        var last = backdrops.length ? backdrops[backdrops.length - 1] : null;
        if (last) {
          last.classList.add('document-scan-backdrop');
          last.style.zIndex = String(maxZ + 10);
        }
      }, 0);
    }

    function ensureModal() {
      modalEl = $('documentScanModal');
      if (!modalEl) {
        return null;
      }
      // Avoid parent stacking contexts trapping the scan UI under #modalTop
      if (modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
      }
      if (window.bootstrap && bootstrap.Modal) {
        modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
          backdrop: 'static',
          keyboard: false,
          focus: true
        });
      }
      return modalEl;
    }

    function setStatus(text, isError) {
      var el = $('documentScanStatus');
      if (!el) return;
      el.textContent = text || '';
      el.classList.toggle('text-danger', !!isError);
      el.classList.toggle('text-muted', !isError);
    }

    function stopStream() {
      if (!mediaStream) return;
      try {
        mediaStream.getTracks().forEach(function(t) {
          t.stop();
        });
      } catch (e) {}
      mediaStream = null;
      var video = $('documentScanVideo');
      if (video) {
        video.srcObject = null;
      }
    }

    function updateSelectedLabel(fileInput) {
      if (!fileInput) return;
      var label = document.querySelector('[data-selected-for="' + fileInput.id + '"]');
      if (!label) return;
      var file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
      if (file) {
        label.textContent = file.name;
        label.classList.add('is-set');
        label.title = file.name;
      } else {
        label.textContent = 'No file selected';
        label.classList.remove('is-set');
        label.removeAttribute('title');
      }
    }

    function assignFileToInput(fileInput, file) {
      if (!fileInput || !file) return;
      try {
        var dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
        fileInput.classList.remove('is-invalid');
        fileInput.dispatchEvent(new Event('change', {
          bubbles: true
        }));
        updateSelectedLabel(fileInput);
      } catch (err) {
        setStatus('Could not attach scanned file. Try Select/Upload File instead.', true);
      }
    }

    function loadJsPdf() {
      if (window.jspdf && window.jspdf.jsPDF) {
        return Promise.resolve(window.jspdf.jsPDF);
      }
      if (jsPdfLoader) {
        return jsPdfLoader;
      }
      jsPdfLoader = new Promise(function(resolve, reject) {
        var script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
        script.async = true;
        script.onload = function() {
          if (window.jspdf && window.jspdf.jsPDF) {
            resolve(window.jspdf.jsPDF);
          } else {
            reject(new Error('jsPDF failed to load'));
          }
        };
        script.onerror = function() {
          reject(new Error('Could not load PDF library'));
        };
        document.head.appendChild(script);
      });
      return jsPdfLoader;
    }

    function blobToDataUrl(blob) {
      return new Promise(function(resolve, reject) {
        var reader = new FileReader();
        reader.onload = function() {
          resolve(reader.result);
        };
        reader.onerror = reject;
        reader.readAsDataURL(blob);
      });
    }

    function renderPages() {
      var wrap = $('documentScanPagesWrap');
      var list = $('documentScanPages');
      var count = $('documentScanPageCount');
      var clearBtn = $('documentScanClearPagesBtn');
      var confirmBtn = $('documentScanConfirmBtn');
      if (!list) return;

      list.innerHTML = '';
      scannedPages.forEach(function(page, idx) {
        var thumb = document.createElement('div');
        thumb.className = 'document-scan-page-thumb';
        thumb.innerHTML = '' +
          '<img src="' + page.dataUrl + '" alt="Page ' + (idx + 1) + '">' +
          '<span class="document-scan-page-num">' + (idx + 1) + '</span>' +
          '<button type="button" class="btn btn-danger btn-remove-page" data-page-index="' + idx + '" title="Remove page">&times;</button>';
        list.appendChild(thumb);
      });

      if (wrap) wrap.hidden = scannedPages.length === 0;
      if (count) count.textContent = '(' + scannedPages.length + ')';
      if (clearBtn) clearBtn.hidden = scannedPages.length === 0;
      if (confirmBtn) confirmBtn.disabled = scannedPages.length === 0;
    }

    function clearPages() {
      scannedPages = [];
      renderPages();
    }

    function addPageFromBlob(blob) {
      if (!blob) return Promise.resolve();
      return blobToDataUrl(blob).then(function(dataUrl) {
        scannedPages.push({
          blob: blob,
          dataUrl: dataUrl,
          type: blob.type || 'image/jpeg'
        });
        renderPages();
        setStatus('Page ' + scannedPages.length + ' added. Capture more pages or confirm.');
      });
    }

    async function pagesToFile() {
      if (!scannedPages.length) {
        throw new Error('No scanned pages');
      }

      var stamp = new Date().toISOString().replace(/[:.]/g, '-');
      var prefix = currentSource === 'scanner' ? 'scanner-document-' : 'scanned-document-';

      if (scannedPages.length === 1) {
        var single = scannedPages[0];
        var ext = (single.type || '').indexOf('png') !== -1 ? 'png' : 'jpg';
        return new File([single.blob], prefix + stamp + '.' + ext, {
          type: single.type || 'image/jpeg'
        });
      }

      var JsPDF = await loadJsPdf();
      var pdf = new JsPDF({
        orientation: 'portrait',
        unit: 'pt',
        format: 'a4'
      });
      var pageWidth = pdf.internal.pageSize.getWidth();
      var pageHeight = pdf.internal.pageSize.getHeight();
      var margin = 18;

      for (var i = 0; i < scannedPages.length; i++) {
        if (i > 0) {
          pdf.addPage();
        }
        var page = scannedPages[i];
        var format = (page.type || '').indexOf('png') !== -1 ? 'PNG' : 'JPEG';
        var img = await new Promise(function(resolve, reject) {
          var image = new Image();
          image.onload = function() {
            resolve(image);
          };
          image.onerror = reject;
          image.src = page.dataUrl;
        });
        var maxW = pageWidth - margin * 2;
        var maxH = pageHeight - margin * 2;
        var ratio = Math.min(maxW / img.width, maxH / img.height);
        var w = img.width * ratio;
        var h = img.height * ratio;
        var x = (pageWidth - w) / 2;
        var y = (pageHeight - h) / 2;
        pdf.addImage(page.dataUrl, format, x, y, w, h);
      }

      var pdfBlob = pdf.output('blob');
      return new File([pdfBlob], prefix + stamp + '.pdf', {
        type: 'application/pdf'
      });
    }

    async function confirmScannedDocument() {
      var fileInput = activeFileInputId ? $(activeFileInputId) : null;
      if (!fileInput) {
        setStatus('No target upload field found.', true);
        return;
      }
      if (!scannedPages.length) {
        setStatus('Capture at least one page first.', true);
        return;
      }

      var confirmBtn = $('documentScanConfirmBtn');
      if (confirmBtn) confirmBtn.disabled = true;
      setStatus(scannedPages.length > 1 ? 'Building PDF…' : 'Preparing document…');

      try {
        var file = await pagesToFile();
        assignFileToInput(fileInput, file);
        setStatus('Document ready.');
        clearPages();
        hideModal();
      } catch (err) {
        setStatus((err && err.message) ? err.message : 'Could not build scanned document.', true);
        if (confirmBtn) confirmBtn.disabled = false;
      }
    }

    function openNativeCameraFallback() {
      var cameraInput = activeCameraInputId ? $(activeCameraInputId) : null;
      if (cameraInput) {
        cameraInput.click();
        return;
      }
      var fileInput = activeFileInputId ? $(activeFileInputId) : null;
      if (fileInput) {
        fileInput.click();
      }
    }

    function openNativeFilePicker() {
      var fileInput = activeFileInputId ? $(activeFileInputId) : null;
      if (fileInput) {
        fileInput.click();
      }
    }

    function isScannerishDevice(device) {
      return !!(device && device.label && SCANNER_LABEL_RE.test(device.label));
    }

    function isWebcamDevice(device) {
      var label = (device && device.label) || '';
      return /webcam|facetime|integrated camera|front camera|user camera|hd camera|usb.?camera|laptop.?camera/i.test(label);
    }

    function scoreDevice(device, preferScanner) {
      var label = (device && device.label) || '';
      var score = 0;
      if (preferScanner && isScannerishDevice(device)) score += 100;
      if (!preferScanner && /back|rear|environment/i.test(label)) score += 40;
      if (!preferScanner && /front|user|facetime|integrated/i.test(label)) score -= 20;
      if (preferScanner && isWebcamDevice(device)) score -= 100;
      return score;
    }

    function setScannerControlsEnabled(enabled) {
      var acquireBtn = $('documentScanAcquireBtn');
      var previewBtn = $('documentScanPreviewBtn');
      if (acquireBtn) acquireBtn.disabled = !enabled;
      if (previewBtn) previewBtn.disabled = !enabled;
    }

    function showNoScannerMessage(show) {
      var alertEl = $('documentScanNoDeviceAlert');
      if (alertEl) alertEl.hidden = !show;
      if (show) {
        setStatus('No scanner device is connected. Please connect a scanner device and try again.', true);
        setScannerControlsEnabled(false);
      }
    }

    function clearScannerPreview() {
      var img = $('documentScanScannerPreviewImg');
      var empty = $('documentScanScannerEmpty');
      if (img) {
        img.hidden = true;
        img.removeAttribute('src');
      }
      if (empty) empty.hidden = false;
    }

    function setScannerPreviewFromBlob(blob) {
      var img = $('documentScanScannerPreviewImg');
      var empty = $('documentScanScannerEmpty');
      if (!img || !blob) return;
      var url = URL.createObjectURL(blob);
      img.onload = function() {
        try { URL.revokeObjectURL(url); } catch (e) {}
      };
      img.src = url;
      img.hidden = false;
      if (empty) empty.hidden = true;
    }

    function getDocumentScanApi() {
      return navigator.documentScan || navigator.documentscan || window.documentScan || null;
    }

    async function listDocumentScanApiScanners() {
      var api = getDocumentScanApi();
      if (!api) return [];
      try {
        if (typeof api.getScannerList === 'function') {
          var list = await api.getScannerList();
          var scanners = (list && (list.scanners || list)) || [];
          return (Array.isArray(scanners) ? scanners : []).map(function(s, idx) {
            return {
              id: String(s.scannerId || s.id || s.deviceId || ('api-' + idx)),
              label: s.name || s.label || s.model || ('Scanner ' + (idx + 1)),
              kind: 'api',
              raw: s
            };
          });
        }
        // Some implementations only expose scan() — treat as one virtual scanner
        if (typeof api.scan === 'function') {
          return [{
            id: 'document-scan-api',
            label: 'System document scanner',
            kind: 'api',
            raw: null
          }];
        }
      } catch (e) {}
      return [];
    }

    async function listScannerishMediaDevices() {
      // Never call getUserMedia here — that would open the camera.
      if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        return [];
      }
      try {
        var devices = await navigator.mediaDevices.enumerateDevices();
        return devices
          .filter(function(d) {
            return d.kind === 'videoinput' && isScannerishDevice(d) && !isWebcamDevice(d);
          })
          .map(function(d) {
            return {
              id: d.deviceId,
              label: d.label || 'Scanner device',
              kind: 'media',
              raw: d
            };
          });
      } catch (e) {
        return [];
      }
    }

    async function refreshScannerDevices() {
      var select = $('documentScanDeviceSelect');
      scannerDevices = [];
      if (!select) return [];

      select.innerHTML = '<option value="">Looking for scanners…</option>';
      setStatus('Looking for scanner devices…');

      var apiScanners = await listDocumentScanApiScanners();
      var mediaScanners = await listScannerishMediaDevices();
      scannerDevices = apiScanners.concat(mediaScanners);

      select.innerHTML = '';
      if (!scannerDevices.length) {
        select.innerHTML = '<option value="">No scanner connected</option>';
        showNoScannerMessage(true);
        clearScannerPreview();
        return [];
      }

      showNoScannerMessage(false);
      scannerDevices.forEach(function(device) {
        var opt = document.createElement('option');
        opt.value = device.id;
        opt.textContent = device.label;
        select.appendChild(opt);
      });
      select.value = scannerDevices[0].id;
      setScannerControlsEnabled(true);
      setStatus('Scanner ready. Place the document and click Scan.');
      return scannerDevices;
    }

    async function ensureDevicePermission() {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        return false;
      }
      try {
        var tmp = await navigator.mediaDevices.getUserMedia({
          audio: false,
          video: true
        });
        tmp.getTracks().forEach(function(t) {
          t.stop();
        });
        return true;
      } catch (e) {
        return false;
      }
    }

    async function refreshCameraDevices() {
      var select = $('documentScanCameraDeviceSelect');
      videoDevices = [];
      if (!select) return;

      select.innerHTML = '';
      if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        select.innerHTML = '<option value="">No camera API available</option>';
        return;
      }

      await ensureDevicePermission();
      var devices = await navigator.mediaDevices.enumerateDevices();
      videoDevices = devices.filter(function(d) {
        return d.kind === 'videoinput';
      });

      if (!videoDevices.length) {
        select.innerHTML = '<option value="">No camera devices found</option>';
        return;
      }

      var ranked = videoDevices.slice().sort(function(a, b) {
        return scoreDevice(b, false) - scoreDevice(a, false);
      });

      ranked.forEach(function(device, idx) {
        var opt = document.createElement('option');
        opt.value = device.deviceId;
        opt.textContent = device.label || ('Camera ' + (idx + 1));
        select.appendChild(opt);
      });

      if (ranked.length) {
        select.value = ranked[0].deviceId;
      }
    }

    function selectedCameraDeviceId() {
      var select = $('documentScanCameraDeviceSelect');
      return select && select.value ? select.value : null;
    }

    function selectedScannerDevice() {
      var select = $('documentScanDeviceSelect');
      var id = select && select.value ? select.value : null;
      if (!id) return null;
      for (var i = 0; i < scannerDevices.length; i++) {
        if (scannerDevices[i].id === id) return scannerDevices[i];
      }
      return null;
    }

    function selectedScanFileType() {
      var el = $('documentScanFileType');
      return (el && el.value) ? el.value : 'pdf';
    }

    function buildVideoConstraints() {
      var deviceId = selectedCameraDeviceId();
      if (deviceId) {
        return {
          audio: false,
          video: {
            deviceId: { exact: deviceId },
            width: { ideal: 1920 },
            height: { ideal: 1080 }
          }
        };
      }
      return {
        audio: false,
        video: {
          facingMode: { ideal: 'environment' },
          width: { ideal: 1920 },
          height: { ideal: 1080 }
        }
      };
    }

    async function startCaptureStream() {
      var video = $('documentScanVideo');
      var captureBtn = $('documentScanCaptureBtn');
      var videoWrap = $('documentScanVideoWrap');
      if (!video) return;

      // Camera mode only — Document Scanner must never open the camera.
      if (currentSource !== 'camera') {
        stopStream();
        if (captureBtn) captureBtn.disabled = true;
        return;
      }

      if (videoWrap) videoWrap.hidden = false;

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Live capture is not available in this browser. Use phone camera instead.', true);
        if (captureBtn) captureBtn.disabled = true;
        return;
      }

      stopStream();
      setStatus('Starting camera…');
      if (captureBtn) captureBtn.disabled = true;

      try {
        mediaStream = await navigator.mediaDevices.getUserMedia(buildVideoConstraints());
        video.srcObject = mediaStream;
        await video.play();
        setStatus('Camera ready. Align the document and capture each page.');
        if (captureBtn) captureBtn.disabled = false;
      } catch (err) {
        setStatus('Camera permission denied or unavailable. Use phone camera instead.', true);
        if (captureBtn) captureBtn.disabled = true;
      }
    }

    function captureFrame() {
      var video = $('documentScanVideo');
      var canvas = $('documentScanCanvas');
      if (!video || !canvas) return;
      if (!video.videoWidth || !video.videoHeight) {
        setStatus('Capture device is not ready yet.', true);
        return;
      }

      canvas.width = video.videoWidth;
      canvas.height = video.videoHeight;
      var ctx = canvas.getContext('2d');
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

      canvas.toBlob(function(blob) {
        if (!blob) {
          setStatus('Failed to capture image.', true);
          return;
        }
        addPageFromBlob(blob);
      }, 'image/jpeg', 0.92);
    }

    async function tryChromeDocumentScanApi(options) {
      options = options || {};
      var autoUpload = options.autoUpload !== false;
      var api = getDocumentScanApi();
      if (!api || typeof api.scan !== 'function') {
        return false;
      }

      try {
        setStatus(autoUpload ? 'Scanning with connected scanner…' : 'Generating scanner preview…');
        var mimeTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        var fileType = selectedScanFileType();
        if (fileType === 'jpeg') mimeTypes = ['image/jpeg', 'application/pdf', 'image/png'];
        if (fileType === 'png') mimeTypes = ['image/png', 'application/pdf', 'image/jpeg'];

        var scanOptions = {
          maxPages: autoUpload ? 20 : 1,
          mimeTypes: mimeTypes
        };
        var selected = selectedScannerDevice();
        if (selected && selected.kind === 'api' && selected.id && selected.id !== 'document-scan-api') {
          scanOptions.scannerId = selected.id;
        }

        var result = await api.scan(scanOptions);
        return await ingestScanResultAndUpload(result, { autoUpload: autoUpload });
      } catch (e) {
        setStatus('Scanner failed: ' + ((e && e.message) ? e.message : 'Unable to complete scan.'), true);
      }
      return false;
    }

    async function ingestScanResultAndUpload(result, options) {
      options = options || {};
      var autoUpload = options.autoUpload !== false;
      if (!result || !result.scans || !result.scans.length) {
        return false;
      }

      var first = result.scans[0];
      if (result.scans.length === 1 && first && (first.mimeType || '').indexOf('pdf') !== -1) {
        var pdfBlob = null;
        if (first.dataUrl) {
          pdfBlob = await (await fetch(first.dataUrl)).blob();
        } else if (first.data) {
          pdfBlob = new Blob([first.data], { type: first.mimeType || 'application/pdf' });
        }
        if (pdfBlob) {
          setScannerPreviewFromBlob(pdfBlob);
          if (!autoUpload) {
            setStatus('Preview ready. Click Scan to attach the document.');
            return true;
          }
          var stamp = new Date().toISOString().replace(/[:.]/g, '-');
          var file = new File([pdfBlob], 'scanner-document-' + stamp + '.pdf', {
            type: 'application/pdf'
          });
          var fileInput = activeFileInputId ? $(activeFileInputId) : null;
          assignFileToInput(fileInput, file);
          clearPages();
          setStatus('Document scanned and attached.');
          hideModal();
          return true;
        }
      }

      clearPages();
      for (var i = 0; i < result.scans.length; i++) {
        var scan = result.scans[i];
        var blob = null;
        if (scan.dataUrl) {
          blob = await (await fetch(scan.dataUrl)).blob();
        } else if (scan.data) {
          blob = new Blob([scan.data], { type: scan.mimeType || 'image/jpeg' });
        }
        if (blob && (blob.type || '').indexOf('pdf') === -1) {
          if (i === 0) setScannerPreviewFromBlob(blob);
          if (autoUpload) {
            await addPageFromBlob(blob);
          }
        }
      }

      if (!autoUpload) {
        setStatus('Preview ready. Click Scan to attach the document.');
        return true;
      }

      if (!scannedPages.length) {
        return false;
      }

      try {
        var built = await pagesToFile();
        var target = activeFileInputId ? $(activeFileInputId) : null;
        assignFileToInput(target, built);
        clearPages();
        setStatus('Document scanned and attached.');
        hideModal();
        return true;
      } catch (err) {
        setStatus(scannedPages.length + ' page(s) acquired. Review and confirm.', false);
        var confirmBtn = $('documentScanConfirmBtn');
        if (confirmBtn) {
          confirmBtn.hidden = false;
          confirmBtn.disabled = false;
        }
        return true;
      }
    }

    async function acquireFromSelectedScanner(options) {
      options = options || {};
      var autoUpload = options.autoUpload !== false;

      var devices = scannerDevices.length ? scannerDevices : await refreshScannerDevices();
      if (!devices.length) {
        showNoScannerMessage(true);
        return;
      }

      var selected = selectedScannerDevice();
      if (!selected) {
        showNoScannerMessage(true);
        return;
      }

      setScannerControlsEnabled(false);
      setStatus(autoUpload ? 'Scanning…' : 'Generating preview…');

      // Prefer system document-scan API (never uses camera)
      if (selected.kind === 'api' || getDocumentScanApi()) {
        var usedApi = await tryChromeDocumentScanApi({ autoUpload: autoUpload });
        if (usedApi) {
          if (!autoUpload) setScannerControlsEnabled(true);
          return;
        }
      }

      // Media "scanner" devices only (document cameras labeled as scanners) — never webcams
      if (selected.kind === 'media' && selected.id) {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          setStatus('This browser cannot capture from the selected scanner device.', true);
          setScannerControlsEnabled(true);
          return;
        }
        try {
          stopStream();
          mediaStream = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
              deviceId: { exact: selected.id },
              width: { ideal: 1920 },
              height: { ideal: 1080 }
            }
          });
          var video = $('documentScanVideo');
          var canvas = $('documentScanCanvas');
          if (!video || !canvas) throw new Error('Preview surface unavailable');
          video.srcObject = mediaStream;
          await video.play();
          await new Promise(function(resolve) { setTimeout(resolve, 350); });
          if (!video.videoWidth || !video.videoHeight) {
            throw new Error('Scanner did not return an image frame');
          }
          canvas.width = video.videoWidth;
          canvas.height = video.videoHeight;
          canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
          stopStream();
          var blob = await new Promise(function(resolve) {
            canvas.toBlob(function(b) { resolve(b); }, 'image/jpeg', 0.92);
          });
          if (!blob) throw new Error('Failed to capture scan');
          setScannerPreviewFromBlob(blob);
          if (!autoUpload) {
            setStatus('Preview ready. Click Scan to attach the document.');
            setScannerControlsEnabled(true);
            return;
          }
          await addPageFromBlob(blob);
          var file = await pagesToFile();
          assignFileToInput(activeFileInputId ? $(activeFileInputId) : null, file);
          clearPages();
          setStatus('Document scanned and attached.');
          hideModal();
          return;
        } catch (err) {
          stopStream();
          setStatus('Could not acquire image from scanner device.', true);
          setScannerControlsEnabled(true);
          return;
        }
      }

      showNoScannerMessage(true);
      setScannerControlsEnabled(false);
    }

    function updateSourceUi() {
      applyDeviceMode();
      var camBtn = $('documentScanSourceCamera');
      var scanBtn = $('documentScanSourceScanner');
      var fileBtn = $('documentScanSourceFile');
      var help = $('documentScanHelp');
      var fallbackBtn = $('documentScanFallbackBtn');
      var captureBtn = $('documentScanCaptureBtn');
      var confirmBtn = $('documentScanConfirmBtn');
      var title = $('documentScanModalLabel');
      var cameraPanel = $('documentScanCameraPanel');
      var scannerPanel = $('documentScanScannerPanel');
      var cameraDeviceWrap = $('documentScanCameraDeviceWrap');
      var noDeviceAlert = $('documentScanNoDeviceAlert');

      // Enforce device rules: desktop = scanner only, mobile = camera only
      if (!isMobile && currentSource === 'camera') {
        currentSource = 'scanner';
      }
      if (isMobile && currentSource === 'scanner') {
        currentSource = 'camera';
      }

      if (camBtn) camBtn.classList.toggle('active', currentSource === 'camera');
      if (scanBtn) scanBtn.classList.toggle('active', currentSource === 'scanner');
      if (fileBtn) fileBtn.classList.toggle('active', currentSource === 'file');

      if (currentSource === 'scanner') {
        if (title) title.innerHTML = '<i class="ti ti-scanner me-1"></i>Document Scanner';
        if (help) {
          help.textContent = 'Select a connected scanner device and click Scan. The camera is not used in this mode.';
        }
        if (scannerPanel) scannerPanel.hidden = false;
        if (cameraPanel) cameraPanel.hidden = true;
        if (cameraDeviceWrap) cameraDeviceWrap.hidden = true;
        if (fallbackBtn) fallbackBtn.hidden = true;
        if (captureBtn) captureBtn.hidden = true;
        if (confirmBtn) confirmBtn.hidden = true;
        stopStream();
      } else if (currentSource === 'file') {
        if (title) title.innerHTML = '<i class="ti ti-upload me-1"></i>Upload File';
        if (help) {
          help.textContent = 'Choose an existing document from your device. The file will use the same upload/save process.';
        }
        if (scannerPanel) scannerPanel.hidden = true;
        if (cameraPanel) cameraPanel.hidden = true;
        if (noDeviceAlert) noDeviceAlert.hidden = true;
        if (fallbackBtn) fallbackBtn.hidden = true;
        if (captureBtn) captureBtn.hidden = true;
        if (confirmBtn) confirmBtn.hidden = true;
        stopStream();
        setStatus('Click Upload File to browse, or cancel to go back.');
        openNativeFilePicker();
      } else {
        if (title) title.innerHTML = '<i class="ti ti-camera me-1"></i>Scan with Camera';
        if (help) {
          help.textContent = 'Align the document inside the frame, capture each page, then confirm. You can also use the device camera picker.';
        }
        if (scannerPanel) scannerPanel.hidden = true;
        if (cameraPanel) cameraPanel.hidden = false;
        if (cameraDeviceWrap) cameraDeviceWrap.hidden = false;
        if (noDeviceAlert) noDeviceAlert.hidden = true;
        if (fallbackBtn) fallbackBtn.hidden = false;
        if (captureBtn) {
          captureBtn.hidden = false;
          captureBtn.disabled = true;
        }
        if (confirmBtn) confirmBtn.hidden = false;
      }
    }

    async function applySource(source) {
      if (source === 'file') {
        currentSource = 'file';
        updateSourceUi();
        return;
      }
      if (source === 'scanner' && isMobile) {
        source = 'camera';
      }
      if (source === 'camera' && !isMobile) {
        source = 'scanner';
      }
      currentSource = source === 'scanner' ? 'scanner' : 'camera';
      updateSourceUi();

      if (currentSource === 'scanner') {
        clearScannerPreview();
        await refreshScannerDevices();
        return;
      }

      await refreshCameraDevices();
      await startCaptureStream();
    }

    function showModal(preferredSource) {
      applyDeviceMode();
      ensureModal();
      clearPages();
      clearScannerPreview();
      if (preferredSource === 'file') {
        currentSource = 'file';
      } else if (preferredSource === 'scanner' || preferredSource === 'camera') {
        currentSource = preferredSource;
      } else {
        currentSource = defaultScanSource();
      }
      updateSourceUi();
      elevateAboveParentModals();
      if (modalInstance) {
        modalInstance.show();
        elevateAboveParentModals();
      } else if (modalEl) {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
      }
      if (currentSource !== 'file') {
        applySource(currentSource);
      }
    }

    function hideModal() {
      stopStream();
      if (modalInstance) {
        modalInstance.hide();
      } else if (modalEl) {
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
      }
    }

    function openScanFor(fileInputId, cameraInputId, scannerInputId, preferredSource) {
      applyDeviceMode();
      activeFileInputId = fileInputId || null;
      activeCameraInputId = cameraInputId || null;
      activeScannerInputId = scannerInputId || null;

      if (preferredSource === 'file') {
        openNativeFilePicker();
        return;
      }

      // Device gate: desktop never opens camera; mobile never opens physical scanner UI
      if (preferredSource === 'camera' && !isMobile) {
        preferredSource = 'scanner';
      }
      if (preferredSource === 'scanner' && isMobile) {
        preferredSource = 'camera';
      }
      if (!preferredSource) {
        preferredSource = defaultScanSource();
      }

      // Document Scanner always opens the scanner modal — never the camera stream.
      showModal(preferredSource);
    }

    function bindModalControls() {
      ensureModal();
      var captureBtn = $('documentScanCaptureBtn');
      var fallbackBtn = $('documentScanFallbackBtn');
      var acquireBtn = $('documentScanAcquireBtn');
      var previewBtn = $('documentScanPreviewBtn');
      var confirmBtn = $('documentScanConfirmBtn');
      var clearBtn = $('documentScanClearPagesBtn');
      var pagesEl = $('documentScanPages');
      var camSourceBtn = $('documentScanSourceCamera');
      var scanSourceBtn = $('documentScanSourceScanner');
      var fileSourceBtn = $('documentScanSourceFile');
      var deviceSelect = $('documentScanDeviceSelect');
      var cameraDeviceSelect = $('documentScanCameraDeviceSelect');

      if (captureBtn && !captureBtn.__docScanBound) {
        captureBtn.__docScanBound = true;
        captureBtn.addEventListener('click', function(e) {
          e.preventDefault();
          captureFrame();
        });
      }
      if (confirmBtn && !confirmBtn.__docScanBound) {
        confirmBtn.__docScanBound = true;
        confirmBtn.addEventListener('click', function(e) {
          e.preventDefault();
          confirmScannedDocument();
        });
      }
      if (clearBtn && !clearBtn.__docScanBound) {
        clearBtn.__docScanBound = true;
        clearBtn.addEventListener('click', function(e) {
          e.preventDefault();
          clearPages();
          clearScannerPreview();
          setStatus('Pages cleared. Scan again.');
        });
      }
      if (pagesEl && !pagesEl.__docScanBound) {
        pagesEl.__docScanBound = true;
        pagesEl.addEventListener('click', function(e) {
          var btn = e.target.closest('.btn-remove-page');
          if (!btn) return;
          e.preventDefault();
          var idx = parseInt(btn.getAttribute('data-page-index'), 10);
          if (!isNaN(idx)) {
            scannedPages.splice(idx, 1);
            renderPages();
            setStatus(scannedPages.length ? 'Page removed.' : 'All pages removed.');
          }
        });
      }
      if (fallbackBtn && !fallbackBtn.__docScanBound) {
        fallbackBtn.__docScanBound = true;
        fallbackBtn.addEventListener('click', function(e) {
          e.preventDefault();
          hideModal();
          openNativeCameraFallback();
        });
      }
      if (acquireBtn && !acquireBtn.__docScanBound) {
        acquireBtn.__docScanBound = true;
        acquireBtn.addEventListener('click', async function(e) {
          e.preventDefault();
          await acquireFromSelectedScanner();
        });
      }
      if (previewBtn && !previewBtn.__docScanBound) {
        previewBtn.__docScanBound = true;
        previewBtn.addEventListener('click', async function(e) {
          e.preventDefault();
          await acquireFromSelectedScanner({ autoUpload: false });
        });
      }
      if (camSourceBtn && !camSourceBtn.__docScanBound) {
        camSourceBtn.__docScanBound = true;
        camSourceBtn.addEventListener('click', function(e) {
          e.preventDefault();
          applySource('camera');
        });
      }
      if (scanSourceBtn && !scanSourceBtn.__docScanBound) {
        scanSourceBtn.__docScanBound = true;
        scanSourceBtn.addEventListener('click', function(e) {
          e.preventDefault();
          applySource('scanner');
        });
      }
      if (fileSourceBtn && !fileSourceBtn.__docScanBound) {
        fileSourceBtn.__docScanBound = true;
        fileSourceBtn.addEventListener('click', function(e) {
          e.preventDefault();
          applySource('file');
        });
      }
      if (deviceSelect && !deviceSelect.__docScanBound) {
        deviceSelect.__docScanBound = true;
        deviceSelect.addEventListener('change', function() {
          // Selecting a scanner must never start the camera stream
          var selected = selectedScannerDevice();
          setScannerControlsEnabled(!!selected);
          if (selected) {
            showNoScannerMessage(false);
            setStatus('Scanner selected: ' + selected.label + '. Click Scan when ready.');
          } else {
            showNoScannerMessage(true);
          }
        });
      }
      if (cameraDeviceSelect && !cameraDeviceSelect.__docScanBound) {
        cameraDeviceSelect.__docScanBound = true;
        cameraDeviceSelect.addEventListener('change', function() {
          if (currentSource === 'camera') {
            startCaptureStream();
          }
        });
      }
      if (modalEl && !modalEl.__docScanBound) {
        modalEl.__docScanBound = true;
        modalEl.addEventListener('show.bs.modal', elevateAboveParentModals);
        modalEl.addEventListener('shown.bs.modal', elevateAboveParentModals);
        modalEl.addEventListener('hidden.bs.modal', function() {
          stopStream();
          clearPages();
          clearScannerPreview();
        });
      }
    }

    async function ingestPickedFiles(fileList, prefix) {
      var files = Array.prototype.slice.call(fileList || []);
      if (!files.length) return;

      var fileInput = activeFileInputId ? $(activeFileInputId) : null;
      var images = files.filter(function(f) {
        return (f.type || '').indexOf('image/') === 0 || /\.(jpe?g|png|tif|tiff)$/i.test(f.name || '');
      });
      var pdfs = files.filter(function(f) {
        return (f.type || '').indexOf('pdf') !== -1 || /\.pdf$/i.test(f.name || '');
      });

      if (pdfs.length === 1 && images.length === 0) {
        var stamp = new Date().toISOString().replace(/[:.]/g, '-');
        var named = new File([pdfs[0]], prefix + stamp + '.pdf', {
          type: 'application/pdf'
        });
        assignFileToInput(fileInput, named);
        hideModal();
        return;
      }

      if (images.length) {
        clearPages();
        for (var i = 0; i < images.length; i++) {
          await addPageFromBlob(images[i]);
        }
        // Single image from native picker/camera: attach immediately.
        if (images.length === 1) {
          try {
            var file = await pagesToFile();
            assignFileToInput(fileInput, file);
            clearPages();
            hideModal();
          } catch (err) {
            setStatus((err && err.message) ? err.message : 'Could not attach scanned image.', true);
          }
          return;
        }
        ensureModal();
        elevateAboveParentModals();
        if (modalInstance) {
          modalInstance.show();
          elevateAboveParentModals();
        } else if (modalEl) {
          modalEl.classList.add('show');
          modalEl.style.display = 'block';
        }
        setStatus(images.length + ' pages ready. Confirm to attach as one PDF.');
        return;
      }

      if (files[0] && fileInput) {
        var stamp2 = new Date().toISOString().replace(/[:.]/g, '-');
        var ext = (files[0].name && files[0].name.indexOf('.') !== -1) ?
          files[0].name.split('.').pop() :
          'bin';
        assignFileToInput(fileInput, new File([files[0]], prefix + stamp2 + '.' + ext, {
          type: files[0].type || 'application/octet-stream'
        }));
        hideModal();
      }
    }

    document.addEventListener('click', function(e) {
      var uploadBtn = e.target.closest('.document-scan-upload-btn');
      if (uploadBtn) {
        e.preventDefault();
        var fileId = uploadBtn.getAttribute('data-file-input');
        var fileInput = fileId ? $(fileId) : null;
        if (fileInput) fileInput.click();
        return;
      }

      var scannerBtn = e.target.closest('.document-scan-scanner-btn');
      if (scannerBtn) {
        e.preventDefault();
        openScanFor(
          scannerBtn.getAttribute('data-file-input'),
          scannerBtn.getAttribute('data-camera-input'),
          scannerBtn.getAttribute('data-scanner-input'),
          'scanner'
        );
        return;
      }

      var scanBtn = e.target.closest('.document-scan-camera-btn');
      if (scanBtn) {
        e.preventDefault();
        openScanFor(
          scanBtn.getAttribute('data-file-input'),
          scanBtn.getAttribute('data-camera-input'),
          scanBtn.getAttribute('data-scanner-input'),
          scanBtn.getAttribute('data-scan-source') || 'camera'
        );
      }
    });

    document.addEventListener('change', function(e) {
      var target = e.target;
      if (!target) return;

      if (target.classList && target.classList.contains('document-scan-file-input')) {
        updateSelectedLabel(target);
        return;
      }

      if (target.classList && (
          target.classList.contains('document-scan-camera-input') ||
          target.classList.contains('document-scan-scanner-input')
        )) {
        var root = target.closest('[data-document-scan-root]');
        var fileInput = root ? root.querySelector('.document-scan-file-input') : null;
        var picked = target.files;
        target.value = '';
        if (fileInput && picked && picked.length) {
          activeFileInputId = fileInput.id;
          var prefix = target.classList.contains('document-scan-scanner-input') ?
            'scanner-document-' :
            'scanned-document-';
          ingestPickedFiles(picked, prefix);
        }
      }
    });

    document.addEventListener('DOMContentLoaded', bindModalControls);
    if (document.readyState !== 'loading') {
      bindModalControls();
    }

    window.DocumentScanField = {
      updateSelectedLabel: updateSelectedLabel,
      openScanFor: openScanFor,
      isMobile: function() {
        applyDeviceMode();
        return isMobile;
      },
      markup: function(opts) {
        opts = opts || {};
        var name = opts.name || 'file';
        var id = opts.id || ('doc_scan_' + String(Math.random()).slice(2, 8));
        var scanId = id + '_camera';
        var scannerId = id + '_scanner';
        var label = opts.label || 'Document';
        var required = !!opts.required;
        var accept = opts.accept || '.jpg,.jpeg,.png,.pdf,.tif,.tiff,.doc,.docx,image/jpeg,image/png,image/tiff,application/pdf';
        var inputClass = 'document-scan-file-input form-control rider-document-file' + (opts.inputClass ? (' ' + opts.inputClass) : '');
        var requiredAttr = required ? ' required' : '';
        var safeLabel = String(label)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;');
        return '' +
          '<div class="document-scan-field document-scan-field--compact mb-3" data-document-scan-root data-universal-document-upload>' +
          '<label class="form-label' + (required ? ' required fw-bold' : '') + '">' + safeLabel + '</label>' +
          '<input type="file" name="' + name + '" id="' + id + '" class="document-scan-native-file ' + inputClass + '" accept="' + accept + '"' + requiredAttr + '>' +
          '<input type="file" id="' + scanId + '" class="document-scan-camera-input document-scan-native-file" accept="image/*" capture="environment" tabindex="-1" aria-hidden="true">' +
          '<input type="file" id="' + scannerId + '" class="document-scan-scanner-input document-scan-native-file" accept="image/*,application/pdf,.jpg,.jpeg,.png,.tif,.tiff,.pdf" multiple tabindex="-1" aria-hidden="true">' +
          '<div class="d-flex flex-wrap gap-2 mb-1">' +
          '<button type="button" class="btn btn-outline-primary btn-sm document-scan-upload-btn" data-file-input="' + id + '">' +
          '<i class="ti ti-upload me-1"></i>Upload File' +
          '</button>' +
          '<button type="button" class="btn btn-outline-secondary btn-sm document-scan-camera-btn document-scan-mobile-only" data-file-input="' + id + '" data-camera-input="' + scanId + '" data-scanner-input="' + scannerId + '" data-scan-source="camera">' +
          '<i class="ti ti-camera me-1"></i>Scan with Camera' +
          '</button>' +
          '<button type="button" class="btn btn-outline-secondary btn-sm document-scan-scanner-btn document-scan-desktop-only" data-file-input="' + id + '" data-camera-input="' + scanId + '" data-scanner-input="' + scannerId + '" data-scan-source="scanner">' +
          '<i class="ti ti-scanner me-1"></i>Document Scanner' +
          '</button>' +
          '</div>' +
          '<div class="document-scan-selected small text-muted" data-selected-for="' + id + '">No file selected</div>' +
          '<div class="form-text document-scan-desktop-only">Upload a file or scan with a connected scanner. Multi-page scans save as one PDF.</div>' +
          '<div class="form-text document-scan-mobile-only">Upload a file or scan with the camera. Multi-page scans save as one PDF.</div>' +
          '<div class="invalid-feedback rider-document-upload-error document-scan-upload-error"></div>' +
          '</div>';
      }
    };
  })();
</script>
@endonce