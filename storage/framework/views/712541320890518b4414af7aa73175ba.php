<?php $__env->startSection('title', __('أدوات :title', ['title' => $cat['title']])); ?>
<?php
    $activePage = $category;
    $firstKey = array_key_first($tools);
    $firstTool = $tools[$firstKey];
    $maxUploadBytes = \App\Services\ToolCatalog::maxUploadBytes(auth()->check());
    $clientConfig = [
        'category'  => $category,
        'maxUpload' => $maxUploadBytes,
        'tools'     => array_map(fn ($t) => [
            'title'  => $t['title'],
            'desc'   => $t['desc'],
            'engine' => $t['engine'],
            'accept' => $t['accept'],
            'multi'  => $t['multi'] ?? false,
            'cropUi' => $t['crop_ui'] ?? false,
            'trimUi' => $t['trim_ui'] ?? false,
        ], $tools),
    ];
?>

<?php $__env->startSection('content'); ?>
<div class="mb-4">
    <nav class="small text-secondary mb-1">
        <a href="<?php echo e(route('home')); ?>" class="link-secondary text-decoration-none"><?php echo e(__('الرئيسية')); ?></a> / <?php echo e($cat['title']); ?>

    </nav>
    <h1 class="h3 fw-bold mb-1"><i class="bi <?php echo e($cat['icon']); ?> text-accent"></i> <?php echo e(__('أدوات :title', ['title' => $cat['title']])); ?></h1>
    <p class="text-secondary mb-0"><?php echo e($cat['desc']); ?></p>
</div>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
    <span class="small fw-bold text-secondary"><i class="bi bi-1-circle text-accent"></i> <?php echo e(__('اختر الأداة المطلوبة')); ?></span>
</div>
<div class="tool-grid mb-4" role="tablist" aria-label="<?php echo e(__('أدوات :title', ['title' => $cat['title']])); ?>">
    <?php $__currentLoopData = $tools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <button type="button" class="tool-chip <?php echo e($key === $firstKey ? 'active' : ''); ?>" data-tool="<?php echo e($key); ?>">
            <i class="bi <?php echo e($t['icon']); ?>"></i>
            <span><?php echo e($t['title']); ?></span>
        </button>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div>

