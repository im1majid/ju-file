<?php $__env->startSection('title', __('أدوات التحميل')); ?>
<?php $__env->startSection('description', __('حمّل الفيديوهات، الصور، واستخرج الصوتيات بصيغ حقيقية (MP3, M4A, WAV) من يوتيوب، إكس، تيك توك، وإنستقرام.')); ?>
<?php
    $activePage = 'social_download';
    $dlI18N = [
        'detecting'     => __('جاري كشف الجودات المتوفرة...'),
        'fetchErr'      => __('تعذر سحب المسار من المصدر (رمز :code). اضغط على «تنفيذ» لتجديد الرابط وحاول مجدداً.'),
        'incompleteErr' => __('الملف المستلم غير مكتمل، اضغط على تنفيذ لتجديد الرابط.'),
        'initFfmpeg'    => __('جاري تهيئة محرك الدمج (أول مرة فقط)...'),
        'pullAudio'     => __('جاري سحب المسار الصوتي الأصلي'),
        'pullVideo'     => __('جاري سحب الفيديو الأصلي عالي الدقة'),
        'pullTrack'     => __('جاري سحب المسار الصوتي المرافق'),
        'merging'       => __('جاري دمج الصوت مع الفيديو بدقة 100% بدون ضغط...'),
        'done'          => __('اكتمل الدمج والتحميل بأعلى جودة بنجاح!'),
        'genericErr'    => __('حدث خطأ أثناء معالجة الملف.'),
    ];
?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <nav class="small text-secondary mb-1">
        <a href="<?php echo e(route('home')); ?>" class="link-secondary text-decoration-none"><?php echo e(__('الرئيسية')); ?></a> / <?php echo e(__('التحميل')); ?>

    </nav>
    <h1 class="h3 fw-bold mb-1">
        <i class="bi bi-cloud-arrow-down text-accent"></i> <?php echo e(__('أدوات التحميل')); ?>

    </h1>
    <p class="text-secondary mb-0">
        <?php echo e(__('حمّل الفيديوهات، الصور، واستخرج الصوتيات بصيغ حقيقية (MP3, M4A, WAV) من يوتيوب، إكس، تيك توك، وإنستقرام.')); ?>

    </p>
</div>

<!-- التبويبات العلوية -->
<div class="d-flex gap-2 mb-4 overflow-auto pb-1">
    <button type="button" onclick="switchTab('media')" id="tab-media"
            class="btn <?php echo e($downloadMode === 'media' ? 'btn-accent' : 'btn-light'); ?> px-4 rounded-3">
        <i class="bi bi-collection-play"></i> <?php echo e(__('صور وفيديوهات')); ?>

    </button>
    <button type="button" onclick="switchTab('audio')" id="tab-audio"
            class="btn <?php echo e($downloadMode === 'audio' ? 'btn-accent' : 'btn-light'); ?> px-4 rounded-3">
        <i class="bi bi-music-note-beamed"></i> <?php echo e(__('استخراج الصوت')); ?>

    </button>
</div>

