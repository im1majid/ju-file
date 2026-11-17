<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['field', 'toolKey']));

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

foreach (array_filter((['field', 'toolKey']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $id = "f_{$toolKey}_{$field['name']}";
    $name = $field['name'];
    $label = $field['label'];
    $val = $field['value'] ?? '';
    $req = ($field['required'] ?? true) ? 'required' : '';
?>

<?php if($field['type'] === 'checkbox'): ?>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="<?php echo e($id); ?>" name="<?php echo e($name); ?>" value="1" <?php if(!empty($val)): echo 'checked'; endif; ?>>
        <label class="form-check-label" for="<?php echo e($id); ?>"><?php echo e($label); ?></label>
    </div>
<?php elseif($field['type'] === 'select'): ?>
    <div class="mb-3">
        <label class="form-label" for="<?php echo e($id); ?>"><?php echo e($label); ?></label>
        <select class="form-select" id="<?php echo e($id); ?>" name="<?php echo e($name); ?>">
            <?php $__currentLoopData = $field['options']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $k => $optLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($k); ?>" <?php if((string) $val === (string) $k): echo 'selected'; endif; ?>><?php echo e($optLabel); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
    </div>
<?php elseif($field['type'] === 'range'): ?>
    <div class="mb-3">
        <label class="form-label" for="<?php echo e($id); ?>">
            <?php echo e($label); ?>: <strong data-range-out="<?php echo e($id); ?>"><?php echo e($val); ?><?php echo e($field['suffix'] ?? ''); ?></strong>
        </label>
        <input type="range" class="form-range" id="<?php echo e($id); ?>" name="<?php echo e($name); ?>"
               min="<?php echo e($field['min']); ?>" max="<?php echo e($field['max']); ?>" value="<?php echo e($val); ?>"
               data-suffix="<?php echo e($field['suffix'] ?? ''); ?>">
    </div>
<?php else: ?>
    <div class="mb-3">
        <label class="form-label" for="<?php echo e($id); ?>"><?php echo e($label); ?></label>
        <?php if(!empty($field['media_time'])): ?>
            <div class="input-group">
                <input type="<?php echo e($field['type']); ?>" class="form-control" id="<?php echo e($id); ?>" name="<?php echo e($name); ?>"
                       value="<?php echo e($val); ?>" min="<?php echo e($field['min'] ?? ''); ?>" max="<?php echo e($field['max'] ?? ''); ?>"
                       step="<?php echo e($field['step'] ?? 'any'); ?>" <?php echo e($req); ?>>
                <button type="button" class="btn btn-light" data-media-time="<?php echo e($id); ?>" title="<?php echo e(__('أخذ الوقت الحالي من المشغّل')); ?>">
                    <i class="bi bi-stopwatch"></i> <?php echo e(__('الآن')); ?>

                </button>
            </div>
        <?php else: ?>
            <input type="<?php echo e($field['type']); ?>" class="form-control" id="<?php echo e($id); ?>" name="<?php echo e($name); ?>"
                   value="<?php echo e($val); ?>" placeholder="<?php echo e($field['placeholder'] ?? ''); ?>"
                   min="<?php echo e($field['min'] ?? ''); ?>" max="<?php echo e($field['max'] ?? ''); ?>"
                   step="<?php echo e($field['step'] ?? 'any'); ?>" maxlength="<?php echo e($field['maxlength'] ?? ''); ?>" <?php echo e($req); ?>>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/components/tool-field.blade.php ENDPATH**/ ?>