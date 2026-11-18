/**
 * السكربت المشترك للموقع:
 * 1) توليد رموز QR، نسخ الروابط، التأكيد قبل الحذف، ومناطق السحب البسيطة.
 * 2) إدارة صفحات الأدوات (اختيار الأداة، معاينة الملف، القص التفاعلي، التنفيذ، وتحويل الناتج لرابط/QR).
 */
(() => {
    'use strict';

    const I18N = window.JU_I18N || {};
    const tr = (k, fallback, vars = {}) => {
        let s = I18N[k] || fallback;
        for (const [vk, vv] of Object.entries(vars)) s = s.replace(`:${vk}`, vv);
        return s;
    };

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    const fmtBytes = (b) => {
        const u = Array.isArray(I18N.bytes) ? I18N.bytes : ['بايت', 'KB', 'MB', 'GB'];
        let i = 0, n = Number(b) || 0;
        while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
        return `${n.toFixed(i ? 1 : 0)} ${u[i]}`;
    };

    /* ---------------- QR + نسخ + تأكيد + تبديل المظهر ---------------- */

    function syncThemeIcon(theme) {
        const btn = document.getElementById('themeToggle');
        if (!btn) return;
        const isDark = theme === 'dark';
        btn.innerHTML = isDark ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon-stars"></i>';
        btn.title = isDark ? tr('themeDarkTip', 'التبديل إلى الوضع الفاتح') : tr('themeLightTip', 'التبديل إلى الوضع الداكن');
    }

    syncThemeIcon(document.documentElement.getAttribute('data-bs-theme') || 'dark');

    document.getElementById('themeToggle')?.addEventListener('click', () => {
        const cur = document.documentElement.getAttribute('data-bs-theme') === 'light' ? 'light' : 'dark';
        const next = cur === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-bs-theme', next);
        localStorage.setItem('ju_theme', next);
        syncThemeIcon(next);
    });

    function makeQrSvg(text, cell = 4) {
        if (typeof qrcode !== 'function') return '';
        const qr = qrcode(0, 'M');
        qr.addData(text);
        qr.make();
        return qr.createSvgTag({ cellSize: cell, margin: 2, scalable: true });
    }

    function renderAllQr(root = document) {
        root.querySelectorAll('[data-qr]').forEach((el) => {
            const url = el.getAttribute('data-qr');
            if (url) el.innerHTML = makeQrSvg(url);
        });
    }

    function downloadQrPng(url) {
        if (typeof qrcode !== 'function') return;
        const qr = qrcode(0, 'M');
        qr.addData(url);
        qr.make();
        const count = qr.getModuleCount();
        const scale = 8, margin = 4;
        const size = (count + margin * 2) * scale;
        const canvas = document.createElement('canvas');
        canvas.width = canvas.height = size;
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#0f172a';
        for (let r = 0; r < count; r++) {
            for (let c = 0; c < count; c++) {
                if (qr.isDark(r, c)) {
                    ctx.fillRect((c + margin) * scale, (r + margin) * scale, scale, scale);
                }
            }
        }
        const a = document.createElement('a');
        a.href = canvas.toDataURL('image/png');
        a.download = 'qr-code.png';
        a.click();
    }

    document.addEventListener('click', async (e) => {
        const copyBtn = e.target.closest('[data-copy]');
        if (copyBtn) {
            const text = copyBtn.getAttribute('data-copy');
            try {
                await navigator.clipboard.writeText(text);
            } catch {
                const inp = document.createElement('input');
                inp.value = text;
                document.body.appendChild(inp);
                inp.select();
                document.execCommand('copy');
                inp.remove();
            }
            const orig = copyBtn.innerHTML;
            copyBtn.innerHTML = `<i class="bi bi-check2"></i> ${esc(tr('copied', 'نُسخ'))}`;
            setTimeout(() => { copyBtn.innerHTML = orig; }, 1500);
        }

        const qrDl = e.target.closest('[data-qr-download]');
        if (qrDl) downloadQrPng(qrDl.getAttribute('data-qr-download'));

        const timeBtn = e.target.closest('[data-media-time]');
        if (timeBtn) {
            const media = document.querySelector('#preview video, #preview audio');
            const input = document.getElementById(timeBtn.getAttribute('data-media-time'));
            if (media && input) input.value = media.currentTime.toFixed(1);
        }
    });

    document.addEventListener('submit', (e) => {
        const msg = e.target.getAttribute('data-confirm');
        if (msg && !window.confirm(msg)) e.preventDefault();
    });

    document.addEventListener('input', (e) => {
        if (e.target.matches('input[type="range"]')) {
            const out = document.querySelector(`[data-range-out="${e.target.id}"], output[data-for="${e.target.id}"]`);
            if (out) out.textContent = e.target.value + (e.target.dataset.suffix || '');
        }
    });

    document.addEventListener('change', (e) => {
        const sel = e.target.closest('[data-toggle-target]');
        if (sel) {
            const box = document.querySelector(sel.getAttribute('data-toggle-target'));
            if (box) box.classList.toggle('d-none', sel.value !== sel.getAttribute('data-toggle-value'));
        }
    });

    // منطقة السحب البسيطة في صفحة المشاركة
    document.querySelectorAll('[data-simple-dropzone]').forEach((zone) => {
        const input = zone.querySelector('input[type="file"]');
        const label = zone.querySelector('[data-file-label]');
        const update = () => {
            if (input.files?.length) {
                const f = input.files[0];
                label.textContent = `${f.name} (${fmtBytes(f.size)})`;
            }
        };
        input.addEventListener('change', update);
        ['dragenter', 'dragover'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.add('dragover'); }));
        ['dragleave', 'drop'].forEach((ev) => zone.addEventListener(ev, (e) => { e.preventDefault(); zone.classList.remove('dragover'); }));
        zone.addEventListener('drop', (e) => {
            if (e.dataTransfer?.files?.length) { input.files = e.dataTransfer.files; update(); }
        });
    });

    const fmtEta = (sec) => {
        if (!Number.isFinite(sec) || sec <= 0) return '';
        const s = Math.ceil(sec);
        const m = Math.floor(s / 60);
        const rem = s % 60;
        const unitS = tr('secShort', 'ث');
        const unitM = tr('minShort', 'د');
        const timeStr = m > 0 ? `${m}${unitM} ${rem}${unitS}` : `${s}${unitS}`;
        return tr('etaPrefix', 'باقي ~ :time', { time: timeStr });
    };

    const renderProgress = (box, { pct = 0, label = '', detail = '', etaSec = null, animated = false }) => {
        const p = Math.max(0, Math.min(100, Math.round(pct)));
        const eta = fmtEta(etaSec);
        const rightText = [detail, eta, `${p}%`].filter(Boolean).join(' · ');
        box.classList.remove('d-none');
        box.innerHTML = `
            <div class="ju-progress-box">
                <div class="d-flex justify-content-between align-items-center small mb-1 gap-2">
                    <span class="fw-medium text-truncate">${esc(label)}</span>
                    <span class="text-secondary text-nowrap" dir="auto">${esc(rightText)}</span>
                </div>
                <div class="ju-progress-track">
                    <div class="ju-progress-fill ${animated ? 'indeterminate' : ''}" style="width:${animated && p === 0 ? 100 : p}%"></div>
                </div>
            </div>`;
    };

    const xhrPost = (url, formData, { responseType = 'json', onProgress } = {}) => new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        const startedAt = Date.now();
        xhr.open('POST', url, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrf());
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.responseType = responseType;

        xhr.upload.onprogress = (ev) => {
            if (!ev.lengthComputable || !onProgress) return;
            const pct = Math.round((ev.loaded / ev.total) * 100);
            const elapsed = (Date.now() - startedAt) / 1000;
            const speed = elapsed > 0.25 ? ev.loaded / elapsed : 0;
            const etaSec = speed > 0 && ev.loaded < ev.total ? (ev.total - ev.loaded) / speed : null;
            onProgress({ pct, loaded: ev.loaded, total: ev.total, etaSec });
        };

        xhr.upload.onload = () => {
            if (onProgress) onProgress({ pct: 100, loaded: 1, total: 1, etaSec: null, finishedUpload: true });
        };

        xhr.onload = async () => {
            const ct = xhr.getResponseHeader('Content-Type') || '';
            if (xhr.status >= 200 && xhr.status < 300) {
                resolve({ status: xhr.status, ct, response: xhr.response, xhr });
                return;
            }
            let msg = tr('processErr', 'تعذّر تنفيذ العملية.');
            let fieldErrors = null;
            try {
                const raw = responseType === 'blob' && xhr.response instanceof Blob
                    ? await xhr.response.text()
                    : (typeof xhr.response === 'string' ? xhr.response : JSON.stringify(xhr.response));
                const j = JSON.parse(raw);
                if (j.errors && typeof j.errors === 'object') fieldErrors = j.errors;
                msg = j.error || j.message || (fieldErrors ? Object.values(fieldErrors).flat().join('\n') : msg);
            } catch {}
            const err = new Error(msg);
            if (fieldErrors) err.fieldErrors = fieldErrors;
            reject(err);
        };

        xhr.onerror = () => reject(new Error(tr('genericErr', 'حدث خطأ أثناء المعالجة.')));
        xhr.send(formData);
    });

    renderAllQr();

    // شريط التقدم لصفحة المشاركة المباشرة (/share)
    const shareForm = document.getElementById('shareForm');
    if (shareForm) {
        shareForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const fileInp = document.getElementById('shareFile');
            const alertBox = document.getElementById('shareAlert');
            const progBox = document.getElementById('shareProgress');
            const submitBtn = document.getElementById('shareSubmitBtn');
            const maxBytes = Number(shareForm.dataset.maxBytes || 0);

            const showErr = (msg) => {
                alertBox.className = 'alert alert-danger py-2 small mb-3';
                alertBox.innerHTML = esc(msg).replace(/\n/g, '<br>');
            };

            alertBox.className = 'd-none';
            const f = fileInp?.files?.[0];
            if (!f) {
                showErr(tr('chooseFileShare', 'اختر ملفاً للمشاركة أولاً.'));
                return;
            }
            if (maxBytes > 0 && f.size > maxBytes) {
                showErr(tr('tooBigClient', 'حجم الملف (:size) يتجاوز الحد المسموح (:max).', { size: fmtBytes(f.size), max: fmtBytes(maxBytes) }));
                return;
            }

            const origBtn = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${esc(tr('uploading', 'جاري الرفع...'))}`;

            try {
                const fd = new FormData(shareForm);
                fd.append('_token', csrf());
                fd.append('ajax', '1');
                const { response } = await xhrPost(shareForm.action, fd, {
                    responseType: 'json',
                    onProgress: ({ pct, loaded, total, etaSec, finishedUpload }) => {
                        renderProgress(progBox, finishedUpload
                            ? { pct: 100, label: tr('savingShare', 'تم الرفع، جاري الحفظ وإنشاء الرابط...'), animated: true }
                            : { pct, label: tr('uploading', 'جاري الرفع...'), detail: `${fmtBytes(loaded)} / ${fmtBytes(total)}`, etaSec });
                    },
                });
                if (!response?.show_url && !response?.url) throw new Error(tr('linkErr', 'تعذّر إنشاء الرابط.'));
                window.location.href = response.show_url || response.url;
            } catch (err) {
                progBox.classList.add('d-none');
                showErr(err.message || tr('linkErr', 'تعذّر إنشاء الرابط.'));
                submitBtn.disabled = false;
                submitBtn.innerHTML = origBtn;
            }
        });
    }

    /* ---------------- صفحات الأدوات ---------------- */

    const page = window.JU_PAGE;
    if (!page) return;

    const chips       = [...document.querySelectorAll('.tool-chip')];
    const forms       = [...document.querySelectorAll('.tool-form')];
    const dropzone    = document.getElementById('dropzone');
    const fileInput   = document.getElementById('fileInput');
    const acceptHint  = document.getElementById('acceptHint');
    const preview     = document.getElementById('preview');
    const toolTitle   = document.getElementById('toolTitle');
    const toolDesc    = document.getElementById('toolDesc');
    const statusBox   = document.getElementById('status');
    const engineNote  = document.getElementById('engineNote');
    const resultSec   = document.getElementById('result');

    let activeTool = null;
    let selectedFiles = [];
    let objectUrls = [];
    let resultBlobUrl = null;
    let cropAspectRatio = null;
    let onCropRatioChange = null;
    let resizeOrigW = 0;
    let resizeOrigH = 0;
    let syncFilterPreview = null;
    let syncTextOverlay = null;

    function clearFieldErrors(form) {
        if (!form) return;
        form.querySelectorAll('.is-invalid').forEach((el) => el.classList.remove('is-invalid'));
        form.querySelectorAll('[data-field-error]').forEach((el) => { el.textContent = ''; });
    }

    function showFieldErrors(form, fieldErrors) {
        if (!form || !fieldErrors) return;
        clearFieldErrors(form);
        Object.entries(fieldErrors).forEach(([field, msg]) => {
            const text = Array.isArray(msg) ? msg.join(' ') : String(msg);
            const inp = form.elements[field];
            if (inp && typeof inp.classList !== 'undefined') inp.classList.add('is-invalid');
            const errBox = form.querySelector(`[data-field-error="${CSS.escape(field)}"]`);
            if (errBox) errBox.textContent = text;
        });
    }

    function applyFilterCss(img) {
        const fForm = document.getElementById('form-filter');
        if (!fForm || !img) return;
        const effect = fForm.elements.effect?.value || 'grayscale';
        const rawPct = Number(fForm.elements.intensity?.value ?? 100);
        const pct = Math.max(0, Math.min(100, Number.isFinite(rawPct) ? rawPct : 100)) / 100;
        const map = {
            grayscale: `grayscale(${(pct * 100).toFixed(0)}%)`,
            sepia:     `sepia(${(pct * 100).toFixed(0)}%)`,
            invert:    `invert(${(pct * 100).toFixed(0)}%)`,
            blur:      `blur(${(pct * 5).toFixed(2)}px)`,
            sharpen:   `contrast(${(100 + pct * 35).toFixed(0)}%)`,
            brighten:  `brightness(${(100 + pct * 60).toFixed(0)}%)`,
        };
        img.style.filter = map[effect] || '';
    }

    function initTextOverlay(stage, img) {
        const tForm = document.getElementById('form-text');
        if (!tForm) return;
        const box = document.createElement('div');
        box.className = 'text-overlay-box';
        stage.appendChild(box);

        const xInp = tForm.elements.x;
        const yInp = tForm.elements.y;
        const readout = document.getElementById('textPosReadout');
        const natW = img.naturalWidth || 1;
        const natH = img.naturalHeight || 1;

        if (!xInp.value || Number(xInp.value) >= natW) xInp.value = Math.round(natW * 0.08);
        if (!yInp.value || Number(yInp.value) >= natH) yInp.value = Math.round(natH * 0.12);

        const update = () => {
            const scale = (img.clientWidth || natW) / natW;
            const textVal = (tForm.elements.text?.value || '').trim();
            const fontKey = tForm.elements.font?.value || 'ibmplex';
            const fontSize = Math.max(8, Math.min(300, Number(tForm.elements.font_size?.value || 42)));
            const colorVal = tForm.elements.color?.value || '#ffffff';
            let x = Math.max(0, Math.min(natW - 1, Math.round(Number(xInp.value || 0))));
            let y = Math.max(0, Math.min(natH - 1, Math.round(Number(yInp.value || 0))));
            xInp.value = x;
            yInp.value = y;

            box.textContent = textVal || tr('textPlaceholder', 'اكتب النص للمعاينة...');
            box.style.opacity = textVal ? '1' : '0.55';
            box.style.fontFamily = `'ju-${fontKey}', sans-serif`;
            box.style.fontSize = `${Math.max(10, fontSize * scale)}px`;
            box.style.color = colorVal;
            box.style.left = `${(x * scale).toFixed(1)}px`;
            box.style.top = `${(y * scale).toFixed(1)}px`;

            const isAr = /[\u0600-\u06FF]/.test(textVal);
            box.dir = isAr ? 'rtl' : 'ltr';
            box.style.textAlign = isAr ? 'right' : 'left';
            if (readout) readout.textContent = `X: ${x} · Y: ${y}`;
        };

        let drag = null;
        box.addEventListener('pointerdown', (e) => {
            e.preventDefault();
            box.setPointerCapture(e.pointerId);
            drag = {
                startX: e.clientX,
                startY: e.clientY,
                origX: Number(xInp.value || 0),
                origY: Number(yInp.value || 0),
            };
        });
        box.addEventListener('pointermove', (e) => {
            if (!drag) return;
            const scale = (img.clientWidth || natW) / natW;
            const dx = Math.round((e.clientX - drag.startX) / (scale || 1));
            const dy = Math.round((e.clientY - drag.startY) / (scale || 1));
            xInp.value = Math.max(0, Math.min(natW - 1, drag.origX + dx));
            yInp.value = Math.max(0, Math.min(natH - 1, drag.origY + dy));
            update();
        });
        box.addEventListener('pointerup', () => { drag = null; });
        box.addEventListener('pointercancel', () => { drag = null; });

        syncTextOverlay = update;
        update();
    }

    (function initLiveControls() {
        const rForm = document.getElementById('form-resize');
        if (rForm) {
            const wInp = rForm.elements.width;
            const hInp = rForm.elements.height;
            const keepRatio = rForm.elements.keep_ratio;
            const resetBtn = document.getElementById('resizeResetBtn');

            wInp?.addEventListener('input', () => {
                rForm.dataset.changedAxis = 'width';
                clearFieldErrors(rForm);
                const w = Number(wInp.value);
                if (keepRatio?.checked && resizeOrigW > 0 && resizeOrigH > 0 && Number.isFinite(w) && w > 0) {
                    hInp.value = Math.max(1, Math.round(w * (resizeOrigH / resizeOrigW)));
                }
            });
            hInp?.addEventListener('input', () => {
                rForm.dataset.changedAxis = 'height';
                clearFieldErrors(rForm);
                const h = Number(hInp.value);
                if (keepRatio?.checked && resizeOrigW > 0 && resizeOrigH > 0 && Number.isFinite(h) && h > 0) {
                    wInp.value = Math.max(1, Math.round(h * (resizeOrigW / resizeOrigH)));
                }
            });
            keepRatio?.addEventListener('change', () => {
                if (!keepRatio.checked || resizeOrigW <= 0 || resizeOrigH <= 0) return;
                const axis = rForm.dataset.changedAxis || 'width';
                if (axis === 'height') {
                    const h = Number(hInp.value);
                    if (Number.isFinite(h) && h > 0) wInp.value = Math.max(1, Math.round(h * (resizeOrigW / resizeOrigH)));
                } else {
                    const w = Number(wInp.value);
                    if (Number.isFinite(w) && w > 0) hInp.value = Math.max(1, Math.round(w * (resizeOrigH / resizeOrigW)));
                }
            });
            resetBtn?.addEventListener('click', () => {
                clearFieldErrors(rForm);
                if (resizeOrigW > 0 && resizeOrigH > 0) {
                    wInp.value = resizeOrigW;
                    hInp.value = resizeOrigH;
                    delete rForm.dataset.changedAxis;
                }
            });
        }

        const fForm = document.getElementById('form-filter');
        if (fForm) {
            fForm.elements.effect?.addEventListener('change', () => syncFilterPreview?.());
            fForm.elements.intensity?.addEventListener('input', () => syncFilterPreview?.());
        }

        const tForm = document.getElementById('form-text');
        if (tForm) {
            ['text', 'font', 'font_size', 'color', 'x', 'y'].forEach((name) => {
                tForm.elements[name]?.addEventListener('input', () => syncTextOverlay?.());
                tForm.elements[name]?.addEventListener('change', () => syncTextOverlay?.());
            });
        }
    })();

    document.querySelectorAll('[data-crop-ratio]').forEach((btn) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('[data-crop-ratio]').forEach((b) => b.classList.remove('active'));
            btn.classList.add('active');
            const val = btn.dataset.cropRatio;
            if (val === 'free') {
                cropAspectRatio = null;
            } else {
                const [rw, rh] = val.split(':').map(Number);
                cropAspectRatio = rw / rh;
            }
            if (typeof onCropRatioChange === 'function') onCropRatioChange(cropAspectRatio);
        });
    });

    function clearUrls() {
        objectUrls.forEach((u) => URL.revokeObjectURL(u));
        objectUrls = [];
    }

    function setStatus(type, html) {
        if (!html) { statusBox.innerHTML = ''; return; }
        statusBox.innerHTML = `<div class="alert alert-${type} py-2 small mb-0">${html}</div>`;
    }

    function selectTool(key) {
        if (!page.tools[key]) key = Object.keys(page.tools)[0];
        activeTool = key;
        const t = page.tools[key];

        chips.forEach((c) => c.classList.toggle('active', c.dataset.tool === key));
        forms.forEach((f) => {
            clearFieldErrors(f);
            f.classList.toggle('d-none', f.dataset.tool !== key);
        });

        toolTitle.textContent = t.title;
        toolDesc.textContent = t.desc;

        fileInput.multiple = !!t.multi;
        fileInput.accept = t.accept.length ? t.accept.map((x) => '.' + x).join(',') : '';
        acceptHint.textContent = (t.accept.length ? tr('supported', 'الصيغ المدعومة: :ext', { ext: t.accept.join(', ').toUpperCase() }) : tr('allSupported', 'جميع الصيغ مدعومة'))
            + (t.multi ? tr('multiHint', ' · يمكنك اختيار عدة ملفات') : '');

        engineNote.innerHTML = t.engine === 'server'
            ? `<i class="bi bi-hdd-network"></i> ${esc(tr('serverNote', 'تُعالج على الخادم — الحد الأقصى :size', { size: fmtBytes(page.maxUpload) }))}`
            : `<i class="bi bi-shield-check"></i> ${esc(tr('browserNote', 'تُعالج مباشرة داخل متصفحك دون رفع الملف للخادم'))}`;

        if (!t.multi && selectedFiles.length > 1) selectedFiles = [selectedFiles[0]];
        setStatus('', '');
        renderPreview();
    }

    function handleFiles(fileList) {
        const t = page.tools[activeTool];
        const incoming = [...fileList];
        if (!incoming.length) return;

        const invalid = t.accept.length
            ? incoming.filter((f) => !t.accept.includes((f.name.split('.').pop() || '').toLowerCase()))
            : [];
        if (invalid.length) {
            setStatus('danger', esc(tr('unsupportedExt', 'صيغة غير مدعومة للملف «:name». الصيغ المسموحة: :ext', { name: invalid[0].name, ext: t.accept.join(', ') })));
            fileInput.value = '';
            return;
        }
        if (t.multi) {
            const combined = [...selectedFiles, ...incoming];
            if (combined.length > 20) {
                selectedFiles = combined.slice(0, 20);
                setStatus('warning', esc(tr('maxFilesLimit', 'الحد الأقصى 20 ملفاً في المرة الواحدة.')));
            } else {
                selectedFiles = combined;
                setStatus('', '');
            }
        } else {
            selectedFiles = [incoming[0]];
            setStatus('', '');
        }
        fileInput.value = '';
        resultSec.classList.add('d-none');
        renderPreview();
    }

    function moveFile(fromIdx, toIdx) {
        if (toIdx < 0 || toIdx >= selectedFiles.length || fromIdx === toIdx) return;
        const [item] = selectedFiles.splice(fromIdx, 1);
        selectedFiles.splice(toIdx, 0, item);
        renderPreview();
    }

    function renderPreview() {
        clearUrls();
        preview.innerHTML = '';
        onCropRatioChange = null;
        syncFilterPreview = null;
        syncTextOverlay = null;

        const hasFiles = selectedFiles.length > 0;
        forms.forEach((f) => {
            const btn = f.querySelector('button[type="submit"]');
            if (btn) btn.disabled = !hasFiles;
        });
        const runHint = document.getElementById('runHint');
        const clearBtn = document.getElementById('clearFilesBtn');
        if (runHint) runHint.classList.toggle('d-none', hasFiles);
        if (clearBtn) clearBtn.classList.toggle('d-none', !hasFiles);
        preview.classList.toggle('d-none', !hasFiles);
        if (!hasFiles) {
            resizeOrigW = 0;
            resizeOrigH = 0;
            return;
        }

        const t = page.tools[activeTool];
        const f = selectedFiles[0];

        if (selectedFiles.length === 1 && !t.multi && f.type.startsWith('image/')) {
            const url = URL.createObjectURL(f);
            objectUrls.push(url);
            const wrap = document.createElement('div');
            wrap.className = 'text-center';
            const stage = document.createElement('div');
            stage.className = t.cropUi ? 'crop-stage' : (t.textUi ? 'text-stage' : 'd-inline-block');
            const img = document.createElement('img');
            img.src = url;
            img.alt = f.name;
            if (!t.cropUi && !t.textUi) img.className = 'preview-media';
            stage.appendChild(img);
            wrap.appendChild(stage);
            const meta = document.createElement('div');
            meta.className = 'small text-secondary mt-2 text-center';
            img.onload = () => {
                meta.textContent = `${f.name} · ${img.naturalWidth}×${img.naturalHeight}px · ${fmtBytes(f.size)}`;
                resizeOrigW = img.naturalWidth;
                resizeOrigH = img.naturalHeight;
                const rForm = document.getElementById('form-resize');
                if (rForm) {
                    const keepExisting = rForm.elements.keep_existing?.checked
                        && Number(rForm.elements.width?.value) > 0
                        && Number(rForm.elements.height?.value) > 0;
                    if (!keepExisting) {
                        rForm.elements.width.value = img.naturalWidth;
                        rForm.elements.height.value = img.naturalHeight;
                        delete rForm.dataset.changedAxis;
                    }
                }
                if (t.cropUi) initCropBox(stage, img);
                if (t.filterUi) {
                    syncFilterPreview = () => applyFilterCss(img);
                    applyFilterCss(img);
                }
                if (t.textUi) initTextOverlay(stage, img);
            };
            preview.append(wrap, meta);
            return;
        }

        if (selectedFiles.length === 1 && (f.type.startsWith('video/') || f.type.startsWith('audio/'))) {
            const url = URL.createObjectURL(f);
            objectUrls.push(url);
            const isVideo = f.type.startsWith('video/');
            const el = document.createElement(isVideo ? 'video' : 'audio');
            el.src = url;
            el.controls = !(isVideo && t.cropUi);
            el.className = isVideo ? (t.cropUi ? '' : 'preview-media') : 'w-100';

            if (isVideo && t.cropUi) {
                const wrap = document.createElement('div');
                wrap.className = 'text-center';
                const stage = document.createElement('div');
                stage.className = 'crop-stage';
                stage.appendChild(el);

                const playBtn = document.createElement('button');
                playBtn.type = 'button';
                playBtn.className = 'btn btn-light btn-sm mt-2';
                playBtn.innerHTML = `<i class="bi bi-play-fill"></i> ${esc(tr('playPreview', 'تشغيل / إيقاف المعاينة'))}`;
                playBtn.onclick = () => { if (el.paused) el.play(); else el.pause(); };

                const meta = document.createElement('div');
                meta.className = 'small text-secondary mt-2 text-center';
                el.onloadedmetadata = () => {
                    meta.textContent = `${f.name} · ${el.videoWidth}×${el.videoHeight}px · ${fmtBytes(f.size)}`;
                    initCropBox(stage, el);
                };
                wrap.append(stage, document.createElement('br'), playBtn, meta);
                preview.append(wrap);
                return;
            }

            el.onloadedmetadata = () => {
                const dur = el.duration;
                if (Number.isFinite(dur) && dur > 0) {
                    document.querySelectorAll(`#form-${activeTool} input[name="end"]`).forEach((inp) => {
                        inp.value = Math.max(0.1, Number(dur.toFixed(1)));
                    });
                    initTrimTimeline(activeTool, dur, el);
                }
            };
            const meta = document.createElement('div');
            meta.className = 'small text-secondary mt-2 text-center';
            meta.textContent = `${f.name} (${fmtBytes(f.size)})`;
            preview.append(el, meta);
            return;
        }

        // قائمة ملفات (مستندات / متعدد) مع إعادة الترتيب بالسحب والإفلات أو الأسهم والحذف
        let dragFromIdx = null;
        const ul = document.createElement('ul');
        ul.className = 'list-group list-group-flush small border rounded-3';
        selectedFiles.forEach((file, idx) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center gap-2' + (t.multi ? ' file-order-item' : '');
            if (t.multi) {
                li.draggable = true;
                li.addEventListener('dragstart', (e) => {
                    dragFromIdx = idx;
                    li.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });
                li.addEventListener('dragend', () => {
                    dragFromIdx = null;
                    li.classList.remove('dragging');
                    ul.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
                });
                li.addEventListener('dragover', (e) => {
                    if (dragFromIdx === null || dragFromIdx === idx) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';
                    li.classList.add('drag-over');
                });
                li.addEventListener('dragleave', () => li.classList.remove('drag-over'));
                li.addEventListener('drop', (e) => {
                    e.preventDefault();
                    li.classList.remove('drag-over');
                    if (dragFromIdx !== null && dragFromIdx !== idx) moveFile(dragFromIdx, idx);
                });
            }

            const labelWrap = document.createElement('span');
            labelWrap.className = 'text-truncate d-flex align-items-center gap-2 min-w-0';
            const badgeHtml = t.multi ? `<span class="badge bg-secondary-subtle text-body">#${idx + 1}</span>` : '';
            const handleHtml = t.multi && selectedFiles.length > 1 ? '<i class="bi bi-grip-vertical text-secondary"></i>' : '';
            labelWrap.innerHTML = `${handleHtml}${badgeHtml}<span class="text-truncate">${esc(file.name)}</span> <span class="text-secondary flex-shrink-0">(${fmtBytes(file.size)})</span>`;

            const actions = document.createElement('div');
            actions.className = 'd-flex align-items-center gap-1 flex-shrink-0';
            if (t.multi && selectedFiles.length > 1) {
                const up = document.createElement('button');
                up.type = 'button';
                up.className = 'btn btn-sm btn-light py-0 px-1';
                up.title = tr('moveUp', 'تحريك للأعلى');
                up.disabled = idx === 0;
                up.innerHTML = '<i class="bi bi-arrow-up"></i>';
                up.onclick = () => moveFile(idx, idx - 1);

                const down = document.createElement('button');
                down.type = 'button';
                down.className = 'btn btn-sm btn-light py-0 px-1';
                down.title = tr('moveDown', 'تحريك للأسفل');
                down.disabled = idx === selectedFiles.length - 1;
                down.innerHTML = '<i class="bi bi-arrow-down"></i>';
                down.onclick = () => moveFile(idx, idx + 1);

                actions.append(up, down);
            }

            const rm = document.createElement('button');
            rm.type = 'button';
            rm.className = 'btn btn-sm btn-link text-danger p-0 ms-1';
            rm.title = tr('removeFile', 'إزالة الملف');
            rm.innerHTML = '<i class="bi bi-x-lg"></i>';
            rm.onclick = () => { selectedFiles.splice(idx, 1); renderPreview(); };
            actions.appendChild(rm);

            li.append(labelWrap, actions);
            ul.appendChild(li);
        });
        preview.appendChild(ul);
    }

    function fmtSec(s) {
        s = Math.max(0, Number(s) || 0);
        const m = Math.floor(s / 60);
        const rem = (s % 60).toFixed(1).padStart(4, '0');
        return `${String(m).padStart(2, '0')}:${rem}`;
    }

    function initTrimTimeline(toolKey, dur, mediaEl) {
        const track = document.querySelector(`[data-trim-track="${toolKey}"]`);
        const form = document.getElementById(`form-${toolKey}`);
        if (!track || !form) return;

        const rangeBar = track.querySelector('.trim-range');
        const hStart = track.querySelector('[data-trim-edge="start"]');
        const hEnd = track.querySelector('[data-trim-edge="end"]');
        const readout = document.getElementById(`trimReadout-${toolKey}`);
        const durBadge = document.getElementById(`trimDur-${toolKey}`);
        const prevBtn = document.querySelector(`[data-trim-preview="${toolKey}"]`);

        let start = 0;
        let end = Number(dur.toFixed(1));
        const minGap = Math.min(0.5, dur * 0.1);

        const sync = (seekTo = null) => {
            start = Math.max(0, Math.min(end - minGap, start));
            end = Math.min(dur, Math.max(start + minGap, end));
            const p1 = (start / dur) * 100;
            const p2 = (end / dur) * 100;

            hStart.style.left = `${p1}%`;
            hEnd.style.left = `${p2}%`;
            rangeBar.style.left = `${p1}%`;
            rangeBar.style.width = `${p2 - p1}%`;

            form.elements.start.value = start.toFixed(1);
            form.elements.end.value = end.toFixed(1);
            if (readout) readout.textContent = `${fmtSec(start)} → ${fmtSec(end)}`;
            if (durBadge) durBadge.textContent = tr('durationSec', 'المدة: :s ثانية', { s: (end - start).toFixed(1) });
            if (seekTo !== null && mediaEl) mediaEl.currentTime = seekTo;
        };

        sync();
        if (prevBtn) {
            prevBtn.disabled = false;
            prevBtn.onclick = () => {
                if (!mediaEl) return;
                mediaEl.currentTime = start;
                mediaEl.play();
                const stopAtEnd = () => {
                    if (mediaEl.currentTime >= end) {
                        mediaEl.pause();
                        mediaEl.removeEventListener('timeupdate', stopAtEnd);
                    }
                };
                mediaEl.addEventListener('timeupdate', stopAtEnd);
            };
        }

        let activeEdge = null;
        track.onpointerdown = (e) => {
            e.preventDefault();
            const r = track.getBoundingClientRect();
            const ratio = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width));
            const t = ratio * dur;
            const edgeEl = e.target.closest('[data-trim-edge]');
            if (edgeEl) {
                activeEdge = edgeEl.dataset.trimEdge;
            } else {
                activeEdge = Math.abs(t - start) < Math.abs(t - end) ? 'start' : 'end';
                if (activeEdge === 'start') start = t; else end = t;
                sync(t);
            }
            track.setPointerCapture(e.pointerId);
        };

        track.onpointermove = (e) => {
            if (!activeEdge) return;
            const r = track.getBoundingClientRect();
            const ratio = Math.max(0, Math.min(1, (e.clientX - r.left) / r.width));
            const t = ratio * dur;
            if (activeEdge === 'start') start = t; else end = t;
            sync(t);
        };

        track.onpointerup = () => { activeEdge = null; };
    }

    function initCropBox(stage, img) {
        const form = document.getElementById('form-crop');
        if (!form) return;

        const readout = document.getElementById('cropReadout');

        const box = document.createElement('div');
        box.className = 'crop-box';
        ['nw', 'n', 'ne', 'e', 'se', 's', 'sw', 'w'].forEach((dir) => {
            const h = document.createElement('span');
            h.className = 'crop-handle';
            h.dataset.dir = dir;
            box.appendChild(h);
        });
        stage.appendChild(box);

        let cur = { x: 0, y: 0, w: 0, h: 0 };

        const applyBox = (x, y, w, h) => {
            const maxW = img.clientWidth, maxH = img.clientHeight;
            w = Math.max(16, Math.min(maxW, w));
            h = Math.max(16, Math.min(maxH, h));
            x = Math.max(0, Math.min(maxW - w, x));
            y = Math.max(0, Math.min(maxH - h, y));
            cur = { x, y, w, h };

            const naturalW = img.videoWidth || img.naturalWidth;
            const naturalH = img.videoHeight || img.naturalHeight;
            const sx = naturalW / maxW;
            const sy = naturalH / maxH;
            const nx = Math.max(0, Math.round(x * sx));
            const ny = Math.max(0, Math.round(y * sy));
            const nw = Math.max(1, Math.round(w * sx));
            const nh = Math.max(1, Math.round(h * sy));

            form.elements.x.value = nx;
            form.elements.y.value = ny;
            form.elements.width.value = nw;
            form.elements.height.value = nh;
            if (readout) readout.textContent = `${nw} × ${nh} px`;

            box.style.left = `${x}px`;
            box.style.top = `${y}px`;
            box.style.width = `${w}px`;
            box.style.height = `${h}px`;
        };

        const fitAspect = (ratio) => {
            const maxW = img.clientWidth, maxH = img.clientHeight;
            let w = maxW * 0.8, h = maxH * 0.8;
            if (ratio) {
                if (w / h > ratio) w = h * ratio;
                else h = w / ratio;
            }
            applyBox((maxW - w) / 2, (maxH - h) / 2, w, h);
        };

        onCropRatioChange = (ratio) => {
            if (ratio) fitAspect(ratio);
        };

        requestAnimationFrame(() => fitAspect(cropAspectRatio));

        let drag = null;
        stage.onpointerdown = (e) => {
            e.preventDefault();
            const r = img.getBoundingClientRect();
            const px = Math.max(0, Math.min(r.width, e.clientX - r.left));
            const py = Math.max(0, Math.min(r.height, e.clientY - r.top));
            const handle = e.target.closest('.crop-handle');
            if (handle) {
                drag = { mode: handle.dataset.dir, px, py, start: { ...cur } };
            } else if (e.target === box) {
                drag = { mode: 'move', px, py, start: { ...cur } };
            } else {
                drag = { mode: 'draw', px, py, start: { x: px, y: py, w: 16, h: 16 } };
            }
            stage.setPointerCapture(e.pointerId);
        };

        stage.onpointermove = (e) => {
            if (!drag) return;
            const r = img.getBoundingClientRect();
            const cx = Math.max(0, Math.min(r.width, e.clientX - r.left));
            const cy = Math.max(0, Math.min(r.height, e.clientY - r.top));
            const dx = cx - drag.px, dy = cy - drag.py;
            const s = drag.start;

            if (drag.mode === 'move') {
                applyBox(s.x + dx, s.y + dy, s.w, s.h);
                return;
            }
            if (drag.mode === 'draw') {
                let w = Math.max(16, Math.abs(dx));
                let h = cropAspectRatio ? w / cropAspectRatio : Math.max(16, Math.abs(dy));
                if (h > r.height) { h = r.height; if (cropAspectRatio) w = h * cropAspectRatio; }
                const x = dx < 0 ? Math.max(0, drag.px - w) : drag.px;
                const y = dy < 0 ? Math.max(0, drag.py - h) : drag.py;
                applyBox(x, y, w, h);
                return;
            }

            let { x, y, w, h } = s;
            if (drag.mode.includes('e')) w = Math.max(16, Math.min(r.width - x, s.w + dx));
            if (drag.mode.includes('s')) h = Math.max(16, Math.min(r.height - y, s.h + dy));
            if (drag.mode.includes('w')) {
                const nx = Math.max(0, Math.min(s.x + s.w - 16, s.x + dx));
                w = s.x + s.w - nx;
                x = nx;
            }
            if (drag.mode.includes('n')) {
                const ny = Math.max(0, Math.min(s.y + s.h - 16, s.y + dy));
                h = s.y + s.h - ny;
                y = ny;
            }
            if (cropAspectRatio) {
                if (drag.mode === 'n' || drag.mode === 's') w = h * cropAspectRatio;
                else h = w / cropAspectRatio;
            }
            applyBox(x, y, w, h);
        };

        stage.onpointerup = () => { drag = null; };
    }

    /* ---------------- تشغيل الأدوات وعرض النتيجة ---------------- */

    async function runServerTool(form) {
        const fd = new FormData(form);
        fd.append('_token', csrf());
        fd.append('category', page.category);
        fd.append('tool', activeTool);
        if (activeTool === 'resize' && form.dataset.changedAxis) {
            fd.append('changed_axis', form.dataset.changedAxis);
        }
        selectedFiles.forEach((f) => fd.append('files[]', f));

        const { ct, response, xhr } = await xhrPost('api/process.php', fd, {
            responseType: 'blob',
            onProgress: ({ pct, loaded, total, etaSec, finishedUpload }) => {
                renderProgress(statusBox, finishedUpload
                    ? { pct: 100, label: tr('processingServer', 'تم الرفع، جاري المعالجة على الخادم...'), animated: true }
                    : { pct, label: tr('uploading', 'جاري الرفع...'), detail: `${fmtBytes(loaded)} / ${fmtBytes(total)}`, etaSec });
            },
        });

        if (ct.includes('application/json')) {
            const data = JSON.parse(await response.text());
            if (data.clientSide) {
                const out = page.category === 'image'
                    ? await runClientImageTool(activeTool, selectedFiles[0], new FormData(form))
                    : await runClientZipTool(selectedFiles, new FormData(form));
                renderFileResult(out.blob, out.name, out.info);
                return;
            }
            if (data.stats) renderStatsResult(data.stats);
            return;
        }
        const name = decodeURIComponent(xhr.getResponseHeader('X-File-Name') || 'output');
        const info = xhr.getResponseHeader('X-Info') ? decodeURIComponent(xhr.getResponseHeader('X-Info')) : '';
        renderFileResult(response, name, info);
    }

    async function runClientImageTool(tool, file, fd) {
        const url = URL.createObjectURL(file);
        const img = await new Promise((res, rej) => {
            const el = new Image();
            el.onload = () => res(el);
            el.onerror = () => rej(new Error('تعذّرت قراءة الصورة.'));
            el.src = url;
        }).finally(() => URL.revokeObjectURL(url));

        let w = img.naturalWidth, h = img.naturalHeight;
        const ext = (file.name.split('.').pop() || 'png').toLowerCase();
        const base = file.name.replace(/\.[^.]+$/, '');
        let outExt = ext === 'jpeg' ? 'jpg' : ext;
        let quality = 0.9, suffix = tool;

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        if (tool === 'compress') {
            quality = Math.max(0.1, Number(fd.get('quality') || 70) / 100);
            const fmt = fd.get('format') || 'keep';
            outExt = fmt === 'keep' ? (['gif', 'bmp'].includes(outExt) ? 'jpg' : outExt) : fmt;
            const maxW = Number(fd.get('max_width') || 0);
            if (maxW > 0 && w > maxW) { h = Math.round(h * maxW / w); w = maxW; }
            canvas.width = w; canvas.height = h;
            ctx.drawImage(img, 0, 0, w, h);
        } else if (tool === 'resize') {
            let nw = Number(fd.get('width') || 0), nh = Number(fd.get('height') || 0);
            if (!nw && !nh) throw new Error('أدخل العرض أو الارتفاع على الأقل.');
            if (fd.get('keep_ratio') === '1' || !nw || !nh) {
                const r = nw && nh ? Math.min(nw / w, nh / h) : (nw ? nw / w : nh / h);
                nw = Math.max(1, Math.round(w * r)); nh = Math.max(1, Math.round(h * r));
            }
            w = canvas.width = nw; h = canvas.height = nh;
            ctx.drawImage(img, 0, 0, w, h);
            suffix = `${w}x${h}`;
        } else if (tool === 'crop') {
            const x = Math.max(0, Number(fd.get('x') || 0)), y = Math.max(0, Number(fd.get('y') || 0));
            const cw = Math.min(Number(fd.get('width') || w), w - x);
            const ch = Math.min(Number(fd.get('height') || h), h - y);
            if (cw <= 0 || ch <= 0) throw new Error('منطقة القص خارج حدود الصورة.');
            w = canvas.width = cw; h = canvas.height = ch;
            ctx.drawImage(img, x, y, cw, ch, 0, 0, cw, ch);
        } else if (tool === 'rotate') {
            const angle = Number(fd.get('angle') || 0);
            const flip = fd.get('flip') || 'none';
            const swap = angle === 90 || angle === 270;
            canvas.width = swap ? h : w; canvas.height = swap ? w : h;
            ctx.translate(canvas.width / 2, canvas.height / 2);
            if (angle) ctx.rotate((angle * Math.PI) / 180);
            ctx.scale(flip === 'h' ? -1 : 1, flip === 'v' ? -1 : 1);
            ctx.drawImage(img, -w / 2, -h / 2);
            w = canvas.width; h = canvas.height;
        } else if (tool === 'convert') {
            outExt = fd.get('format') || 'png';
            canvas.width = w; canvas.height = h;
            ctx.drawImage(img, 0, 0);
            suffix = '';
        } else if (tool === 'filter') {
            const eff = fd.get('effect') || 'grayscale';
            const map = { grayscale: 'grayscale(100%)', sepia: 'sepia(100%)', negate: 'invert(100%)', blur: 'blur(4px)', sharpen: 'contrast(145%)', brighten: 'brightness(135%)' };
            canvas.width = w; canvas.height = h;
            ctx.filter = map[eff] || 'none';
            ctx.drawImage(img, 0, 0);
            suffix = eff;
        }

        const mimes = { jpg: 'image/jpeg', png: 'image/png', webp: 'image/webp', gif: 'image/png', bmp: 'image/bmp' };
        const mime = mimes[outExt] || 'image/png';
        const blob = await new Promise((r) => canvas.toBlob(r, mime, quality));
        return { blob, name: `${base}${suffix ? '-' + suffix : ''}.${outExt}`, info: `${w}×${h} بكسل` };
    }

    async function runClientZipTool(files, fd) {
        const crcTable = new Uint32Array(256).map((_, n) => {
            let c = n;
            for (let k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
            return c >>> 0;
        });
        const crc32 = (u8) => {
            let c = 0xFFFFFFFF;
            for (let i = 0; i < u8.length; i++) c = crcTable[(c ^ u8[i]) & 0xFF] ^ (c >>> 8);
            return (c ^ 0xFFFFFFFF) >>> 0;
        };
        const enc = new TextEncoder();
        const parts = [], central = [];
        let offset = 0;

        for (const f of files) {
            const nameBytes = enc.encode(f.name);
            const data = new Uint8Array(await f.arrayBuffer());
            const crc = crc32(data);
            const local = new DataView(new ArrayBuffer(30 + nameBytes.length));
            local.setUint32(0, 0x04034b50, true);
            local.setUint16(4, 20, true);
            local.setUint16(6, 0x0800, true); // UTF-8
            local.setUint32(14, crc, true);
            local.setUint32(18, data.length, true);
            local.setUint32(22, data.length, true);
            local.setUint16(26, nameBytes.length, true);
            new Uint8Array(local.buffer, 30).set(nameBytes);
            parts.push(new Uint8Array(local.buffer), data);

            const cd = new DataView(new ArrayBuffer(46 + nameBytes.length));
            cd.setUint32(0, 0x02014b50, true);
            cd.setUint16(4, 20, true);
            cd.setUint16(6, 20, true);
            cd.setUint16(8, 0x0800, true);
            cd.setUint32(16, crc, true);
            cd.setUint32(20, data.length, true);
            cd.setUint32(24, data.length, true);
            cd.setUint16(28, nameBytes.length, true);
            cd.setUint32(42, offset, true);
            new Uint8Array(cd.buffer, 46).set(nameBytes);
            central.push(new Uint8Array(cd.buffer));
            offset += 30 + nameBytes.length + data.length;
        }

        const cdSize = central.reduce((s, b) => s + b.length, 0);
        const eocd = new DataView(new ArrayBuffer(22));
        eocd.setUint32(0, 0x06054b50, true);
        eocd.setUint16(8, files.length, true);
        eocd.setUint16(10, files.length, true);
        eocd.setUint32(12, cdSize, true);
        eocd.setUint32(16, offset, true);

        const archive = (fd.get('archive') || 'files').trim().replace(/\.zip$/i, '') || 'files';
        return {
            blob: new Blob([...parts, ...central, new Uint8Array(eocd.buffer)], { type: 'application/zip' }),
            name: `${archive}.zip`,
            info: `${files.length} ملف`,
        };
    }

    function logBrowserOp() {
        const fd = new FormData();
        fd.append('_token', csrf());
        fd.append('csrf_token', csrf());
        fd.append('category', page.category);
        fd.append('tool', activeTool);
        if (selectedFiles[0]) fd.append('file_name', selectedFiles[0].name);
        fetch('api/log.php', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf() }, body: fd }).catch(() => {});
    }

    function renderStatsResult(stats) {
        resultSec.classList.remove('d-none');
        const items = Object.entries(stats).map(([k, v]) =>
            `<div class="col-6 col-md-4"><div class="p-3 border rounded-3 bg-light h-100"><div class="small text-secondary">${esc(k)}</div><div class="fw-bold mt-1">${esc(v)}</div></div></div>`
        ).join('');
        resultSec.innerHTML = `<h3 class="h6 fw-bold mb-3"><i class="bi bi-check-circle-fill text-success"></i> ${esc(tr('statsTitle', 'إحصائيات الملف'))}</h3><div class="row g-2">${items}</div>`;
        resultSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderFileResult(blob, name, info = '') {
        if (resultBlobUrl) URL.revokeObjectURL(resultBlobUrl);
        resultBlobUrl = URL.createObjectURL(blob);

        const origSize = selectedFiles.reduce((s, f) => s + f.size, 0);
        const diff = origSize ? Math.round((1 - blob.size / origSize) * 100) : 0;
        const savingBadge = diff > 0 ? `<span class="badge text-bg-success">${esc(tr('savedPct', 'وفّرت :pct%', { pct: diff }))}</span>` : '';

        let mediaHtml = '';
        if (blob.type.startsWith('image/')) {
            mediaHtml = `<img class="preview-media mb-3" src="${resultBlobUrl}" alt="${esc(name)}">`;
        } else if (blob.type.startsWith('video/')) {
            mediaHtml = `<video class="preview-media mb-3" src="${resultBlobUrl}" controls></video>`;
        } else if (blob.type.startsWith('audio/')) {
            mediaHtml = `<audio class="w-100 mb-3" src="${resultBlobUrl}" controls></audio>`;
        }

        resultSec.classList.remove('d-none');
        resultSec.innerHTML = `
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h3 class="h6 fw-bold mb-1"><i class="bi bi-check-circle-fill text-success"></i> ${esc(tr('ready', 'جاهز: :name', { name }))}</h3>
                    <div class="small text-secondary">${fmtBytes(blob.size)} ${info ? '· ' + esc(info) : ''} ${savingBadge}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-accent" href="${resultBlobUrl}" download="${esc(name)}"><i class="bi bi-download"></i> ${esc(tr('downloadFile', 'تنزيل الملف'))}</a>
                    <button class="btn btn-outline-dark" type="button" id="shareResultBtn"><i class="bi bi-qr-code"></i> ${esc(tr('shareBtn', 'تحويل إلى رابط و QR'))}</button>
                </div>
            </div>
            ${mediaHtml}
            <div id="shareResultBox"></div>
        `;

        document.getElementById('shareResultBtn').onclick = async (ev) => {
            const btn = ev.currentTarget;
            const box = document.getElementById('shareResultBox');
            if (blob.size > page.maxUpload) {
                box.innerHTML = `<div class="alert alert-warning small mb-0">${esc(tr('tooLargeShare', 'حجم الملف الناتج (:size) أكبر من حد الرفع للمشاركة (:max).', { size: fmtBytes(blob.size), max: fmtBytes(page.maxUpload) }))}</div>`;
                return;
            }
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${esc(tr('uploading', 'جاري الرفع...'))}`;
            try {
                const fd = new FormData();
                fd.append('_token', csrf());
                fd.append('csrf_token', csrf());
                fd.append('ajax', '1');
                fd.append('expiry', '24');
                fd.append('file', new File([blob], name, { type: blob.type || 'application/octet-stream' }));
                const { response: data } = await xhrPost('share.php', fd, {
                    responseType: 'json',
                    onProgress: ({ pct, loaded, total, etaSec, finishedUpload }) => {
                        renderProgress(box, finishedUpload
                            ? { pct: 100, label: tr('savingShare', 'تم الرفع، جاري الحفظ وإنشاء الرابط...'), animated: true }
                            : { pct, label: tr('uploading', 'جاري الرفع...'), detail: `${fmtBytes(loaded)} / ${fmtBytes(total)}`, etaSec });
                    },
                });
                if (!data?.url) throw new Error(data?.error || tr('linkErr', 'تعذّر إنشاء الرابط.'));
                const u = esc(data.url);
                box.innerHTML = `
                    <div class="share-box mt-2">
                        <div class="qr" data-qr="${u}"></div>
                        <div class="flex-grow-1 w-100">
                            <label class="form-label small text-secondary">${esc(tr('directShareLbl', 'رابط المشاركة المباشر'))}</label>
                            <div class="input-group mb-2" dir="ltr">
                                <input type="text" class="form-control" value="${u}" readonly>
                                <button class="btn btn-accent" type="button" data-copy="${u}"><i class="bi bi-clipboard"></i> ${esc(tr('copy', 'نسخ'))}</button>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light btn-sm" href="${u}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-left"></i> ${esc(tr('open', 'فتح'))}</a>
                                <button class="btn btn-light btn-sm" type="button" data-qr-download="${u}"><i class="bi bi-download"></i> ${esc(tr('downloadQr', 'تنزيل QR'))}</button>
                            </div>
                        </div>
                    </div>`;
                renderAllQr(box);
                btn.remove();
            } catch (err) {
                box.innerHTML = `<div class="alert alert-danger small mb-0">${esc(err.message)}</div>`;
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-qr-code"></i> ${esc(tr('shareBtn', 'تحويل إلى رابط و QR'))}`;
            }
        };

        resultSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ربط الأحداث
    chips.forEach((c) => c.addEventListener('click', () => selectTool(c.dataset.tool)));
    fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    document.getElementById('clearFilesBtn')?.addEventListener('click', () => {
        selectedFiles = [];
        fileInput.value = '';
        resultSec.classList.add('d-none');
        setStatus('', '');
        renderPreview();
    });
    ['dragenter', 'dragover'].forEach((ev) => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach((ev) => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); }));
    dropzone.addEventListener('drop', (e) => { if (e.dataTransfer?.files?.length) handleFiles(e.dataTransfer.files); });

    forms.forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            clearFieldErrors(form);
            if (!selectedFiles.length) {
                setStatus('warning', esc(tr('readFileFirst', 'اختر ملفاً أولاً قبل التنفيذ.')));
                return;
            }
            if (activeTool === 'resize') {
                const wVal = Number(form.elements.width?.value);
                const hVal = Number(form.elements.height?.value);
                const errs = {};
                if (!Number.isFinite(wVal) || wVal <= 0) errs.width = tr('invalidDim', 'أدخل قيمة رقمية أكبر من الصفر.');
                if (!Number.isFinite(hVal) || hVal <= 0) errs.height = tr('invalidDim', 'أدخل قيمة رقمية أكبر من الصفر.');
                if (Object.keys(errs).length) {
                    showFieldErrors(form, errs);
                    setStatus('danger', esc(tr('invalidDimForm', 'أدخل أبعاداً صحيحة أكبر من الصفر.')));
                    return;
                }
            }
            const t = page.tools[activeTool];
            const btn = form.querySelector('button[type="submit"]');
            const origBtn = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> ${esc(tr('processing', 'جاري المعالجة...'))}`;
            setStatus('info', esc(tr('processing', 'جاري المعالجة...')));

            try {
                if (t.engine === 'server') {
                    await runServerTool(form);
                } else {
                    const vfd = new FormData(form);
                    vfd.append('_token', csrf());
                    vfd.append('category', page.category);
                    vfd.append('tool', activeTool);
                    if (t.engine === 'pdf' && !t.multi && window.JU_PDF?.getPageCount) {
                        const tp = await window.JU_PDF.getPageCount(selectedFiles[0]);
                        if (tp > 0) vfd.append('total_pages', String(tp));
                    }
                    await xhrPost('api/validate-tool', vfd, { responseType: 'json' });

                    if (t.engine === 'ffmpeg') {
                        const out = await window.JU_MEDIA.run(page.category, activeTool, selectedFiles[0], new FormData(form), (prog) => {
                            if (typeof prog === 'object' && prog) renderProgress(statusBox, prog);
                            else setStatus('info', esc(prog));
                        });
                        renderFileResult(out.blob, out.name, out.info);
                        logBrowserOp();
                    } else if (t.engine === 'pdf') {
                        renderProgress(statusBox, { pct: 0, label: tr('processing', 'جاري المعالجة...'), animated: true });
                        const out = await window.JU_PDF.run(activeTool, selectedFiles, new FormData(form));
                        renderFileResult(out.blob, out.name, out.info);
                        logBrowserOp();
                    }
                }
                setStatus('', '');
            } catch (err) {
                if (err.fieldErrors) showFieldErrors(form, err.fieldErrors);
                setStatus('danger', esc(err.message || tr('genericErr', 'حدث خطأ أثناء المعالجة.')).replace(/\n/g, '<br>'));
            } finally {
                btn.disabled = false;
                btn.innerHTML = origBtn;
            }
        });
    });

    const initialTool = new URLSearchParams(location.search).get('tool') || Object.keys(page.tools)[0];
    selectTool(initialTool);
})();

