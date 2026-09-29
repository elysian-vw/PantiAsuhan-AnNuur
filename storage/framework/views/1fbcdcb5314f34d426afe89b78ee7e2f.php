<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['name', 'label', 'type' => 'text', 'value' => '', 'required' => false]));

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

foreach (array_filter((['name', 'label', 'type' => 'text', 'value' => '', 'required' => false]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<div class="field">
<label for="<?php echo e($name); ?>"><?php echo e($label); ?> <?php if($required): ?><span aria-hidden="true">*</span><?php endif; ?></label>
<?php if($type === 'textarea'): ?>
<textarea id="<?php echo e($name); ?>" name="<?php echo e($name); ?>" rows="5" <?php echo e($required ? 'required' : ''); ?> <?php echo e($attributes); ?>><?php echo e(old($name, $value)); ?></textarea>
<?php else: ?>
<input id="<?php echo e($name); ?>" name="<?php echo e($name); ?>" type="<?php echo e($type); ?>" value="<?php echo e($type === 'password' ? '' : old($name, $value)); ?>" <?php echo e($required ? 'required' : ''); ?> <?php echo e($attributes); ?>>
<?php endif; ?>
<?php $__errorArgs = [$name];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><span class="field-error"><?php echo e($message); ?></span><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH D:\apps\laragon\www\projectTA\resources\views/components/field.blade.php ENDPATH**/ ?>