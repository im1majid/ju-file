/**
 * السكربت المشترك للموقع:
 * 1) توليد رموز QR، نسخ الروابط، التأكيد قبل الحذف، ومناطق السحب البسيطة.
 * 2) إدارة صفحات الأدوات (اختيار الأداة، معاينة الملف، القص التفاعلي، التنفيذ، وتحويل الناتج لرابط/QR).
 */
(() => {
    'use strict';

    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    const fmtBytes = (b) => {
        const u = ['بايت', 'KB', 'MB', 'GB'];
        let i = 0, n = Number(b) || 0;
        while (n >= 1024 && i < u.length - 1) { n /= 1024; i++; }
        return `${n.toFixed(i ? 1 : 0)} ${u[i]}`;
    };

    /* ---------------- QR + نسخ + تأكيد ---------------- */

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
            copyBtn.innerHTML = '<i class="bi bi-check2"></i> نُسخ';
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
            const out = document.querySelector(`output[data-for="${e.target.id}"]`);
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

    renderAllQr();

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
        forms.forEach((f) => f.classList.toggle('d-none', f.dataset.tool !== key));

        toolTitle.textContent = t.title;
        toolDesc.textContent = t.desc;

        fileInput.multiple = !!t.multi;
        fileInput.accept = t.accept.length ? t.accept.map((x) => '.' + x).join(',') : '';
        acceptHint.textContent = (t.accept.length ? `الصيغ المدعومة: ${t.accept.join(', ').toUpperCase()}` : 'جميع الصيغ مدعومة')
            + (t.multi ? ' · يمكنك اختيار عدة ملفات' : '');

        engineNote.innerHTML = t.engine === 'server'
            ? `<i class="bi bi-hdd-network"></i> تُعالج على الخادم — الحد الأقصى ${fmtBytes(page.maxUpload)}`
            : '<i class="bi bi-shield-check"></i> تُعالج مباشرة داخل متصفحك دون رفع الملف للخادم';

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
            setStatus('danger', `صيغة غير مدعومة للملف «${esc(invalid[0].name)}». الصيغ المسموحة: ${esc(t.accept.join(', '))}`);
            return;
        }
        selectedFiles = t.multi ? incoming : [incoming[0]];
        setStatus('', '');
        resultSec.classList.add('d-none');
        renderPreview();
    }

    function renderPreview() {
        clearUrls();
        preview.innerHTML = '';
        if (!selectedFiles.length) return;

        const t = page.tools[activeTool];
        const f = selectedFiles[0];

        if (selectedFiles.length === 1 && f.type.startsWith('image/')) {
            const url = URL.createObjectURL(f);
            objectUrls.push(url);
            const stage = document.createElement('div');
            stage.className = t.cropUi ? 'crop-stage' : 'text-center';
            const img = document.createElement('img');
            img.src = url;
            img.alt = f.name;
            if (!t.cropUi) img.className = 'preview-media';
            stage.appendChild(img);
            const meta = document.createElement('div');
            meta.className = 'small text-secondary mt-2 text-center';
            img.onload = () => {
                meta.textContent = `${f.name} · ${img.naturalWidth}×${img.naturalHeight}px · ${fmtBytes(f.size)}`;
                const rForm = document.getElementById('form-resize');
                if (rForm && !rForm.elements.width.value && !rForm.elements.height.value) {
                    rForm.elements.width.value = img.naturalWidth;
                    rForm.elements.height.value = img.naturalHeight;
                }
                if (t.cropUi) initCropBox(stage, img);
            };
            preview.append(stage, meta);
            return;
        }

        if (selectedFiles.length === 1 && (f.type.startsWith('video/') || f.type.startsWith('audio/'))) {
            const url = URL.createObjectURL(f);
            objectUrls.push(url);
            const el = document.createElement(f.type.startsWith('video/') ? 'video' : 'audio');
            el.src = url;
            el.controls = true;
            el.className = f.type.startsWith('video/') ? 'preview-media' : 'w-100';
            el.onloadedmetadata = () => {
                const dur = el.duration;
                if (Number.isFinite(dur)) {
                    document.querySelectorAll(`#form-${activeTool} input[name="end"]`).forEach((inp) => {
                        inp.value = Math.max(0.1, Number(dur.toFixed(1)));
                    });
                }
            };
            const meta = document.createElement('div');
            meta.className = 'small text-secondary mt-2 text-center';
            meta.textContent = `${f.name} (${fmtBytes(f.size)})`;
            preview.append(el, meta);
            return;
        }

        // قائمة ملفات (مستندات / متعدد)
        const ul = document.createElement('ul');
        ul.className = 'list-group list-group-flush small border rounded-3';
        selectedFiles.forEach((file, idx) => {
            const li = document.createElement('li');
            li.className = 'list-group-item d-flex justify-content-between align-items-center';
            li.innerHTML = `<span class="text-truncate">${esc(file.name)} <span class="text-secondary">(${fmtBytes(file.size)})</span></span>`;
            if (selectedFiles.length > 1) {
                const rm = document.createElement('button');
                rm.type = 'button';
                rm.className = 'btn btn-sm btn-link text-danger p-0';
                rm.innerHTML = '<i class="bi bi-x-lg"></i>';
                rm.onclick = () => { selectedFiles.splice(idx, 1); renderPreview(); };
                li.appendChild(rm);
            }
            ul.appendChild(li);
        });
        preview.appendChild(ul);
    }

    function initCropBox(stage, img) {
        const form = document.getElementById('form-crop');
        if (!form) return;
        const box = document.createElement('div');
        box.className = 'crop-box';
        stage.appendChild(box);

        const setFields = (x, y, w, h) => {
            const sx = img.naturalWidth / img.clientWidth;
            const sy = img.naturalHeight / img.clientHeight;
            form.elements.x.value = Math.max(0, Math.round(x * sx));
            form.elements.y.value = Math.max(0, Math.round(y * sy));
            form.elements.width.value = Math.max(1, Math.round(w * sx));
            form.elements.height.value = Math.max(1, Math.round(h * sy));
            box.style.left = `${x}px`;
            box.style.top = `${y}px`;
            box.style.width = `${w}px`;
            box.style.height = `${h}px`;
        };

        // مستطيل ابتدائي في المنتصف
        requestAnimationFrame(() => {
            const w = img.clientWidth, h = img.clientHeight;
            setFields(w * 0.1, h * 0.1, w * 0.8, h * 0.8);
        });

        let start = null;
        stage.onpointerdown = (e) => {
            const r = img.getBoundingClientRect();
            start = { x: Math.max(0, Math.min(r.width, e.clientX - r.left)), y: Math.max(0, Math.min(r.height, e.clientY - r.top)) };
            stage.setPointerCapture(e.pointerId);
        };
        stage.onpointermove = (e) => {
            if (!start) return;
            const r = img.getBoundingClientRect();
            const cx = Math.max(0, Math.min(r.width, e.clientX - r.left));
            const cy = Math.max(0, Math.min(r.height, e.clientY - r.top));
            const x = Math.min(start.x, cx), y = Math.min(start.y, cy);
            const w = Math.max(4, Math.abs(cx - start.x)), h = Math.max(4, Math.abs(cy - start.y));
            setFields(x, y, w, h);
        };
        stage.onpointerup = () => { start = null; };
    }

    /* ---------------- تشغيل الأدوات وعرض النتيجة ---------------- */

    async function runServerTool(form) {
        const fd = new FormData(form);
        fd.append('category', page.category);
        fd.append('tool', activeTool);
        selectedFiles.forEach((f) => fd.append('files[]', f));

        const res = await fetch('api/process.php', { method: 'POST', body: fd });
        const ct = res.headers.get('Content-Type') || '';
        if (!res.ok) {
            const err = ct.includes('application/json') ? (await res.json()).error : 'تعذّر تنفيذ العملية.';
            throw new Error(err || 'تعذّر تنفيذ العملية.');
        }
        if (ct.includes('application/json')) {
            const data = await res.json();
            if (data.stats) renderStatsResult(data.stats);
            return;
        }
        const blob = await res.blob();
        const name = decodeURIComponent(res.headers.get('X-File-Name') || 'output');
        const info = res.headers.get('X-Info') ? decodeURIComponent(res.headers.get('X-Info')) : '';
        renderFileResult(blob, name, info);
    }

    function logBrowserOp() {
        const fd = new FormData();
        fd.append('csrf_token', csrf());
        fd.append('category', page.category);
        fd.append('tool', activeTool);
        if (selectedFiles[0]) fd.append('file_name', selectedFiles[0].name);
        fetch('api/log.php', { method: 'POST', body: fd }).catch(() => {});
    }

    function renderStatsResult(stats) {
        resultSec.classList.remove('d-none');
        const items = Object.entries(stats).map(([k, v]) =>
            `<div class="col-6 col-md-4"><div class="p-3 border rounded-3 bg-light h-100"><div class="small text-secondary">${esc(k)}</div><div class="fw-bold mt-1">${esc(v)}</div></div></div>`
        ).join('');
        resultSec.innerHTML = `<h3 class="h6 fw-bold mb-3"><i class="bi bi-check-circle-fill text-success"></i> إحصائيات الملف</h3><div class="row g-2">${items}</div>`;
        resultSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function renderFileResult(blob, name, info = '') {
        if (resultBlobUrl) URL.revokeObjectURL(resultBlobUrl);
        resultBlobUrl = URL.createObjectURL(blob);

        const origSize = selectedFiles.reduce((s, f) => s + f.size, 0);
        const diff = origSize ? Math.round((1 - blob.size / origSize) * 100) : 0;
        const savingBadge = diff > 0 ? `<span class="badge text-bg-success">وفّرت ${diff}%</span>` : '';

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
                    <h3 class="h6 fw-bold mb-1"><i class="bi bi-check-circle-fill text-success"></i> جاهز: ${esc(name)}</h3>
                    <div class="small text-secondary">${fmtBytes(blob.size)} ${info ? '· ' + esc(info) : ''} ${savingBadge}</div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-accent" href="${resultBlobUrl}" download="${esc(name)}"><i class="bi bi-download"></i> تنزيل الملف</a>
                    <button class="btn btn-outline-dark" type="button" id="shareResultBtn"><i class="bi bi-qr-code"></i> تحويل إلى رابط و QR</button>
                </div>
            </div>
            ${mediaHtml}
            <div id="shareResultBox"></div>
        `;

        document.getElementById('shareResultBtn').onclick = async (ev) => {
            const btn = ev.currentTarget;
            const box = document.getElementById('shareResultBox');
            if (blob.size > page.maxUpload) {
                box.innerHTML = `<div class="alert alert-warning small mb-0">حجم الملف الناتج (${fmtBytes(blob.size)}) أكبر من حد الرفع للمشاركة (${fmtBytes(page.maxUpload)}).</div>`;
                return;
            }
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري رفع الملف وإنشاء الرابط...';
            try {
                const fd = new FormData();
                fd.append('csrf_token', csrf());
                fd.append('ajax', '1');
                fd.append('expiry', '24');
                fd.append('file', new File([blob], name, { type: blob.type || 'application/octet-stream' }));
                const r = await fetch('share.php', { method: 'POST', body: fd });
                const data = await r.json();
                if (!r.ok || !data.url) throw new Error(data.error || 'تعذّر إنشاء الرابط.');
                const u = esc(data.url);
                box.innerHTML = `
                    <div class="share-box mt-2">
                        <div class="qr" data-qr="${u}"></div>
                        <div class="flex-grow-1 w-100">
                            <label class="form-label small text-secondary">رابط المشاركة المباشر</label>
                            <div class="input-group mb-2" dir="ltr">
                                <input type="text" class="form-control" value="${u}" readonly>
                                <button class="btn btn-accent" type="button" data-copy="${u}"><i class="bi bi-clipboard"></i> نسخ</button>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light btn-sm" href="${u}" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-left"></i> فتح الصفحة</a>
                                <button class="btn btn-light btn-sm" type="button" data-qr-download="${u}"><i class="bi bi-download"></i> تنزيل QR</button>
                            </div>
                        </div>
                    </div>`;
                renderAllQr(box);
                btn.remove();
            } catch (err) {
                box.innerHTML = `<div class="alert alert-danger small mb-0">${esc(err.message)}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-qr-code"></i> تحويل إلى رابط و QR';
            }
        };

        resultSec.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // ربط الأحداث
    chips.forEach((c) => c.addEventListener('click', () => selectTool(c.dataset.tool)));
    fileInput.addEventListener('change', () => handleFiles(fileInput.files));
    ['dragenter', 'dragover'].forEach((ev) => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.add('dragover'); }));
    ['dragleave', 'drop'].forEach((ev) => dropzone.addEventListener(ev, (e) => { e.preventDefault(); dropzone.classList.remove('dragover'); }));
    dropzone.addEventListener('drop', (e) => { if (e.dataTransfer?.files?.length) handleFiles(e.dataTransfer.files); });

    forms.forEach((form) => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!selectedFiles.length) {
                setStatus('warning', 'اختر ملفاً أولاً قبل التنفيذ.');
                return;
            }
            const t = page.tools[activeTool];
            const btn = form.querySelector('button[type="submit"]');
            const origBtn = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري المعالجة...';
            setStatus('info', 'جاري معالجة الملف...');

            try {
                if (t.engine === 'server') {
                    await runServerTool(form);
                } else if (t.engine === 'ffmpeg') {
                    const out = await window.JU_MEDIA.run(page.category, activeTool, selectedFiles[0], new FormData(form), (msg) => setStatus('info', esc(msg)));
                    renderFileResult(out.blob, out.name, out.info);
                    logBrowserOp();
                } else if (t.engine === 'pdf') {
                    const out = await window.JU_PDF.run(activeTool, selectedFiles, new FormData(form));
                    renderFileResult(out.blob, out.name, out.info);
                    logBrowserOp();
                }
                setStatus('', '');
            } catch (err) {
                setStatus('danger', esc(err.message || 'حدث خطأ أثناء المعالجة.').replace(/\n/g, '<br>'));
            } finally {
                btn.disabled = false;
                btn.innerHTML = origBtn;
            }
        });
    });

    const initialTool = new URLSearchParams(location.search).get('tool') || Object.keys(page.tools)[0];
    selectTool(initialTool);
})();

