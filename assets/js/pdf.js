/**
 * أدوات PDF داخل المتصفح عبر pdf-lib.
 */
window.JU_PDF = (() => {
    'use strict';

    /** يحوّل نصاً مثل "1-3,5" إلى مصفوفة فهارس صفرية مرتبة (0-based). */
    function parsePages(spec, total) {
        const text = (spec || '').trim();
        if (!text) return [...Array(total).keys()];
        const set = new Set();
        for (const part of text.split(',')) {
            const p = part.trim();
            if (!p) continue;
            const m = p.match(/^(\d+)\s*-\s*(\d+)$/);
            if (m) {
                const a = Number(m[1]), b = Number(m[2]);
                if (a < 1 || b < a || b > total) throw new Error(`النطاق «${p}» خارج حدود المستند (1–${total}).`);
                for (let i = a; i <= b; i++) set.add(i - 1);
            } else if (/^\d+$/.test(p)) {
                const n = Number(p);
                if (n < 1 || n > total) throw new Error(`الصفحة ${n} غير موجودة (عدد الصفحات ${total}).`);
                set.add(n - 1);
            } else {
                throw new Error(`صيغة الصفحات غير صحيحة عند «${p}». استخدم أرقاماً مثل 1-3,5`);
            }
        }
        if (!set.size) throw new Error('حدد صفحة واحدة على الأقل.');
        return [...set].sort((a, b) => a - b);
    }

    /** يحوّل أي صورة إلى PNG/JPEG مدعوم من pdf-lib. */
    async function imageToBytes(file) {
        const type = file.type.toLowerCase();
        if (type === 'image/jpeg' || type === 'image/png') {
            return { bytes: new Uint8Array(await file.arrayBuffer()), isPng: type === 'image/png' };
        }
        const url = URL.createObjectURL(file);
        try {
            const img = await new Promise((res, rej) => {
                const el = new Image();
                el.onload = () => res(el);
                el.onerror = () => rej(new Error(`تعذّرت قراءة الصورة ${file.name}`));
                el.src = url;
            });
            const canvas = document.createElement('canvas');
            canvas.width = img.naturalWidth;
            canvas.height = img.naturalHeight;
            canvas.getContext('2d').drawImage(img, 0, 0);
            const blob = await new Promise((r) => canvas.toBlob(r, 'image/png'));
            return { bytes: new Uint8Array(await blob.arrayBuffer()), isPng: true };
        } finally {
            URL.revokeObjectURL(url);
        }
    }

    async function run(tool, files, formData) {
        if (!window.PDFLib) throw new Error('مكتبة PDF لم تُحمّل.');
        const { PDFDocument, degrees } = window.PDFLib;
        const base = files[0].name.replace(/\.[^.]+$/, '');

        switch (tool) {
            case 'pdf_merge': {
                if (files.length < 2) throw new Error('اختر ملفي PDF على الأقل للدمج.');
                const out = await PDFDocument.create();
                let count = 0;
                for (const f of files) {
                    const src = await PDFDocument.load(await f.arrayBuffer());
                    const pages = await out.copyPages(src, src.getPageIndices());
                    pages.forEach((p) => out.addPage(p));
                    count += pages.length;
                }
                return makeResult(await out.save(), `${base}-merged.pdf`, `${count} صفحة`);
            }

            case 'pdf_extract': {
                const src = await PDFDocument.load(await files[0].arrayBuffer());
                const indices = parsePages(formData.get('pages'), src.getPageCount());
                const out = await PDFDocument.create();
                (await out.copyPages(src, indices)).forEach((p) => out.addPage(p));
                return makeResult(await out.save(), `${base}-extracted.pdf`, `${indices.length} صفحة`);
            }

            case 'pdf_delete': {
                const src = await PDFDocument.load(await files[0].arrayBuffer());
                const total = src.getPageCount();
                const del = new Set(parsePages(formData.get('pages'), total));
                if (del.size >= total) throw new Error('لا يمكن حذف جميع صفحات المستند.');
                const keep = [...Array(total).keys()].filter((i) => !del.has(i));
                const out = await PDFDocument.create();
                (await out.copyPages(src, keep)).forEach((p) => out.addPage(p));
                return makeResult(await out.save(), `${base}-trimmed.pdf`, `تبقّى ${keep.length} صفحة`);
            }

            case 'pdf_rotate': {
                const doc = await PDFDocument.load(await files[0].arrayBuffer());
                const angle = Number(formData.get('angle') || 90);
                const indices = parsePages(formData.get('pages'), doc.getPageCount());
                indices.forEach((i) => {
                    const p = doc.getPage(i);
                    p.setRotation(degrees((p.getRotation().angle + angle) % 360));
                });
                return makeResult(await doc.save(), `${base}-rotated.pdf`, `دُوّرت ${indices.length} صفحة`);
            }

            case 'images_pdf': {
                const doc = await PDFDocument.create();
                const sizeMode = formData.get('size') || 'a4';
                for (const f of files) {
                    const { bytes, isPng } = await imageToBytes(f);
                    const img = isPng ? await doc.embedPng(bytes) : await doc.embedJpg(bytes);
                    if (sizeMode === 'fit') {
                        const page = doc.addPage([img.width, img.height]);
                        page.drawImage(img, { x: 0, y: 0, width: img.width, height: img.height });
                    } else {
                        const pw = 595.28, ph = 841.89, pad = 28;
                        const scale = Math.min((pw - pad * 2) / img.width, (ph - pad * 2) / img.height, 1);
                        const w = img.width * scale, h = img.height * scale;
                        const page = doc.addPage([pw, ph]);
                        page.drawImage(img, { x: (pw - w) / 2, y: (ph - h) / 2, width: w, height: h });
                    }
                }
                return makeResult(await doc.save(), `${base}.pdf`, `${files.length} صفحة`);
            }
        }
        throw new Error('أداة PDF غير معروفة.');
    }

    function makeResult(bytes, name, info) {
        return { blob: new Blob([bytes], { type: 'application/pdf' }), name, info };
    }

    return { run };
})();

