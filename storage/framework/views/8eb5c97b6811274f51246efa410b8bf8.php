<?php $__env->startSection('title', __('إنشاء حساب')); ?>
<?php $activePage = 'register'; ?>

<?php $__env->startSection('content'); ?>
<div class="auth-card panel mx-auto">
    <h1 class="h4 fw-bold mb-1"><?php echo e(__('إنشاء حساب جديد')); ?></h1>
    <p class="text-secondary small mb-4"><?php echo e(__('احفظ روابط ملفاتك، عدّلها، وتحكم بمدة صلاحيتها.')); ?></p>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger py-2 small">
            <ul class="mb-0 ps-3">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(route('register.store')); ?>" novalidate>
        <?php echo csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label" for="name"><?php echo e(__('الاسم')); ?></label>
            <input class="form-control" id="name" name="name" value="<?php echo e(old('name')); ?>" minlength="2" maxlength="80" autocomplete="name" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="email"><?php echo e(__('البريد الإلكتروني')); ?></label>
            <input class="form-control" type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password"><?php echo e(__('كلمة المرور')); ?></label>
            <input class="form-control" type="password" id="password" name="password" minlength="8" autocomplete="new-password" required>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password_confirmation"><?php echo e(__('تأكيد كلمة المرور')); ?></label>
            <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="8" autocomplete="new-password" required>
        </div>
        <button class="btn btn-accent w-100 py-2"><?php echo e(__('إنشاء الحساب')); ?></button>
    </form>
    <p class="text-center small text-secondary mt-3 mb-0">
        <?php echo e(__('لديك حساب؟')); ?> <a href="<?php echo e(route('login')); ?>"><?php echo e(__('تسجيل الدخول')); ?></a>
    </p>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/auth/register.blade.php ENDPATH**/ ?>