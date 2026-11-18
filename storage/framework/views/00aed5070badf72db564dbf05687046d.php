<?php $__env->startSection('title', !empty($needsLock) ? __('ملف محمي بكلمة مرور') : ($share ? ($share->title ?: $share->original_name) : __('ملف غير موجود'))); ?>

<?php $__env->startSection('content'); ?>
<?php if(!empty($needsLock)): ?>
    <div class="auth-card panel mx-auto" style="max-width:480px">
        <div class="text-center mb-3">
            <div class="cat-icon mx-auto mb-2"><i class="bi bi-lock-fill"></i></div>
            <h1 class="h5 fw-bold mb-1"><?php echo e(__('الوصول إلى ملف مشارك')); ?></h1>
            <p class="small text-secondary mb-0"><?php echo e(__('أدخل رمز الملف وكلمة المرور لفتح الملف وتنزيله.')); ?></p>
        </div>

        <form method="post" action="<?php echo e(route('shares.unlock')); ?>" novalidate>
            <?php echo csrf_field(); ?>
            <div class="mb-3">
                <label class="form-label" for="token"><?php echo e(__('رمز الملف (16 حرفاً)')); ?></label>
                <input type="text" class="form-control <?php $__errorArgs = ['token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="token" name="token"
                       value="<?php echo e(old('token')); ?>" dir="ltr" placeholder="a1b2c3d4e5f60708" required>
                <?php $__errorArgs = ['token'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="mb-3">
                <label class="form-label" for="password"><?php echo e(__('كلمة المرور')); ?></label>
                <input type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="password" name="password" required>
                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <button class="btn btn-accent w-100"><i class="bi bi-unlock"></i> <?php echo e(__('فتح الملف')); ?></button>
        </form>
    </div>
<?php elseif(!$share || $expired): ?>
    <div class="panel text-center py-5 mx-auto" style="max-width:520px">
        <i class="bi bi-file-earmark-x display-4 text-secondary"></i>
        <h1 class="h4 fw-bold mt-3"><?php echo e($share ? __('انتهت صلاحية هذا الرابط') : __('الملف غير موجود')); ?></h1>
        <p class="text-secondary"><?php echo e(__('ربما حُذف الملف أو انتهت مدته.')); ?></p>
        <a class="btn btn-accent" href="<?php echo e(route('shares.create')); ?>"><?php echo e(__('شارك ملفاً جديداً')); ?></a>
    </div>
<?php else: ?>
    <?php
        $dl = route('shares.download', $share->token);
    ?>
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <div class="panel">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="file-icon"><i class="bi <?php echo e(\App\Services\ToolCatalog::categoryIcon($share->category)); ?>"></i></div>
                    <div class="min-w-0">
                        <h1 class="h5 fw-bold mb-0 text-truncate"><?php echo e($share->title ?: $share->original_name); ?></h1>
                        <div class="small text-secondary text-truncate"><?php echo e($share->original_name); ?></div>
                    </div>
                </div>

                <?php if(!$unlocked): ?>
                    <form method="post" action="<?php echo e(route('shares.unlock', $share->token)); ?>" class="border rounded-3 p-3 bg-light" novalidate>
                        <?php echo csrf_field(); ?>
                        <label class="form-label fw-bold" for="password"><i class="bi bi-lock"></i> <?php echo e(__('هذا الملف محمي بكلمة مرور')); ?></label>
                        <div class="input-group">
                            <input type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="password" name="password" required autofocus>
                            <button class="btn btn-accent"><?php echo e(__('فتح')); ?></button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback d-block mt-1"><?php echo e($message); ?></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </form>
                <?php else: ?>
                    <?php if($previewable === 'img'): ?>
                        <img class="preview-media mb-3" src="<?php echo e($dl); ?>?inline=1" alt="<?php echo e($share->original_name); ?>">
                    <?php elseif($previewable === 'video'): ?>
                        <video class="preview-media mb-3" src="<?php echo e($dl); ?>?inline=1" controls preload="metadata"></video>
                    <?php elseif($previewable === 'audio'): ?>
                        <audio class="w-100 mb-3" src="<?php echo e($dl); ?>?inline=1" controls preload="metadata"></audio>
                    <?php endif; ?>
                    <a class="btn btn-accent btn-lg w-100" href="<?php echo e($dl); ?>">
                        <i class="bi bi-download"></i> <?php echo e(__('تنزيل')); ?> (<?php echo e(\App\Services\ToolCatalog::formatBytes($share->size)); ?>)
                    </a>
                <?php endif; ?>

                <dl class="meta-list mt-4 mb-0">
                    <div><dt><?php echo e(__('النوع')); ?></dt><dd><?php echo e(\App\Services\ToolCatalog::categoryLabel($share->category)); ?> · <span dir="ltr"><?php echo e($share->mime); ?></span></dd></div>
                    <div><dt><?php echo e(__('الحجم')); ?></dt><dd><?php echo e(\App\Services\ToolCatalog::formatBytes($share->size)); ?></dd></div>
                    <div><dt><?php echo e(__('التنزيلات')); ?></dt><dd><?php echo e($share->downloads); ?></dd></div>
                    <div><dt><?php echo e(__('تاريخ الرفع')); ?></dt><dd dir="ltr"><?php echo e($share->created_at?->format('Y-m-d H:i')); ?></dd></div>
                    <div><dt><?php echo e(__('ينتهي')); ?></dt><dd dir="ltr"><?php echo e($share->expires_at?->format('Y-m-d H:i') ?? __('بدون انتهاء')); ?></dd></div>
                </dl>

                <?php if($isOwner): ?>
                    <div class="d-flex gap-2 mt-3">
                        <a class="btn btn-light btn-sm" href="<?php echo e(route('shares.edit', $share->id)); ?>"><i class="bi bi-pencil"></i> <?php echo e(__('تعديل')); ?></a>
                        <a class="btn btn-light btn-sm" href="<?php echo e(route('dashboard')); ?>"><i class="bi bi-grid"></i> <?php echo e(__('ملفاتي')); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel">
                <h2 class="h6 fw-bold mb-3"><i class="bi bi-qr-code-scan text-accent"></i> <?php echo e(__('المشاركة بالرابط أو رمز QR')); ?></h2>
                <?php if (isset($component)) { $__componentOriginal4805c26aa09f966990f8b10b2e428ba5 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal4805c26aa09f966990f8b10b2e428ba5 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.share-box','data' => ['url' => $share->publicUrl()]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('share-box'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($share->publicUrl())]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal4805c26aa09f966990f8b10b2e428ba5)): ?>
<?php $attributes = $__attributesOriginal4805c26aa09f966990f8b10b2e428ba5; ?>
<?php unset($__attributesOriginal4805c26aa09f966990f8b10b2e428ba5); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal4805c26aa09f966990f8b10b2e428ba5)): ?>
<?php $component = $__componentOriginal4805c26aa09f966990f8b10b2e428ba5; ?>
<?php unset($__componentOriginal4805c26aa09f966990f8b10b2e428ba5); ?>
<?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/shares/show.blade.php ENDPATH**/ ?>