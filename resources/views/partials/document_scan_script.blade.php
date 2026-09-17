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

  #documentScanModal .document-scan-video-wrap {
    position: relative;
    background: #0f172a;
    border-radius: 0.75rem;
    overflow: hidden;
    min-height: 280px;
  }

  #documentScanModal video {
    width: 100%;
    max-height: 420px;
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

  /* Sit above nested upload modals (#modalTop / Bootstrap defaults ~1050/1055) */
  #documentScanModal {
    z-index: 11060 !important;
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
  <div class="modal-dialog modal-dialog-centered modal-lg">
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
            <i class="ti ti-scanner me-1"></i>Scan with Scanner
          </button>
          <button type="button" class="btn btn-outline-primary btn-sm" id="documentScanSourceFile" data-source="file">
            <i class="ti ti-upload me-1"></i>Upload File
          </button>
        </div>

        <p class="text-muted small mb-2" id="documentScanHelp">
          Align the document inside the frame, then capture. You can add multiple pages.
        </p>

        <div class="mb-2" id="documentScanDeviceWrap" hidden>
          <label class="form-label small mb-1" for="documentScanDeviceSelect">Scanner / capture device</label>
          <select id="documentScanDeviceSelect" class="form-select form-select-sm">
            <option value="">Detecting devices…</option>
          </select>
        </div>

        <div class="document-scan-video-wrap mb-2" id="documentScanVideoWrap">
          <video id="documentScanVideo" autoplay playsinline muted></video>
          <div class="document-scan-overlay" aria-hidden="true"></div>
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
        <button type="button" class="btn btn-outline-primary" id="documentScanAcquireBtn" hidden>
          <i class="ti ti-scanner me-1"></i>Acquire from scanner
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
  var scannedPages = [];
  var jsPdfLoader = null;
  var isMobile = false;
  var SCANNER_LABEL_RE = /scan|scanner|document|twain|wia|epson|brother|canon|fujitsu|kodak|plustek|mustek|hp\s*scan|adf|flatbed/i;

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

    function openNativeScannerAcquire() {
      var scannerInput = activeScannerInputId ? $(activeScannerInputId) : null;
      if (scannerInput) {
        scannerInput.click();
        return;
      }
      openNativeCameraFallback();
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

    function scoreDevice(device, preferScanner) {
      var label = (device && device.label) || '';
      var score = 0;
      if (preferScanner && isScannerishDevice(device)) score += 100;
      if (!preferScanner && /back|rear|environment/i.test(label)) score += 40;
      if (!preferScanner && /front|user|facetime|integrated/i.test(label)) score -= 20;
      if (preferScanner && /webcam|facetime|integrated camera/i.test(label)) score -= 30;
      return score;
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

    async function refreshDevices() {
      var select = $('documentScanDeviceSelect');
      videoDevices = [];
      if (!select) return;
      select.innerHTML = '';

      if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        select.innerHTML = '<option value="">No device API available</option>';
        return;
      }

      await ensureDevicePermission();
      var devices = await navigator.mediaDevices.enumerateDevices();
      videoDevices = devices.filter(function(d) {
        return d.kind === 'videoinput';
      });

      if (!videoDevices.length) {
        select.innerHTML = '<option value="">No camera/scanner devices found</option>';
        return;
      }

      var preferScanner = currentSource === 'scanner';
      var ranked = videoDevices.slice().sort(function(a, b) {
        return scoreDevice(b, preferScanner) - scoreDevice(a, preferScanner);
      });

      ranked.forEach(function(device, idx) {
        var opt = document.createElement('option');
        opt.value = device.deviceId;
        var name = device.label || ('Camera ' + (idx + 1));
        if (isScannerishDevice(device)) {
          name = 'Scanner: ' + name;
        }
        opt.textContent = name;
        select.appendChild(opt);
      });

      if (ranked.length) {
        select.value = ranked[0].deviceId;
      }
    }

    function selectedDeviceId() {
      var select = $('documentScanDeviceSelect');
      return select && select.value ? select.value : null;
    }

    function buildVideoConstraints(preferScanner) {
      var deviceId = selectedDeviceId();
      if (deviceId) {
        return {
          audio: false,
          video: {
            deviceId: {
              exact: deviceId
            },
            width: {
              ideal: 1920
            },
            height: {
              ideal: 1080
            }
          }
        };
      }
      if (preferScanner) {
        return {
          audio: false,
          video: {
            width: {
              ideal: 1920
            },
            height: {
              ideal: 1080
            }
          }
        };
      }
      return {
        audio: false,
        video: {
          facingMode: {
            ideal: 'environment'
          },
          width: {
            ideal: 1920
          },
          height: {
            ideal: 1080
          }
        }
      };
    }

    async function startCaptureStream() {
      var video = $('documentScanVideo');
      var captureBtn = $('documentScanCaptureBtn');
      var videoWrap = $('documentScanVideoWrap');
      if (!video) return;

      if (currentSource === 'file') {
        stopStream();
        if (videoWrap) videoWrap.hidden = true;
        if (captureBtn) captureBtn.disabled = true;
        return;
      }

      if (videoWrap) videoWrap.hidden = false;

      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        setStatus('Live capture is not available in this browser. Use Acquire from scanner or Select/Upload File.', true);
        if (captureBtn) captureBtn.disabled = true;
        return;
      }

      stopStream();
      setStatus(currentSource === 'scanner' ? 'Connecting to scanner…' : 'Starting camera…');
      if (captureBtn) captureBtn.disabled = true;

      try {
        mediaStream = await navigator.mediaDevices.getUserMedia(buildVideoConstraints(currentSource === 'scanner'));
        video.srcObject = mediaStream;
        await video.play();
        setStatus(currentSource === 'scanner' ?
          'Scanner ready. Place the document and capture each page.' :
          'Camera ready. Align the document and capture each page.');
        if (captureBtn) captureBtn.disabled = false;
      } catch (err) {
        setStatus(
          currentSource === 'scanner' ?
          'Could not open scanner device. Try another device, or use Acquire from scanner.' :
          'Camera permission denied or unavailable. Use phone camera instead.',
          true
        );
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

    async function tryChromeDocumentScanApi() {
      var api = navigator.documentScan || navigator.documentscan || window.documentScan;
      if (!api || (typeof api.getScannerList !== 'function' && typeof api.scan !== 'function')) {
        return false;
      }

      try {
        setStatus('Looking for scanner devices…');
        if (typeof api.scan === 'function') {
          var result = await api.scan({
            maxPages: 20,
            mimeTypes: ['image/jpeg', 'image/png', 'application/pdf']
          });
          if (result && result.scans && result.scans.length) {
            var first = result.scans[0];
            if (result.scans.length === 1 && first && (first.mimeType || '').indexOf('pdf') !== -1) {
              var pdfBlob = null;
              if (first.dataUrl) {
                pdfBlob = await (await fetch(first.dataUrl)).blob();
              } else if (first.data) {
                pdfBlob = new Blob([first.data], {
                  type: first.mimeType || 'application/pdf'
                });
              }
              if (pdfBlob) {
                var stamp = new Date().toISOString().replace(/[:.]/g, '-');
                var file = new File([pdfBlob], 'scanner-document-' + stamp + '.pdf', {
                  type: 'application/pdf'
                });
                var fileInput = activeFileInputId ? $(activeFileInputId) : null;
                assignFileToInput(fileInput, file);
                clearPages();
                hideModal();
                return true;
              }
            }

            for (var i = 0; i < result.scans.length; i++) {
              var scan = result.scans[i];
              var blob = null;
              if (scan.dataUrl) {
                blob = await (await fetch(scan.dataUrl)).blob();
              } else if (scan.data) {
                blob = new Blob([scan.data], {
                  type: scan.mimeType || 'image/jpeg'
                });
              }
              if (blob && (blob.type || '').indexOf('pdf') === -1) {
                await addPageFromBlob(blob);
              }
            }
            if (scannedPages.length) {
              setStatus(scannedPages.length + ' page(s) acquired. Review and confirm.');
              return true;
            }
          }
        }
      } catch (e) {
        setStatus('System scanner API failed. Try selecting a device or Acquire from scanner.', true);
      }
      return false;
    }

    function updateSourceUi() {
      applyDeviceMode();
      var camBtn = $('documentScanSourceCamera');
      var scanBtn = $('documentScanSourceScanner');
      var fileBtn = $('documentScanSourceFile');
      var help = $('documentScanHelp');
      var deviceWrap = $('documentScanDeviceWrap');
      var acquireBtn = $('documentScanAcquireBtn');
      var fallbackBtn = $('documentScanFallbackBtn');
      var captureBtn = $('documentScanCaptureBtn');
      var title = $('documentScanModalLabel');
      var videoWrap = $('documentScanVideoWrap');

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
        if (title) title.innerHTML = '<i class="ti ti-scanner me-1"></i>Scan with Scanner';
        if (help) {
          help.textContent = 'Select a connected scanner, capture each page, then confirm. For Windows flatbed/ADF scanners, use Acquire from scanner.';
        }
        if (deviceWrap) deviceWrap.hidden = false;
        if (acquireBtn) acquireBtn.hidden = false;
        if (fallbackBtn) fallbackBtn.hidden = true;
        if (videoWrap) videoWrap.hidden = false;
        if (captureBtn) captureBtn.hidden = false;
      } else if (currentSource === 'file') {
        if (title) title.innerHTML = '<i class="ti ti-upload me-1"></i>Upload File';
        if (help) {
          help.textContent = 'Choose an existing document from your device. The file will use the same upload/save process.';
        }
        if (deviceWrap) deviceWrap.hidden = true;
        if (acquireBtn) acquireBtn.hidden = true;
        if (fallbackBtn) fallbackBtn.hidden = true;
        if (videoWrap) videoWrap.hidden = true;
        if (captureBtn) captureBtn.hidden = true;
        stopStream();
        setStatus('Click Upload File to browse, or cancel to go back.');
        openNativeFilePicker();
      } else {
        if (title) title.innerHTML = '<i class="ti ti-camera me-1"></i>Scan with Camera';
        if (help) {
          help.textContent = 'Align the document inside the frame, capture each page, then confirm. You can also use the device camera picker.';
        }
        if (deviceWrap) deviceWrap.hidden = false;
        if (acquireBtn) acquireBtn.hidden = true;
        if (fallbackBtn) fallbackBtn.hidden = false;
        if (videoWrap) videoWrap.hidden = false;
        if (captureBtn) captureBtn.hidden = false;
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
      await refreshDevices();
      await startCaptureStream();
    }

    function showModal(preferredSource) {
      applyDeviceMode();
      ensureModal();
      clearPages();
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

      if (preferredSource === 'scanner' && !(navigator.mediaDevices && navigator.mediaDevices.getUserMedia)) {
        openNativeScannerAcquire();
        return;
      }

      if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        showModal(preferredSource);
        return;
      }

      if (preferredSource === 'scanner') {
        openNativeScannerAcquire();
        return;
      }
      openNativeCameraFallback();
    }

    function bindModalControls() {
      ensureModal();
      var captureBtn = $('documentScanCaptureBtn');
      var fallbackBtn = $('documentScanFallbackBtn');
      var acquireBtn = $('documentScanAcquireBtn');
      var confirmBtn = $('documentScanConfirmBtn');
      var clearBtn = $('documentScanClearPagesBtn');
      var pagesEl = $('documentScanPages');
      var camSourceBtn = $('documentScanSourceCamera');
      var scanSourceBtn = $('documentScanSourceScanner');
      var fileSourceBtn = $('documentScanSourceFile');
      var deviceSelect = $('documentScanDeviceSelect');

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
          setStatus('Pages cleared. Capture again.');
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
          var usedApi = await tryChromeDocumentScanApi();
          if (!usedApi) {
            openNativeScannerAcquire();
          }
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
          startCaptureStream();
        });
      }
      if (modalEl && !modalEl.__docScanBound) {
        modalEl.__docScanBound = true;
        modalEl.addEventListener('show.bs.modal', elevateAboveParentModals);
        modalEl.addEventListener('shown.bs.modal', elevateAboveParentModals);
        modalEl.addEventListener('hidden.bs.modal', function() {
          stopStream();
          clearPages();
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
      isMobile: function () { applyDeviceMode(); return isMobile; },
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
          '<i class="ti ti-scanner me-1"></i>Scan with Scanner' +
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