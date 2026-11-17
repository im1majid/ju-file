<?php $__env->startSection('title', 'تسجيل الدخول'); ?>
<?php $activePage = 'login'; ?>

<?php $__env->startSection('content'); ?>
<div class="auth-card panel mx-auto">
    <h1 class="h4 fw-bold mb-1">تسجيل الدخول</h1>
    <p class="text-secondary small mb-4">أهلاً بعودتك إلى <?php echo e(config('app.name')); ?></p>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger py-2 small"><?php echo e($errors->first()); ?></div>
    <?php endif; ?>

    <form method="post" action="<?php echo e(route('login.store')); ?>" novalidate>
        <?php echo csrf_field(); ?>
        <div class="mb-3">
            <label class="form-label" for="email">البريد الإلكتروني</label>
            <input class="form-control" type="email" id="email" name="email" value="<?php echo e(old('email')); ?>" autocomplete="email" required autofocus>
        </div>
        <div class="mb-4">
            <label class="form-label" for="password">كلمة المرور</label>
            <input class="form-control" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-accent w-100 py-2">دخول</button>
    </form>
    <p class="text-center small text-secondary mt-3 mb-0">
        ليس لديك حساب؟ <a href="<?php echo e(route('register')); ?>">إنشاء حساب جديد</a>
    </p>
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/auth/login.blade.php ENDPATH**/ ?>