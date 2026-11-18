<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['url']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['url']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="share-box">
    <div class="qr" data-qr="<?php echo e($url); ?>"></div>
    <div class="flex-grow-1 w-100">
        <label class="form-label small text-secondary"><?php echo e(__('رابط المشاركة')); ?></label>
        <div class="input-group mb-2" dir="ltr">
            <input type="text" class="form-control" value="<?php echo e($url); ?>" readonly>
            <button class="btn btn-accent" type="button" data-copy="<?php echo e($url); ?>"><i class="bi bi-clipboard"></i> <?php echo e(__('نسخ')); ?></button>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light btn-sm" href="<?php echo e($url); ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-left"></i> <?php echo e(__('فتح')); ?></a>
            <button class="btn btn-light btn-sm" type="button" data-qr-download="<?php echo e($url); ?>"><i class="bi bi-download"></i> <?php echo e(__('تنزيل QR')); ?></button>
        </div>
    </div>
</div>
<?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/components/share-box.blade.php ENDPATH**/ ?>