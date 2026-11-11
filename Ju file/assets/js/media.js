/**
 * معالجة الفيديو والصوت داخل المتصفح عبر FFmpeg.wasm.
 */
window.JU_MEDIA = (() => {
    'use strict';

    let ffmpeg = null;

    const MIMES = {
        mp4: 'video/mp4', webm: 'video/webm', mov: 'video/quicktime',
        mkv: 'video/x-matroska', avi: 'video/x-msvideo', gif: 'image/gif',
        mp3: 'audio/mpeg', wav: 'audio/wav', ogg: 'audio/ogg',
        m4a: 'audio/mp4', flac: 'audio/flac',
    };

    async function ensureLoaded(onProgress) {
        if (ffmpeg?.loaded) return ffmpeg;
        if (!window.FFmpegWASM?.FFmpeg) {
            throw new Error('مكتبة معالجة الوسائط لم تُحمّل.');
        }
        onProgress('جاري تهيئة محرك الوسائط (أول مرة فقط)...');
        ffmpeg = new window.FFmpegWASM.FFmpeg();
        ffmpeg.on('progress', ({ progress }) => {
            if (Number.isFinite(progress) && progress > 0 && progress <= 1) {
                onProgress(`جاري المعالجة... ${Math.round(progress * 100)}%`);
            }
        });
        await ffmpeg.load({
            coreURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd/ffmpeg-core.js',
            wasmURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd/ffmpeg-core.wasm',
        });
        return ffmpeg;
    }

    function cropFilter(ratio) {
        const [rw, rh] = ratio.split(':').map(Number);
        // يقتطع أكبر مستطيل في المنتصف يحقق النسبة المطلوبة مع أبعاد زوجية
        return `crop='trunc(min(iw,ih*${rw}/${rh})/2)*2':'trunc(min(ih,iw*${rh}/${rw})/2)*2'`;
    }

    async function run(category, tool, file, formData, onProgress) {
        const ff = await ensureLoaded(onProgress);
        const ext = (file.name.split('.').pop() || (category === 'video' ? 'mp4' : 'mp3')).toLowerCase();
        const base = file.name.replace(/\.[^.]+$/, '');
        const inName = `input.${ext}`;

        onProgress('جاري قراءة الملف...');
        await ff.writeFile(inName, new Uint8Array(await file.arrayBuffer()));

        let outExt = ext;
        let args = [];
        let suffix = tool;

        if (category === 'video') {
            switch (tool) {
                case 'compress': {
                    outExt = 'mp4';
                    const crf = formData.get('quality') || '28';
                    const h = Number(formData.get('max_height') || 0);
                    const vf = h > 0 ? ['-vf', `scale=-2:'min(${h},ih)'`] : [];
                    args = ['-i', inName, ...vf, '-c:v', 'libx264', '-preset', 'veryfast', '-crf', crf, '-c:a', 'aac', '-b:a', '128k', '-movflags', '+faststart'];
                    break;
                }
                case 'trim': {
                    const start = Number(formData.get('start') || 0);
                    const end = Number(formData.get('end') || 0);
                    if (!(end > start)) throw new Error('وقت النهاية يجب أن يكون أكبر من وقت البداية.');
                    const fast = formData.get('fast') === '1';
                    args = fast
                        ? ['-ss', String(start), '-to', String(end), '-i', inName, '-c', 'copy']
                        : ['-ss', String(start), '-to', String(end), '-i', inName, '-c:v', 'libx264', '-preset', 'veryfast', '-c:a', 'aac'];
                    break;
                }
                case 'crop': {
                    outExt = 'mp4';
                    const ratio = formData.get('ratio') || '9:16';
                    args = ['-i', inName, '-vf', cropFilter(ratio), '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '23', '-c:a', 'copy'];
                    suffix = ratio.replace(':', 'x');
                    break;
                }
                case 'convert': {
                    outExt = formData.get('format') || 'mp4';
                    suffix = '';
                    if (outExt === 'gif') {
                        args = ['-t', '15', '-i', inName, '-vf', 'fps=12,scale=480:-1:flags=lanczos'];
                    } else if (outExt === 'webm') {
                        args = ['-i', inName, '-c:v', 'libvpx', '-b:v', '1M', '-c:a', 'libvorbis'];
                    } else {
                        args = ['-i', inName, '-c:v', 'libx264', '-preset', 'veryfast', '-c:a', 'aac'];
                    }
                    break;
                }
                case 'extract_audio': {
                    outExt = formData.get('format') || 'mp3';
                    suffix = 'audio';
                    args = outExt === 'wav'
                        ? ['-i', inName, '-vn', '-c:a', 'pcm_s16le']
                        : outExt === 'm4a'
                            ? ['-i', inName, '-vn', '-c:a', 'aac', '-b:a', '160k']
                            : ['-i', inName, '-vn', '-c:a', 'libmp3lame', '-q:a', '2'];
                    break;
                }
                case 'mute': {
                    args = ['-i', inName, '-c:v', 'copy', '-an'];
                    break;
                }
            }
        } else { // audio
            switch (tool) {
                case 'convert': {
                    outExt = formData.get('format') || 'mp3';
                    suffix = '';
                    args = ['-i', inName];
                    break;
                }
                case 'compress': {
                    outExt = 'mp3';
                    const br = (formData.get('bitrate') || '96') + 'k';
                    const mono = formData.get('mono') === '1' ? ['-ac', '1'] : [];
                    args = ['-i', inName, ...mono, '-c:a', 'libmp3lame', '-b:a', br];
                    break;
                }
                case 'trim': {
                    const start = Number(formData.get('start') || 0);
                    const end = Number(formData.get('end') || 0);
                    if (!(end > start)) throw new Error('وقت النهاية يجب أن يكون أكبر من وقت البداية.');
                    args = ['-ss', String(start), '-to', String(end), '-i', inName, '-c', 'copy'];
                    break;
                }
                case 'volume': {
                    const lvl = formData.get('level') || '1.5';
                    const af = lvl === 'normalize' ? 'dynaudnorm' : `volume=${lvl}`;
                    outExt = ext === 'm4a' ? 'mp3' : ext;
                    args = ['-i', inName, '-af', af];
                    break;
                }
            }
        }

        const outName = `output.${outExt}`;
        onProgress('جاري المعالجة...');
        const code = await ff.exec([...args, outName]);
        if (code !== 0) {
            await ff.deleteFile(inName).catch(() => {});
            throw new Error('تعذّرت معالجة الملف بهذه الإعدادات. جرّب صيغة أخرى أو ألغِ القص السريع.');
        }

        const data = await ff.readFile(outName);
        await ff.deleteFile(inName).catch(() => {});
        await ff.deleteFile(outName).catch(() => {});

        const mime = MIMES[outExt] || 'application/octet-stream';
        return {
            blob: new Blob([data.buffer], { type: mime }),
            name: `${base}${suffix ? '-' + suffix : ''}.${outExt}`,
            info: '',
        };
    }

    return { run };
})();