<!-- نموذج الجلب -->
<form id="dlForm" method="POST" action="<?php echo e(route('social.download.fetch')); ?>" class="row g-4">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="download_mode" id="downloadModeInput" value="<?php echo e($downloadMode); ?>">

    <!-- إدخال الرابط -->
    <div class="col-lg-8">
        <div class="panel p-4 p-lg-5 h-100 d-flex flex-column align-items-center justify-content-center text-center"
             style="border-style: dashed !important; min-height: 280px;">
            <i class="bi bi-link-45deg display-4 text-accent mb-3"></i>
            <h2 class="h6 mb-3 fw-bold"><?php echo e(__('الصق رابط المقطع هنا')); ?></h2>
            <div class="w-100" style="max-width: 560px;" dir="ltr">
                <input type="url"
                       name="video_url"
                       value="<?php echo e(old('video_url', $videoUrl)); ?>"
                       class="form-control text-center p-3"
                       placeholder="https://www.youtube.com/watch?v=... / Instagram / TikTok / X"
                       required>
            </div>
            <p class="small text-secondary mt-3 mb-0">
                <?php echo e(__('المنصات المدعومة: YouTube, X (Twitter), TikTok, Instagram, Facebook')); ?>

            </p>
        </div>
    </div>

    <!-- الإعدادات -->
    <div class="col-lg-4">
        <div class="panel p-4 h-100 d-flex flex-column">
            <h3 class="h6 fw-bold mb-1"><?php echo e(__('إعدادات التحميل')); ?></h3>
            <p class="small text-secondary mb-3 border-bottom pb-2">
                <?php echo e(__('كشف تلقائي لجميع الجودات والصيغ المتوفرة في المقطع.')); ?>

            </p>

            <div class="mb-4" id="mediaSettingsBox" style="<?php echo e($downloadMode === 'audio' ? 'display:none;' : 'display:block;'); ?>">
                <div class="p-3 rounded-3 border bg-light small">
                    <i class="bi bi-collection-play text-accent"></i>
                    <strong><?php echo e(__('عرض جميع الجودات الحقيقية معاً:')); ?></strong>
                    <div class="text-secondary mt-1">
                        <?php echo e(__('يتم استخراج جميع الدقات الموجودة فعلياً في المقطع مع حجم كل جودة بالميجابايت، ودمجها بالصوت الأصلي الكامل (128kbps AAC) في ثوانٍ.')); ?>

                    </div>
                </div>
            </div>

            <div class="mb-4" id="audioSettingsBox" style="<?php echo e($downloadMode === 'audio' ? 'display:block;' : 'display:none;'); ?>">
                <div class="p-3 rounded-3 border bg-light small">
                    <i class="bi bi-music-note-list text-accent"></i>
                    <strong><?php echo e(__('جميع الصيغ الصوتية معاً:')); ?></strong>
                    <div class="text-secondary mt-1">
                        <?php echo e(__('تظهر لك أزرار التحميل بصيغ (MP3 و M4A / MP4 و WAV) معاً لتختار منها مباشرة.')); ?>

                    </div>
                </div>
            </div>

            <div class="mt-auto">
                <button type="submit" id="submitBtn" class="btn btn-accent w-100 py-2 d-flex justify-content-center align-items-center gap-2">
                    <span><?php echo e(__('تنفيذ وعرض الجودات المتوفرة')); ?></span>
                    <i class="bi bi-arrow-left icon-directional"></i>
                </button>
                <div class="mt-3 small text-secondary text-center">
                    <i class="bi bi-shield-check text-accent"></i> <?php echo e(__('سحب مباشر بأعلى جودة أصلية')); ?>

                </div>
            </div>
        </div>
    </div>
</form>

<!-- رسائل الخطأ -->
<?php if(!empty($errorMsg)): ?>
    <div class="alert alert-danger text-center mt-4" role="alert">
        <i class="bi bi-exclamation-triangle-fill"></i> <?php echo e($errorMsg); ?>

    </div>
<?php endif; ?>

