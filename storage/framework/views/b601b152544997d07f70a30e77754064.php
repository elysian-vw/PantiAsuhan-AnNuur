<?php if($recap = \App\Models\Setting::read('rekap_penerima')): ?>
<section class="profil-extra-card profil-extra-penerima" id="penerima-manfaat">
    <div class="profil-extra-head">
        <div class="profil-extra-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        </div>
        <div>
            <p class="profil-extra-eyebrow">PENERIMA MANFAAT</p>
            <h2 class="profil-extra-title">Pelayanan dalam panti &amp; nonpanti</h2>
            <?php if($period = \App\Models\Setting::read('periode_statistik')): ?>
            <p class="profil-extra-period"><?php echo e($period); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <p class="prose-text profil-extra-body"><?php echo e($recap); ?></p>
</section>
<?php endif; ?>
<?php $__currentLoopData = ['pendiri' => ['label'=>'Pendiri yayasan','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0M19.618 5.984A11.955 11.955 0 0112 4c-2.899 0-5.578.988-7.618 2.618"/>','cls'=>'profil-extra-pendiri'], 'tata_tertib' => ['label'=>'Tata tertib santri','icon'=>'<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>','cls'=>'profil-extra-tatatertib']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<?php if($content = \App\Models\Setting::read($key)): ?>
<section class="profil-extra-card <?php echo e($meta['cls']); ?>" id="<?php echo e($key); ?>">
    <div class="profil-extra-head">
        <div class="profil-extra-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $meta['icon']; ?></svg>
        </div>
        <h2 class="profil-extra-title"><?php echo e($meta['label']); ?></h2>
    </div>
    <p class="prose-text profil-extra-body"><?php echo e($content); ?></p>
</section>
<?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php if($source = \App\Models\Setting::read('sumber_profil')): ?>
<p class="help-text mt-6 profil-source-note">
    <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?php echo e($source); ?>

</p>
<?php endif; ?>
<?php /**PATH D:\apps\laragon\www\projectTA\resources\views/public/partials/photo-profile.blade.php ENDPATH**/ ?>