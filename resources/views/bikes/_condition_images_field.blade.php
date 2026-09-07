@php
$label = $label ?? ($field->resolvedLabel() ?? 'Vehicle condition photos');
$required = (bool) ($required ?? false);
$controlRequired = (bool) ($controlRequired ?? $required);
$colClass = $colClass ?? 'col-md-6';
$groupClass = $groupClass ?? '';
$wrapperId = $wrapperId ?? 'assign-field-condition_images';
$inputId = $inputId ?? 'condition_images';
$cameraInputId = $cameraInputId ?? ($inputId . '_camera');
$previewId = $previewId ?? ($inputId . '_preview');
$hintId = $hintId ?? ($inputId . '_hint');
$addBtnId = $addBtnId ?? ($inputId . '_add_btn');
$cameraBtnId = $cameraBtnId ?? ($inputId . '_camera_btn');
@endphp
<div class="{{ $colClass }} form-group {{ $groupClass }}" id="{{ $wrapperId }}" data-assign-field="condition_images" data-condition-images-root="1">
    <label for="{{ $inputId }}">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>

    <input
        type="file"
        name="condition_images[]"
        id="{{ $inputId }}"
        class="d-none condition-images-input"
        multiple
        accept="image/*,.pdf,application/pdf,.heic,.heif"
        @if($controlRequired) required data-assign-required="1" @endif>

    <input
        type="file"
        id="{{ $cameraInputId }}"
        class="d-none condition-images-camera-input"
        accept="image/*"
        capture="environment"
        tabindex="-1"
        aria-hidden="true">

    <div class="d-flex flex-wrap gap-2 mb-2">
        <button type="button" class="btn btn-outline-primary btn-sm" id="{{ $addBtnId }}">
            <i class="ti ti-photo-plus me-1"></i>Add images
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="{{ $cameraBtnId }}">
            <i class="ti ti-camera me-1"></i>Take photo
        </button>
    </div>

    <div id="{{ $previewId }}" class="condition-images-preview d-flex flex-wrap gap-2"></div>

    <small class="text-muted d-block mt-1" id="{{ $hintId }}">
        One file is stored as uploaded. Multiple images are combined into a single PDF.
    </small>