<!-- النتائج وأزرار الجودات المتوفرة فعلياً -->
<?php if(!empty($availableLinks)): ?>
    <div class="panel p-4 mt-4 border-success">
        <div class="row align-items-center g-3">
            <?php if(!empty($mediaThumb)): ?>
                <div class="col-md-3 text-center">
                    <img src="<?php echo e($mediaThumb); ?>" alt="Thumbnail" class="img-fluid rounded-3 border" style="max-height: 160px; object-fit: cover;">
                </div>
            <?php endif; ?>

            <div class="<?php echo e(!empty($mediaThumb) ? 'col-md-9' : 'col-12'); ?>">
                <h2 class="h5 fw-bold mb-2">
                    <i class="bi bi-check-circle-fill text-success"></i> <?php echo e($mediaTitle); ?>

                </h2>
                <p class="small text-secondary mb-3">
                    <?php echo e(__('اختر الجودة المطلوبة لبدء السحب المباشر والدمج بأعلى دقة أصلية:')); ?>

                </p>

                <div class="d-flex flex-wrap gap-2">
                    <?php $__currentLoopData = $availableLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $linkItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($linkItem['mode'] === 'audio'): ?>
                            <button type="button"
                                    class="btn btn-outline-info px-4 py-2 rounded-3 d-flex align-items-center gap-2 dl-action-btn"
                                    onclick="processSmartDownload(this)"
                                    data-mode="audio"
                                    data-url="<?php echo e($linkItem['proxy_video']); ?>"
                                    data-vsize="<?php echo e((int) $linkItem['video_size']); ?>"
                                    data-format="<?php echo e($linkItem['format']); ?>">
                                <i class="bi bi-music-note-beamed"></i>
                                <span><?php echo e(__('تحميل صوت')); ?> <?php echo e($linkItem['label']); ?></span>
                            </button>
                        <?php elseif($linkItem['mode'] === 'merge'): ?>
                            <button type="button"
                                    class="btn btn-success px-4 py-2 rounded-3 d-flex align-items-center gap-2 dl-action-btn"
                                    onclick="processSmartDownload(this)"
                                    data-mode="merge"
                                    data-url="<?php echo e($linkItem['proxy_video']); ?>"
                                    data-audio="<?php echo e($linkItem['proxy_audio']); ?>"
                                    data-vsize="<?php echo e((int) $linkItem['video_size']); ?>"
                                    data-asize="<?php echo e((int) $linkItem['audio_size']); ?>"
                                    data-label="<?php echo e($linkItem['label']); ?>">
                                <i class="bi bi-download"></i>
                                <span><?php echo e(__('تحميل')); ?> (<?php echo e($linkItem['label']); ?>)</span>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo e($linkItem['proxy_video']); ?>"
                               class="btn btn-success px-4 py-2 rounded-3 d-flex align-items-center gap-2">
                                <i class="bi bi-download"></i>
                                <span><?php echo e(__('تحميل')); ?> (<?php echo e($linkItem['label']); ?>)</span>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>

                <!-- شريط حالة السحب والدمج -->
                <div id="convertStatusBox" class="mt-3 small text-accent d-none">
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    <span id="convertStatusText"><?php echo e(__('جاري تجهيز الملف...')); ?></span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="<?php echo e(asset('assets/vendor/ffmpeg.js')); ?>"></script>
<script src="<?php echo e(asset('assets/js/media.js?v=4')); ?>"></script>
<script>
const DL_I18N = <?php echo json_encode($dlI18N, JSON_UNESCAPED_UNICODE, 512) ?>;
let cachedFFmpeg = null;

function switchTab(mode) {
    document.getElementById('downloadModeInput').value = mode;
    const tabMedia = document.getElementById('tab-media');
    const tabAudio = document.getElementById('tab-audio');
    const mediaBox = document.getElementById('mediaSettingsBox');
    const audioBox = document.getElementById('audioSettingsBox');

    if (mode === 'audio') {
        tabAudio.className = 'btn btn-accent px-4 rounded-3';
        tabMedia.className = 'btn btn-light px-4 rounded-3';
        audioBox.style.display = 'block';
        mediaBox.style.display = 'none';
    } else {
        tabMedia.className = 'btn btn-accent px-4 rounded-3';
        tabAudio.className = 'btn btn-light px-4 rounded-3';
        mediaBox.style.display = 'block';
        audioBox.style.display = 'none';
    }
}

document.getElementById('dlForm')?.addEventListener('submit', function () {
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span>' + DL_I18N.detecting + '</span> <span class="spinner-border spinner-border-sm"></span>';
    }
});

function setAllButtonsDisabled(state) {
    document.querySelectorAll('.dl-action-btn').forEach(b => { b.disabled = state; });
}

function triggerBlobDownload(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 6000);
}

async function fetchStreamWithProgress(url, labelPrefix, expectedBytes, statusText) {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(DL_I18N.fetchErr.replace(':code', String(response.status)));
    }
    if (!response.body) {
        return await response.blob();
    }

    const reader = response.body.getReader();
    const chunks = [];
    let receivedLength = 0;
    const totalMbStr = expectedBytes > 0 ? ' / ' + (expectedBytes / (1024 * 1024)).toFixed(1) + ' MB' : ' MB';

    while (true) {
        const step = await reader.read();
        if (step.done) break;
        chunks.push(step.value);
        receivedLength += step.value.length;
        const mb = (receivedLength / (1024 * 1024)).toFixed(1);
        statusText.textContent = labelPrefix + ' (' + mb + totalMbStr + ')...';
    }

    if (receivedLength < 1024) {
        throw new Error(DL_I18N.incompleteErr);
    }

    return new Blob(chunks);
}

