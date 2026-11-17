<?php $__env->startSection('title', 'مشاركة ملف برابط أو QR'); ?>
<?php $activePage = 'share'; ?>

<?php $__env->startSection('content'); ?>
<div class="row g-4 justify-content-center">
    <div class="col-lg-7">
        <div class="panel">
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-qr-code text-accent"></i> تحويل أي ملف إلى رابط ورمز QR</h1>
            <p class="text-secondary small mb-4">ارفع صورة، فيديو، صوت، PDF أو أي ملف لتحصل فوراً على رابط مشاركة عام ورمز QR قابل للمسح.</p>

            <?php if($errors->any()): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo e(route('shares.store')); ?>" enctype="multipart/form-data" novalidate>
                <?php echo csrf_field(); ?>
                <label class="dropzone mb-3" for="shareFile" data-drop-target="#shareFileName">
                    <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                    <div class="fw-bold mt-2">اسحب أي ملف هنا أو اضغط للاختيار</div>
                    <div class="small text-secondary mt-1" id="shareFileName">
                        الحد الأقصى <?php echo e(\App\Services\ToolCatalog::formatBytes(\App\Services\ToolCatalog::MAX_UPLOAD_BYTES)); ?> · جميع الصيغ مدعومة
                    </div>
                    <input class="d-none" type="file" id="shareFile" name="file" required>
                </label>

                <div class="mb-3">
                    <label class="form-label" for="title">عنوان للملف (اختياري)</label>
                    <input class="form-control" id="title" name="title" maxlength="150"
                           value="<?php echo e(old('title')); ?>" placeholder="مثال: عرض التخرج النهائي">
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-sm-6">
                        <label class="form-label" for="expiry">مدة الصلاحية</label>
                        <select class="form-select" id="expiry" name="expiry">
                            <?php $__currentLoopData = $expiryOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($k); ?>" <?php if(old('expiry', '24') === (string) $k): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if(auth()->guard()->guest()): ?>
                            <div class="form-text">سجّل دخولك للحصول على مدة 30 يوماً أو بدون انتهاء.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="password">كلمة مرور للحماية (اختياري)</label>
                        <input class="form-control" type="password" id="password" name="password"
                               minlength="4" maxlength="72" placeholder="اتركه فارغاً للوصول العام">
                    </div>
                </div>

                <button class="btn btn-accent btn-lg w-100"><i class="bi bi-link-45deg"></i> إنشاء الرابط ورمز QR</button>
            </form>
        </div>
    </div>

    <?php if(auth()->guard()->check()): ?>
        <?php if($recent->isNotEmpty()): ?>
            <div class="col-lg-4">
                <div class="panel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 fw-bold mb-0">آخر مشاركاتك</h2>
                        <a href="<?php echo e(route('dashboard')); ?>" class="small">عرض الكل</a>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php $__currentLoopData = $recent; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a class="list-group-item list-group-item-action px-0 d-flex align-items-center gap-2"
                               href="<?php echo e(route('shares.show', $r->token)); ?>">
                                <i class="bi <?php echo e(\App\Services\ToolCatalog::categoryIcon($r->category)); ?> text-accent"></i>
                                <span class="text-truncate flex-grow-1"><?php echo e($r->title ?: $r->original_name); ?></span>
                                <i class="bi bi-chevron-left small text-secondary"></i>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/shares/create.blade.php ENDPATH**/ ?>