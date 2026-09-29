<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?php echo $__env->yieldContent('title','Dashboard'); ?> · Pengurus An-Nuur 2</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css','resources/js/app.js']); ?></head>
<body class="admin-body" x-data="{ menu: false }" @keydown.escape.window="menu = false">
<a class="skip-link" href="#main">Langsung ke isi</a>
<div class="admin-overlay" x-show="menu" x-cloak @click="menu = false" @touchmove.prevent></div>
<aside class="sidebar" :class="{ 'is-open': menu }">
    <div class="sidebar-top">
        <a class="brand" href="<?php echo e(route('admin.dashboard')); ?>">
            <span class="brand-mark" aria-hidden="true">ن</span>
            <span><strong>AN-NUUR 2</strong><small>RUANG PENGURUS</small></span>
        </a>
        <button type="button" class="sidebar-close-btn" @click="menu = false" aria-label="Tutup menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div class="sidebar-nav-wrap">
        <nav aria-label="Menu dashboard">
            
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="nav-item <?php if(request()->routeIs('admin.dashboard')): ?> active <?php endif; ?>" <?php if(request()->routeIs('admin.dashboard')): ?> aria-current="page" <?php endif; ?>>
                <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span>Dashboard</span>
            </a>

            
            <?php
                $isMasterActive = request()->is('admin/data/pengguna*') 
                    || request()->is('admin/data/kategori-donasi*') 
                    || request()->is('admin/data/kategori-bantuan*') 
                    || request()->is('admin/data/jenis-kunjungan*');
            ?>
            <div class="sidebar-dropdown" x-data="{ open: <?php echo e($isMasterActive ? 'true' : 'false'); ?> }">
                <button type="button" class="sidebar-dropdown-btn <?php if($isMasterActive): ?> is-active <?php endif; ?>" @click="open = !open" :aria-expanded="open">
                    <span class="sidebar-btn-content">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                        <span>Master Data</span>
                    </span>
                    <svg class="sidebar-chevron" :class="{ 'is-rotated': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-cloak>
                    <?php $__currentLoopData = ['pengguna','kategori-donasi','kategori-bantuan','jenis-kunjungan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.resource.index',$key)); ?>" <?php if(request()->is('admin/data/'.$key.'*')): ?> aria-current="page" <?php endif; ?>><?php echo e(\App\Support\AdminResources::get($key)[0]); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            
            <?php
                $isDonasiActive = request()->is('admin/data/donatur*') 
                    || request()->is('admin/transaksi/donasi*') 
                    || request()->is('admin/transaksi/bantuan*') 
                    || request()->is('admin/data/kebutuhan*');
            ?>
            <div class="sidebar-dropdown" x-data="{ open: <?php echo e($isDonasiActive ? 'true' : 'false'); ?> }">
                <button type="button" class="sidebar-dropdown-btn <?php if($isDonasiActive): ?> is-active <?php endif; ?>" @click="open = !open" :aria-expanded="open">
                    <span class="sidebar-btn-content">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Donasi & Bantuan</span>
                    </span>
                    <svg class="sidebar-chevron" :class="{ 'is-rotated': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-cloak>
                    <a href="<?php echo e(route('admin.resource.index','donatur')); ?>" <?php if(request()->is('admin/data/donatur*')): ?> aria-current="page" <?php endif; ?>>Data Donatur</a>
                    <a href="<?php echo e(route('admin.transaction.index','donasi')); ?>" <?php if(request()->is('admin/transaksi/donasi*')): ?> aria-current="page" <?php endif; ?>>Donasi Uang</a>
                    <a href="<?php echo e(route('admin.transaction.index','bantuan')); ?>" <?php if(request()->is('admin/transaksi/bantuan*')): ?> aria-current="page" <?php endif; ?>>Bantuan Barang</a>
                    <a href="<?php echo e(route('admin.resource.index','kebutuhan')); ?>" <?php if(request()->is('admin/data/kebutuhan*')): ?> aria-current="page" <?php endif; ?>>Kebutuhan Panti</a>
                </div>
            </div>

            
            <?php
                $isKunjunganActive = request()->is('admin/transaksi/kunjungan*') || request()->is('admin/kalender*') || request()->is('admin/tamu-langsung*');
            ?>
            <div class="sidebar-dropdown" x-data="{ open: <?php echo e($isKunjunganActive ? 'true' : 'false'); ?> }">
                <button type="button" class="sidebar-dropdown-btn <?php if($isKunjunganActive): ?> is-active <?php endif; ?>" @click="open = !open" :aria-expanded="open">
                    <span class="sidebar-btn-content">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span>Kunjungan</span>
                    </span>
                    <svg class="sidebar-chevron" :class="{ 'is-rotated': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-cloak>
                    <a href="<?php echo e(route('admin.transaction.index',['kind'=>'kunjungan','status'=>'Menunggu'])); ?>" <?php if(request()->fullUrl() == route('admin.transaction.index',['kind'=>'kunjungan','status'=>'Menunggu'])): ?> aria-current="page" <?php endif; ?>>Pengajuan Kunjungan</a>
                    <a href="<?php echo e(route('admin.calendar')); ?>" <?php if(request()->routeIs('admin.calendar')): ?> aria-current="page" <?php endif; ?>>Jadwal Kunjungan</a>
                    <a href="<?php echo e(route('admin.transaction.index',['kind'=>'kunjungan','status'=>'Hadir'])); ?>" <?php if(request()->fullUrl() == route('admin.transaction.index',['kind'=>'kunjungan','status'=>'Hadir'])): ?> aria-current="page" <?php endif; ?>>Buku Tamu</a>
                    <a href="<?php echo e(route('admin.transaction.index','kunjungan')); ?>" <?php if(request()->fullUrl() == route('admin.transaction.index','kunjungan')): ?> aria-current="page" <?php endif; ?>>Riwayat Kunjungan</a>
                </div>
            </div>

            
            <?php
                $isPublikasiActive = request()->is('admin/data/pengurus*') 
                    || request()->is('admin/data/kegiatan*') 
                    || request()->is('admin/data/berita*') 
                    || request()->is('admin/data/galeri*');
            ?>
            <div class="sidebar-dropdown" x-data="{ open: <?php echo e($isPublikasiActive ? 'true' : 'false'); ?> }">
                <button type="button" class="sidebar-dropdown-btn <?php if($isPublikasiActive): ?> is-active <?php endif; ?>" @click="open = !open" :aria-expanded="open">
                    <span class="sidebar-btn-content">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                        <span>Publikasi</span>
                    </span>
                    <svg class="sidebar-chevron" :class="{ 'is-rotated': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-cloak>
                    <a href="<?php echo e(route('admin.settings')); ?>#profil">Profil Panti</a>
                    <?php $__currentLoopData = ['pengurus','kegiatan','berita','galeri']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.resource.index',$key)); ?>" <?php if(request()->is('admin/data/'.$key.'*')): ?> aria-current="page" <?php endif; ?>><?php echo e(\App\Support\AdminResources::get($key)[0]); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            
            <?php
                $isLaporanActive = request()->is('admin/laporan*');
            ?>
            <div class="sidebar-dropdown" x-data="{ open: <?php echo e($isLaporanActive ? 'true' : 'false'); ?> }">
                <button type="button" class="sidebar-dropdown-btn <?php if($isLaporanActive): ?> is-active <?php endif; ?>" @click="open = !open" :aria-expanded="open">
                    <span class="sidebar-btn-content">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Laporan</span>
                    </span>
                    <svg class="sidebar-chevron" :class="{ 'is-rotated': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-cloak>
                    <?php $__currentLoopData = ['donasi','bantuan','kunjungan']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $kind): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e(route('admin.reports',$kind)); ?>" <?php if(request()->is('admin/laporan/'.$kind.'*')): ?> aria-current="page" <?php endif; ?>>Laporan <?php echo e(ucfirst($kind)); ?></a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            
            <?php
                $isPengaturanActive = request()->is('admin/pengaturan*');
            ?>
            <div class="sidebar-dropdown" x-data="{ open: <?php echo e($isPengaturanActive ? 'true' : 'false'); ?> }">
                <button type="button" class="sidebar-dropdown-btn <?php if($isPengaturanActive): ?> is-active <?php endif; ?>" @click="open = !open" :aria-expanded="open">
                    <span class="sidebar-btn-content">
                        <svg class="nav-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Pengaturan</span>
                    </span>
                    <svg class="sidebar-chevron" :class="{ 'is-rotated': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div class="sidebar-submenu" x-show="open" x-cloak>
                    <a href="<?php echo e(route('admin.settings')); ?>" <?php if(request()->routeIs('admin.settings')): ?> aria-current="page" <?php endif; ?>>Pengaturan Website</a>
                    <a href="<?php echo e(route('admin.settings')); ?>#integrasi">Payment & WhatsApp Gateway</a>
                </div>
            </div>
        </nav>
    </div>

    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-user-avatar"><?php echo e(strtoupper(substr(auth()->user()->name ?? 'P', 0, 1))); ?></div>
            <div class="sidebar-user-info">
                <strong><?php echo e(auth()->user()->name ?? 'Pengurus'); ?></strong>
                <small><?php echo e(auth()->user()->email ?? ''); ?></small>
            </div>
        </div>
        <div class="sidebar-footer-links">
            <a href="<?php echo e(route('home')); ?>" class="sidebar-footer-link" target="_blank" rel="noopener noreferrer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span>Lihat Web</span>
            </a>
            <form action="<?php echo e(route('logout')); ?>" method="post" class="flex-1">
                <?php echo csrf_field(); ?>
                <button type="submit" class="sidebar-footer-link danger w-full">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>