async function getLoadedFFmpeg(statusText) {
    if (cachedFFmpeg && cachedFFmpeg.loaded) {
        return cachedFFmpeg;
    }
    statusText.textContent = DL_I18N.initFfmpeg;
    cachedFFmpeg = new window.FFmpegWASM.FFmpeg();
    await cachedFFmpeg.load({
        coreURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd/ffmpeg-core.js',
        wasmURL: 'https://unpkg.com/@ffmpeg/core@0.12.6/dist/umd/ffmpeg-core.wasm'
    });
    return cachedFFmpeg;
}

async function processSmartDownload(btn) {
    const mode       = btn.getAttribute('data-mode');
    const streamUrl  = btn.getAttribute('data-url');
    const audioUrl   = btn.getAttribute('data-audio');
    const vSize      = parseInt(btn.getAttribute('data-vsize') || '0', 10);
    const aSize      = parseInt(btn.getAttribute('data-asize') || '0', 10);
    const targetFmt  = btn.getAttribute('data-format') || 'mp3';
    const statusBox  = document.getElementById('convertStatusBox');
    const statusText = document.getElementById('convertStatusText');

    setAllButtonsDisabled(true);
    statusBox.classList.remove('d-none');

    try {
        if (mode === 'audio') {
            const rawAudioBlob = await fetchStreamWithProgress(streamUrl, DL_I18N.pullAudio, vSize, statusText);
            if (targetFmt === 'm4a') {
                triggerBlobDownload(new Blob([rawAudioBlob], { type: 'audio/mp4' }), 'jufile_' + Date.now() + '.m4a');
            } else if (window.JU_MEDIA && typeof window.JU_MEDIA.run === 'function') {
                const inFile = new File([rawAudioBlob], 'source.m4a', { type: 'audio/mp4' });
                const fd = new FormData();
                fd.set('format', targetFmt);

                const out = await window.JU_MEDIA.run('video', 'extract_audio', inFile, fd, function (msg) {
                    if (typeof msg === 'string') {
                        statusText.textContent = msg;
                    } else if (msg && msg.label) {
                        statusText.textContent = msg.pct ? msg.label + ' (' + msg.pct + '%)' : msg.label;
                    }
                });
                triggerBlobDownload(out.blob, 'jufile_' + Date.now() + '.' + targetFmt);
            } else {
                triggerBlobDownload(rawAudioBlob, 'jufile_' + Date.now() + '.' + targetFmt);
            }
        } else if (mode === 'merge' && audioUrl && window.FFmpegWASM && window.FFmpegWASM.FFmpeg) {
            const rawVideoBlob = await fetchStreamWithProgress(streamUrl, DL_I18N.pullVideo, vSize, statusText);
            const aBlob        = await fetchStreamWithProgress(audioUrl, DL_I18N.pullTrack, aSize, statusText);

            const ff = await getLoadedFFmpeg(statusText);
            statusText.textContent = DL_I18N.merging;

            await ff.writeFile('v.mp4', new Uint8Array(await rawVideoBlob.arrayBuffer()));
            await ff.writeFile('a.m4a', new Uint8Array(await aBlob.arrayBuffer()));

            const code = await ff.exec(['-i', 'v.mp4', '-i', 'a.m4a', '-c:v', 'copy', '-c:a', 'copy', '-shortest', 'merged.mp4']);
            if (code !== 0) {
                await ff.exec(['-i', 'v.mp4', '-i', 'a.m4a', '-c:v', 'copy', '-c:a', 'aac', '-shortest', 'merged.mp4']);
            }

            const mergedData = await ff.readFile('merged.mp4');
            await ff.deleteFile('v.mp4').catch(() => {});
            await ff.deleteFile('a.m4a').catch(() => {});
            await ff.deleteFile('merged.mp4').catch(() => {});

            const mergedBlob = new Blob([mergedData.buffer], { type: 'video/mp4' });
            triggerBlobDownload(mergedBlob, 'jufile_' + Date.now() + '.mp4');
        }

        statusText.textContent = DL_I18N.done;
        setTimeout(() => statusBox.classList.add('d-none'), 4000);
    } catch (err) {
        alert(err.message || DL_I18N.genericErr);
        statusBox.classList.add('d-none');
    } finally {
        setAllButtonsDisabled(false);
    }
}
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Fan Boy\Documents\GitHub\ju-file\resources\views/social_download.blade.php ENDPATH**/ ?>