<div class="row g-4 align-items-start">
    <div class="col-lg-5">
        <div class="panel">
            <div class="small fw-bold text-accent mb-2"><i class="bi bi-3-circle"></i> <?php echo e(__('اضبط الخيارات ونفّذ')); ?></div>
            <h2 class="h5 fw-bold mb-1" id="toolTitle"><?php echo e($firstTool['title']); ?></h2>
            <p class="small text-secondary mb-3" id="toolDesc"><?php echo e($firstTool['desc']); ?></p>

            <div class="small text-secondary mb-3" id="engineNote">
                <?php if($firstTool['engine'] === 'server'): ?>
                    <i class="bi bi-hdd-network"></i> <?php echo e(__('تُعالج على الخادم — الحد الأقصى :size', ['size' => \App\Services\ToolCatalog::formatBytes($maxUploadBytes)])); ?>

                <?php else: ?>
                    <i class="bi bi-shield-check"></i> <?php echo e(__('تُعالج مباشرة داخل متصفحك دون رفع الملف للخادم')); ?>

                <?php endif; ?>
            </div>

            <?php $__currentLoopData = $tools; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <form class="tool-form <?php echo e($key === $firstKey ? '' : 'd-none'); ?>" id="form-<?php echo e($key); ?>" data-tool="<?php echo e($key); ?>" enctype="multipart/form-data" novalidate>
                    <?php if(!empty($t['crop_ui'])): ?>
                        <div class="mb-3">
                            <label class="form-label d-block"><?php echo e(__('نسبة القص')); ?></label>
                            <div class="d-flex flex-wrap gap-1" id="cropRatios">
                                <?php $__currentLoopData = ['free' => __('حر'), '1:1' => __('1:1 مربع'), '16:9' => '16:9', '9:16' => '9:16', '4:3' => '4:3', '4:5' => '4:5']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rk => $rl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <button type="button" class="btn btn-light btn-sm <?php echo e($rk === 'free' ? 'active' : ''); ?>" data-crop-ratio="<?php echo e($rk); ?>"><?php echo e($rl); ?></button>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                        <div class="p-2 px-3 rounded-3 bg-light border d-flex justify-content-between align-items-center small mb-3">
                            <span class="text-secondary"><i class="bi bi-bounding-box text-accent"></i> <?php echo e(__('التحديد بالماوس')); ?></span>
                            <strong id="cropReadout" dir="ltr"><?php echo e(__('اسحب على الصورة')); ?></strong>
                        </div>
                        <details class="mb-3 small">
                            <summary class="text-secondary" style="cursor:pointer"><?php echo e(__('تعديل القياسات يدوياً (اختياري)')); ?></summary>
                            <div class="row g-2 mt-1">
                                <?php $__currentLoopData = $t['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="col-6">
                                        <?php if (isset($component)) { $__componentOriginald08ddcda9742f392ae06d374d1339897 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald08ddcda9742f392ae06d374d1339897 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tool-field','data' => ['field' => $field,'toolKey' => $key]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tool-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field),'tool-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $attributes = $__attributesOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__attributesOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $component = $__componentOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__componentOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </details>
                    <?php elseif(!empty($t['trim_ui'])): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center small mb-2">
                                <span class="text-secondary"><i class="bi bi-sliders text-accent"></i> <?php echo e(__('اسحب لتحديد المقطع')); ?></span>
                                <strong id="trimReadout-<?php echo e($key); ?>" dir="ltr"><?php echo e(__('ارفع ملفاً أولاً')); ?></strong>
                            </div>
                            <div class="trim-track" data-trim-track="<?php echo e($key); ?>" dir="ltr">
                                <div class="trim-range"></div>
                                <span class="trim-handle" data-trim-edge="start" title="<?php echo e(__('البداية')); ?>"></span>
                                <span class="trim-handle" data-trim-edge="end" title="<?php echo e(__('النهاية')); ?>"></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <button type="button" class="btn btn-light btn-sm" data-trim-preview="<?php echo e($key); ?>" disabled>
                                    <i class="bi bi-play-circle"></i> <?php echo e(__('معاينة المقطع المحدد')); ?>

                                </button>
                                <span class="small text-secondary" id="trimDur-<?php echo e($key); ?>"></span>
                            </div>
                        </div>
                        <?php $__currentLoopData = $t['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if($field['type'] === 'checkbox'): ?>
                                <?php if (isset($component)) { $__componentOriginald08ddcda9742f392ae06d374d1339897 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald08ddcda9742f392ae06d374d1339897 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tool-field','data' => ['field' => $field,'toolKey' => $key]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tool-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field),'tool-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $attributes = $__attributesOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__attributesOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $component = $__componentOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__componentOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <details class="mb-3 small">
                            <summary class="text-secondary" style="cursor:pointer"><?php echo e(__('تعديل الثواني يدوياً (اختياري)')); ?></summary>
                            <div class="row g-2 mt-1">
                                <?php $__currentLoopData = $t['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($field['type'] !== 'checkbox'): ?>
                                        <div class="col-6">
                                            <?php if (isset($component)) { $__componentOriginald08ddcda9742f392ae06d374d1339897 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald08ddcda9742f392ae06d374d1339897 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tool-field','data' => ['field' => $field,'toolKey' => $key]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tool-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field),'tool-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $attributes = $__attributesOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__attributesOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $component = $__componentOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__componentOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </details>
                    <?php else: ?>
                        <?php $__currentLoopData = $t['fields']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php if (isset($component)) { $__componentOriginald08ddcda9742f392ae06d374d1339897 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald08ddcda9742f392ae06d374d1339897 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.tool-field','data' => ['field' => $field,'toolKey' => $key]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tool-field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['field' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($field),'tool-key' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($key)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $attributes = $__attributesOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__attributesOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald08ddcda9742f392ae06d374d1339897)): ?>
<?php $component = $__componentOriginald08ddcda9742f392ae06d374d1339897; ?>
<?php unset($__componentOriginald08ddcda9742f392ae06d374d1339897); ?>
<?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>

                    <button type="submit" class="btn btn-accent w-100 py-2" disabled>
                        <i class="bi bi-play-fill"></i> <?php echo e(__('تنفيذ الأداة')); ?>

                    </button>
                </form>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <div id="runHint" class="small text-secondary text-center mt-2">
                <i class="bi bi-info-circle"></i> <?php echo e(__('ارفع ملفاً أولاً لتفعيل زر التنفيذ')); ?>

            </div>
            <div id="status" class="mt-3"></div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="panel mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small fw-bold text-accent"><i class="bi bi-2-circle"></i> <?php echo e(__('ارفع الملف للمعاينة')); ?></span>
                <button type="button" id="clearFilesBtn" class="btn btn-light btn-sm d-none">
                    <i class="bi bi-x-lg"></i> <?php echo e(__('مسح الملف')); ?>

                </button>
            </div>
            <label for="fileInput" id="dropzone" class="dropzone">
                <i class="bi bi-cloud-arrow-up display-6 text-accent"></i>
                <div class="fw-bold mt-2"><?php echo e(__('اسحب الملف هنا أو اضغط للاختيار')); ?></div>
                <div class="small text-secondary mt-1" id="acceptHint"></div>
                <input id="fileInput" type="file" class="d-none">
            </label>

            <div id="preview" class="mt-3 d-none"></div>
        </div>

        <div id="result" class="panel d-none"></div>
    </div>
</div>

<script>window.JU_PAGE = <?php echo json_encode($clientConfig, JSON_UNESCAPED_UNICODE, 512) ?>;</script>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    <?php $__currentLoopData = $scripts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <script src="<?php echo e(asset($s)); ?>"></script>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\CS\my-cs-projects\Ju file\resources\views/tools/category.blade.php ENDPATH**/ ?>