</aside>

<div class="admin-main">
    <header class="admin-header">
        <div class="flex items-center gap-2.5">
            <button class="menu-toggle" @click="menu = !menu" :aria-expanded="menu" aria-label="Menu dashboard">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
            <span class="admin-header-title">Dashboard pengurus</span>
        </div>
        <div class="admin-header-actions">
            <a href="<?php echo e(route('home')); ?>" class="header-action-btn" title="Kunjungi Website" target="_blank" rel="noopener noreferrer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                <span class="action-text">Website</span>
            </a>
            <form action="<?php echo e(route('logout')); ?>" method="post" class="inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="header-action-btn danger" title="Keluar dari akun">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="action-text">Keluar</span>
                </button>
            </form>
        </div>
    </header>

    <main id="main" class="admin-content">
        <p class="eyebrow">PANTI ASUHAN NU AN-NUUR 2</p>
        <h1 class="page-title"><?php echo $__env->yieldContent('title','Dashboard'); ?></h1>
        <?php if (isset($component)) { $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.flash','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('flash'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $attributes = $__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__attributesOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2)): ?>
<?php $component = $__componentOriginal5168fdb0c14fd91c6598264bc4be63f2; ?>
<?php unset($__componentOriginal5168fdb0c14fd91c6598264bc4be63f2); ?>
<?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
    </main>
</div>
</body></html>
<?php /**PATH D:\apps\laragon\www\projectTA\resources\views/layouts/admin.blade.php ENDPATH**/ ?>