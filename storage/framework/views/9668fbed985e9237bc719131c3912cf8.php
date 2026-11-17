<?php $__env->startSection('title', 'كل أدوات ملفاتك في مكان واحد'); ?>
<?php $activePage = 'home'; ?>

<?php $__env->startSection('content'); ?>
<section class="hero text-center py-4 py-lg-5 mb-4">
    <span class="badge rounded-pill bg-accent-soft text-accent px-3 py-2 mb-3">
        <i class="bi bi-magic"></i> بسيط · سريع · آمن
    </span>
    <h1 class="display-6 fw-bold mb-3">اختر نوع الملف وابدأ التعديل فوراً</h1>
    <p class="lead text-secondary mx-auto mb-4" style="max-width:640px">
        منصة متخصصة لتعديل الملفات، تحويل الصيغ، وتصغير الحجم — مع إمكانية تحويل أي ملف إلى رابط مباشر أو رمز QR للمشاركة السريعة.
    </p>
    <div class="d-flex justify-content-center flex-wrap gap-2">
        <a href="<?php echo e(route('shares.create')); ?>" class="btn btn-accent btn-lg px-4">
            <i class="bi bi-qr-code"></i> حوّل ملفاً إلى رابط أو QR
        </a>
        <?php if(auth()->guard()->guest()): ?>
            <a href="<?php echo e(route('register')); ?>" class="btn btn-outline-dark btn-lg px-4">حساب مجاني لحفظ روابطك</a>
        <?php else: ?>
            <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-outline-dark btn-lg px-4"><i class="bi bi-grid"></i> لوحة ملفاتي</a>
        <?php endif; ?>
    </div>
</section>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 fw-bold mb-0">ماذا تريد أن تعدّل اليوم؟</h2>
        <span class="small text-secondary">تظهر لك الخيارات المتاحة لكل نوع ملف تحديداً</span>
    </div>

    <div class="row g-3">
        <?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="col-md-6 col-lg-3">
                <a class="cat-card panel h-100 d-flex flex-column text-decoration-none text-reset" href="<?php echo e(route('tools.' . $key)); ?>">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="cat-icon"><i class="bi <?php echo e($cat['icon']); ?>"></i></div>
                        <span class="badge text-bg-light"><?php echo e(count($cat['tools'])); ?> أدوات</span>
                    </div>
                    <h3 class="h5 fw-bold mb-1"><?php echo e($cat['title']); ?></h3>
                    <p class="small text-secondary mb-3 flex-grow-1"><?php echo e($cat['desc']); ?></p>
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <?php $__currentLoopData = $cat['tools']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <span class="mini-tag"><?php echo e($t['title']); ?></span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center small fw-bold text-accent mt-auto pt-2 border-top">
                        <span>فتح أدوات <?php echo e($cat['title']); ?></span>
                        <i class="bi bi-arrow-left"></i>
                    </div>
                </a>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</section>

<section class="panel mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <h2 class="h5 fw-bold mb-2"><i class="bi bi-qr-code-scan text-accent"></i> مشاركة أي ملف برابط أو رمز QR</h2>
            <p class="text-secondary mb-3">
                سواء عدّلت ملفك هنا أو كان لديك ملف جاهز على جهازك، يمكنك تحويله خلال ثوانٍ إلى رابط قصير ورمز QR قابل للمسح بالجوال، مع خيار تحديد مدة صلاحية أو كلمة مرور.
            </p>
            <a href="<?php echo e(route('shares.create')); ?>" class="btn btn-accent"><i class="bi bi-upload"></i> ارفع ملفاً للمشاركة</a>
        </div>
        <div class="col-lg-5">
            <div class="row g-2 text-center">
                <div class="col-6">
                    <div class="p-3 rounded-3 bg-light">
                        <div class="h4 fw-bold mb-0 text-accent"><?php echo e($totalOps); ?></div>
                        <div class="small text-secondary">عملية معالجة منفّذة</div>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 rounded-3 bg-light">
                        <div class="h4 fw-bold mb-0 text-accent"><?php echo e($totalShares); ?></div>
                        <div class="small text-secondary">رابط مشاركة نشط</div>
                    </div>
                </div>
                <?php $__currentLoopData = $cats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-3">
                        <div class="p-2 rounded-3 bg-light">
                            <div class="fw-bold"><?php echo e((int) ($catCounts[$key] ?? 0)); ?></div>
                            <div class="small text-secondary"><?php echo e($cat['title']); ?></div>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
    </div>
</section>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/home.blade.php ENDPATH**/ ?>