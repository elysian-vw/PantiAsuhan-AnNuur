<?php
    $sortedItems = $items->sortBy('urutan')->values();
    $total = $sortedItems->count();

    if ($total > 0) {
        // 1. Root / Ketua
        $orgRoot = $sortedItems->firstWhere('jabatan', 'Ketua') ?? $sortedItems->first();
        $remaining = $sortedItems->reject(fn($i) => $i->id === $orgRoot->id)->values();

        // 2. BPH (Wakil Ketua, Sekretaris, Wakil Sekretaris, Bendahara, Wakil Bendahara)
        $isBph = function($item) {
            $j = strtolower(trim($item->jabatan));
            return str_contains($j, 'wakil ketua') 
                || str_contains($j, 'sekretaris') 
                || str_contains($j, 'bendahara');
        };

        $bphWakil = $remaining->filter(fn($i) => str_contains(strtolower($i->jabatan), 'wakil ketua'))->values();
        $bphSekretaris = $remaining->filter(fn($i) => str_contains(strtolower($i->jabatan), 'sekretaris'))->values();
        $bphBendahara = $remaining->filter(fn($i) => str_contains(strtolower($i->jabatan), 'bendahara'))->values();

        $bphAll = $remaining->filter($isBph)->values();

        // Fallback if no specific BPH keywords found
        if ($bphAll->isEmpty() && $remaining->count() > 0) {
            $bphAll = $remaining->take(min(3, $remaining->count()))->values();
            $bphWakil = $bphAll;
            $bphSekretaris = collect();
            $bphBendahara = collect();
        }

        $bphIds = $bphAll->pluck('id')->toArray();

        // 3. Divisi / Bidang & Pengasuh (Level 3 Branches)
        $branchMembers = $remaining->reject(fn($i) => in_array($i->id, $bphIds))->values();

        // Group by division/jabatan
        $branches = $branchMembers->groupBy(function($item) {
            $j = trim($item->jabatan);
            return $j;
        });
    }
?>

<?php if($total === 0): ?>
<div class="empty-state">
    <span class="empty-icon">
        <svg class="w-8 h-8 mx-auto text-emerald-800/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
        </svg>
    </span>
    Informasi struktur organisasi belum dipublikasikan.
</div>
<?php else: ?>
<div class="org-tree" aria-label="Struktur Organisasi Panti">

    
    <?php if($orgRoot): ?>
    <div class="org-level org-root-level">
        <div class="org-node org-root">
            <?php if($orgRoot->foto): ?>
                <img class="org-photo" src="<?php echo e(asset('storage/'.$orgRoot->foto)); ?>" alt="<?php echo e($orgRoot->nama); ?>" loading="lazy">
            <?php else: ?>
                <div class="org-photo-placeholder">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="org-info">
                <span class="org-role org-role-root"><?php echo e($orgRoot->jabatan); ?></span>
                <strong class="org-name"><?php echo e($orgRoot->nama); ?></strong>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <?php if($bphAll->count()): ?>
    <div class="org-stem org-stem-root" aria-hidden="true"></div>
    <div class="org-hbar" aria-hidden="true"></div>
    
    <div class="org-level org-bph-level">
        
        <?php if($bphWakil->count()): ?>
        <div class="org-col org-bph-col">
            <div class="org-stem org-stem-down" aria-hidden="true"></div>
            <div class="org-group-cards">
                <?php $__currentLoopData = $bphWakil; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="org-node org-l2">
                    <?php if($member->foto): ?>
                        <img class="org-photo org-photo-sm" src="<?php echo e(asset('storage/'.$member->foto)); ?>" alt="<?php echo e($member->nama); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="org-photo-placeholder org-photo-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="org-info">
                        <span class="org-role org-role-l2"><?php echo e($member->jabatan); ?></span>
                        <strong class="org-name"><?php echo e($member->nama); ?></strong>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <?php if($bphSekretaris->count()): ?>
        <div class="org-col org-bph-col">
            <div class="org-stem org-stem-down" aria-hidden="true"></div>
            <div class="org-group-cards">
                <?php $__currentLoopData = $bphSekretaris; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="org-node org-l2">
                    <?php if($member->foto): ?>
                        <img class="org-photo org-photo-sm" src="<?php echo e(asset('storage/'.$member->foto)); ?>" alt="<?php echo e($member->nama); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="org-photo-placeholder org-photo-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="org-info">
                        <span class="org-role org-role-l2"><?php echo e($member->jabatan); ?></span>
                        <strong class="org-name"><?php echo e($member->nama); ?></strong>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        
        <?php if($bphBendahara->count()): ?>
        <div class="org-col org-bph-col">
            <div class="org-stem org-stem-down" aria-hidden="true"></div>
            <div class="org-group-cards">
                <?php $__currentLoopData = $bphBendahara; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="org-node org-l2">
                    <?php if($member->foto): ?>
                        <img class="org-photo org-photo-sm" src="<?php echo e(asset('storage/'.$member->foto)); ?>" alt="<?php echo e($member->nama); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="org-photo-placeholder org-photo-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="org-info">
                        <span class="org-role org-role-l2"><?php echo e($member->jabatan); ?></span>
                        <strong class="org-name"><?php echo e($member->nama); ?></strong>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    
    <?php if($branches->count()): ?>
    <div class="org-stem org-stem-trunk" aria-hidden="true"></div>
    <div class="org-divider-node">
        <span class="org-divider-badge">
            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Bidang & Divisi Operasional
        </span>
    </div>
    <div class="org-stem org-stem-down" aria-hidden="true"></div>
    <div class="org-hbar org-hbar-branches" aria-hidden="true"></div>

    <div class="org-branches-grid">
        <?php $__currentLoopData = $branches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $title => $members): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="org-branch-card">
            <div class="org-stem-branch-in" aria-hidden="true"></div>
            <div class="org-branch-header">
                <span class="org-branch-dot" aria-hidden="true"></span>
                <h3 class="org-branch-title"><?php echo e($title); ?></h3>
            </div>
            <div class="org-branch-members">
                <?php $__currentLoopData = $members; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $member): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="org-branch-member">
                    <?php if($member->foto): ?>
                        <img class="org-photo org-photo-xs" src="<?php echo e(asset('storage/'.$member->foto)); ?>" alt="<?php echo e($member->nama); ?>" loading="lazy">
                    <?php else: ?>
                        <div class="org-photo-placeholder org-photo-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                    <?php endif; ?>
                    <div class="org-info-inline">
                        <strong class="org-name-sm"><?php echo e($member->nama); ?></strong>
                        <span class="org-role-xs"><?php echo e($member->jabatan); ?></span>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>
<?php /**PATH D:\apps\laragon\www\projectTA\resources\views/public/partials/org-tree.blade.php ENDPATH**/ ?>