</div>
<style>
    .condition-images-preview .condition-image-thumb {
        position: relative;
        width: 88px;
        height: 88px;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        overflow: hidden;
        background: #f8f9fa;
        flex: 0 0 auto;
    }
    .condition-images-preview .condition-image-thumb img,
    .condition-images-preview .condition-image-thumb .condition-image-file-icon {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .condition-images-preview .condition-image-file-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 0.25rem;
        padding: 0.35rem;
        text-align: center;
        font-size: 0.65rem;
        color: #6c757d;
        word-break: break-all;
        line-height: 1.15;
    }
    .condition-images-preview .condition-image-file-icon i {
        font-size: 1.35rem;
    }
    .condition-images-preview .condition-image-remove {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 22px;
        height: 22px;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: rgba(220, 53, 69, 0.92);
        color: #fff;
        line-height: 22px;
        font-size: 14px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .condition-images-preview .condition-image-remove:hover {
        background: #b02a37;
    }
</style>
<script>
    (function() {
        var root = document.getElementById(@json($wrapperId));
        if (!root || root.dataset.conditionImagesBound === '1') {
            return;
        }
        root.dataset.conditionImagesBound = '1';

        var input = document.getElementById(@json($inputId));
        var cameraInput = document.getElementById(@json($cameraInputId));
        var hint = document.getElementById(@json($hintId));
        var preview = document.getElementById(@json($previewId));
        var addBtn = document.getElementById(@json($addBtnId));
        var cameraBtn = document.getElementById(@json($cameraBtnId));
        if (!input || !preview || !addBtn || !cameraBtn) {
            return;
        }

        var selectedFiles = [];
        var objectUrls = [];

        function revokeObjectUrls() {
            objectUrls.forEach(function(url) {
                try { URL.revokeObjectURL(url); } catch (e) {}
            });
            objectUrls = [];
        }

        function isImageFile(file) {
            if (!file) return false;
            if (file.type && file.type.indexOf('image/') === 0) return true;
            var name = (file.name || '').toLowerCase();
            return /\.(jpe?g|png|gif|webp|bmp|heic|heif)$/.test(name);
        }

        function syncInputFiles() {
            var dt = new DataTransfer();
            selectedFiles.forEach(function(file) {
                dt.items.add(file);
            });
            input.files = dt.files;

            // Some browsers skip required checks on programmatically set FileLists.
            if (input.hasAttribute('data-assign-required')) {
                if (selectedFiles.length > 0) {
                    input.removeAttribute('required');
                } else {
                    input.setAttribute('required', 'required');
                }
            }

            updateHint();
            renderPreview();
        }

        function updateHint() {
            if (!hint) return;
            var n = selectedFiles.length;
            if (n > 1) {
                hint.textContent = n + ' files selected — images will be saved as one PDF.';
            } else if (n === 1) {
                hint.textContent = '1 file selected — it will be stored as-is.';
            } else {
                hint.textContent = 'One file is stored as uploaded. Multiple images are combined into a single PDF.';
            }
        }

        function renderPreview() {
            revokeObjectUrls();
            preview.innerHTML = '';

            selectedFiles.forEach(function(file, index) {
                var thumb = document.createElement('div');
                thumb.className = 'condition-image-thumb';
                thumb.title = file.name || ('File ' + (index + 1));

                if (isImageFile(file) && !(file.name || '').toLowerCase().match(/\.heic$|\.heif$/)) {
                    var url = URL.createObjectURL(file);
                    objectUrls.push(url);
                    var img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name || 'Selected image';
                    thumb.appendChild(img);
                } else {
                    var iconWrap = document.createElement('div');
                    iconWrap.className = 'condition-image-file-icon';
                    var icon = document.createElement('i');
                    icon.className = (file.type === 'application/pdf' || /\.pdf$/i.test(file.name || ''))
                        ? 'ti ti-file-type-pdf'
                        : 'ti ti-file';
                    iconWrap.appendChild(icon);
                    var nameSpan = document.createElement('span');
                    nameSpan.textContent = file.name || ('File ' + (index + 1));
                    iconWrap.appendChild(nameSpan);
                    thumb.appendChild(iconWrap);
                }

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'condition-image-remove';
                removeBtn.setAttribute('aria-label', 'Remove ' + (file.name || 'file'));
                removeBtn.innerHTML = '&times;';
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    selectedFiles.splice(index, 1);
                    syncInputFiles();
                });
                thumb.appendChild(removeBtn);
                preview.appendChild(thumb);
            });
        }

        function appendFiles(fileList) {
            if (!fileList || !fileList.length) return;
            Array.prototype.forEach.call(fileList, function(file) {
                selectedFiles.push(file);
            });
            if (selectedFiles.length > 20) {
                selectedFiles = selectedFiles.slice(0, 20);
                if (hint) {
                    hint.textContent = 'Maximum 20 files allowed. Extra files were not added.';
                }
            }
            syncInputFiles();
        }

        addBtn.addEventListener('click', function() {
            input.click();
        });

        cameraBtn.addEventListener('click', function() {
            if (cameraInput) {
                cameraInput.click();
            } else {
                input.click();
            }
        });

        input.addEventListener('change', function() {
            var picked = this.files ? Array.prototype.slice.call(this.files) : [];
            // Reset native value so the same file can be chosen again later.
            this.value = '';
            appendFiles(picked);
        });

        if (cameraInput) {
            cameraInput.addEventListener('change', function() {
                var picked = this.files ? Array.prototype.slice.call(this.files) : [];
                this.value = '';
                appendFiles(picked);
            });
        }

        var form = root.closest('form');
        if (form) {
            form.addEventListener('reset', function() {
                selectedFiles = [];
                syncInputFiles();
            });
        }
    })();
</script>
