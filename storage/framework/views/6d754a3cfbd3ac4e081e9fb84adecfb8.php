<?php
$guides = [
    'donasi' => ['label'=>'Donasi uang', 'title'=>'Pilih nominal, mulai kebaikan.', 'description'=>'Mulai dari Rp5.000, setiap donasi menjadi bentuk kepedulian Anda untuk panti.', 'steps'=>['Pilih kategori dan nominal donasi.', 'Isi nama serta nomor WhatsApp Anda.', 'Lanjutkan pembayaran dan lihat status pada tautan pribadi.'], 'action'=>'Isi formulir donasi', 'note'=>'Pembayaran saat ini dalam mode pengujian sandbox.'],
    'bantuan' => ['label'=>'Bantuan barang', 'title'=>'Barang yang dibutuhkan, perhatian yang berarti.', 'description'=>'Ajukan bantuan berupa barang. Anda dapat mencantumkan beberapa jenis barang dalam satu pengajuan.', 'steps'=>['Isi identitas dan rincian barang.', 'Tunggu konfirmasi dari pengurus.', 'Serahkan barang sesuai kesepakatan dengan pengurus.'], 'action'=>'Ajukan bantuan barang', 'note'=>'Tanggal penerimaan dicatat setelah barang diterima oleh panti.'],
    'kunjungan' => ['label'=>'Kunjungan', 'title'=>'Hadir langsung, dekatkan silaturahmi.', 'description'=>'Rencanakan waktu untuk bertemu dan berkegiatan bersama, sesuai persetujuan pengurus.', 'steps'=>['Pilih tanggal dan jam antara 07.00–21.00 WIB.', 'Isi jumlah peserta serta tujuan kunjungan.', 'Tunggu persetujuan jadwal melalui WhatsApp.'], 'action'=>'Rencanakan kunjungan', 'note'=>'Jika jadwal berubah, pengurus akan meminta persetujuan Anda kembali.'],
];
?>
<section id="panduan-berbagi" class="giving-guide section" x-data="givingGuide">
<div class="container guide-grid">
<div class="guide-intro"><p class="eyebrow">SATU NIAT, BANYAK CARA</p><h2>Temukan cara<br>berbagi Anda.</h2><p>Pilih bentuk kepedulian untuk melihat langkah selanjutnya. Semuanya dapat dilakukan tanpa membuat akun.</p>
<div class="guide-tabs" role="tablist" aria-label="Pilih cara berbagi" x-cloak>
<?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<button type="button" id="tab-<?php echo e($key); ?>" role="tab" aria-controls="guide-<?php echo e($key); ?>" :aria-selected="active==='<?php echo e($key); ?>'" :tabindex="active==='<?php echo e($key); ?>'?0:-1" @click="active='<?php echo e($key); ?>'" @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)" @keydown.home.prevent="select('donasi')" @keydown.end.prevent="select('kunjungan')"><span><?php echo e($guide['label']); ?></span><span class="guide-tab-arrow" aria-hidden="true">→</span></button>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div></div>
<div class="guide-panels">
<?php $__currentLoopData = $guides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$guide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<section id="guide-<?php echo e($key); ?>" class="guide-panel" role="tabpanel" aria-label="<?php echo e($guide['label']); ?>" tabindex="0" x-show="active==='<?php echo e($key); ?>'">
<span class="guide-kicker"><?php echo e($guide['label']); ?></span><h3><?php echo e($guide['title']); ?></h3><p><?php echo e($guide['description']); ?></p>
<ol class="guide-steps"><?php $__currentLoopData = $guide['steps']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><span aria-hidden="true">0<?php echo e($loop->iteration); ?></span><p><?php echo e($step); ?></p></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ol>
<a class="button" href="<?php echo e(route('submission.form',$key)); ?>"><?php echo e($guide['action']); ?> <span aria-hidden="true">→</span></a><p class="guide-note"><?php echo e($guide['note']); ?></p>
</section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div></div></section>

<?php /**PATH D:\apps\laragon\www\projectTA\resources\views/public/partials/giving-guide.blade.php ENDPATH**/ ?>