<?php if(session('success')): ?><div class="notice success" role="status"><?php echo e(session('success')); ?></div><?php endif; ?>
<?php if(session('warning')): ?><div class="notice" role="status"><?php echo e(session('warning')); ?></div><?php endif; ?>
<?php if($errors->any()): ?>
<div class="notice error" role="alert"><strong>Periksa kembali isian berikut.</strong><ul class="mt-2 list-disc pl-5"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
<?php endif; ?>
<?php /**PATH D:\apps\laragon\www\projectTA\resources\views/components/flash.blade.php ENDPATH**/ ?>