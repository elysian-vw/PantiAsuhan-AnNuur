<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['kategori_donasi', 'kategori_bantuan', 'jenis_kunjungan'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('nama')->unique();
                $t->text('deskripsi')->nullable();
                $t->timestamps();
            });
        }
        Schema::create('settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->longText('value')->nullable();
            $t->timestamps();
        });
        Schema::create('pengurus', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('jabatan');
            $t->string('foto')->nullable();
            $t->unsignedInteger('urutan')->default(0);
            $t->timestamps();
        });
        foreach (['kegiatan', 'berita'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('judul');
                $t->date('tanggal');
                $t->string('lokasi')->nullable();
                $t->text('deskripsi');
                $t->string('foto')->nullable();
                $t->string('status')->default('Draft');
                $t->timestamps();
            });
        }
        Schema::create('galeri', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kegiatan_id')->nullable()->constrained('kegiatan')->nullOnDelete();
            $t->string('judul');
            $t->string('foto');
            $t->text('deskripsi')->nullable();
            $t->timestamps();
        });
        Schema::create('kebutuhan', function (Blueprint $t) {
            $t->id();
            $t->foreignId('kategori_bantuan_id')->constrained('kategori_bantuan')->restrictOnDelete();
            $t->string('nama');
            $t->text('deskripsi')->nullable();
            $t->decimal('target', 14, 2);
            $t->decimal('terpenuhi', 14, 2)->default(0);
            $t->string('satuan', 40);
            $t->string('status')->default('Dibutuhkan');
            $t->timestamps();
        });
        Schema::create('donatur', function (Blueprint $t) {
            $t->id();
            $t->string('nama');
            $t->string('whatsapp', 20)->unique();
            $t->string('email')->nullable();
            $t->timestamps();
        });
        Schema::create('donasi', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('donatur_id')->constrained('donatur')->restrictOnDelete();
            $t->foreignId('kategori_donasi_id')->constrained('kategori_donasi')->restrictOnDelete();
            $t->string('nama');
            $t->string('whatsapp', 20);
            $t->string('email')->nullable();
            $t->decimal('nominal', 14, 2);
            $t->decimal('biaya', 14, 2)->nullable();
            $t->string('sumber_biaya')->nullable();
            $t->string('status')->default('Menunggu pembayaran');
            $t->timestamp('dibayar_pada')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('payment_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('donasi_id')->unique()->constrained('donasi')->restrictOnDelete();
            $t->string('order_id')->unique();
            $t->string('provider')->default('midtrans');
            $t->string('provider_id')->nullable();
            $t->string('snap_token')->nullable();
            $t->text('payment_url')->nullable();
            $t->string('status')->default('created');
            $t->string('metode')->nullable();
            $t->timestamps();
        });
        Schema::create('kunjungan', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('jenis_kunjungan_id')->constrained('jenis_kunjungan')->restrictOnDelete();
            $t->string('nama');
            $t->string('whatsapp', 20);
            $t->string('instansi')->nullable();
            $t->unsignedInteger('peserta');
            $t->date('tanggal')->index();
            $t->time('jam');
            $t->time('jam_selesai');
            $t->text('tujuan');
            $t->text('catatan')->nullable();
            $t->string('status')->default('Menunggu');
            $t->string('sumber')->default('online');
            $t->timestamp('konfirmasi_ulang_pada')->nullable();
            $t->timestamps();
        });
        Schema::create('bantuan', function (Blueprint $t) {
            $t->id();
            $t->string('kode')->unique();
            $t->string('token_hash', 64)->unique();
            $t->foreignId('donatur_id')->constrained('donatur')->restrictOnDelete();
            $t->foreignId('kunjungan_id')->nullable()->constrained('kunjungan')->restrictOnDelete();
            $t->string('nama');
            $t->string('whatsapp', 20);
            $t->text('catatan')->nullable();
            $t->date('tanggal_diterima')->nullable()->index();
            $t->string('status')->default('Diajukan');
            $t->timestamps();
        });
        Schema::create('bantuan_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('bantuan_id')->constrained('bantuan')->cascadeOnDelete();
            $t->foreignId('kategori_bantuan_id')->constrained('kategori_bantuan')->restrictOnDelete();
            $t->string('nama');
            $t->decimal('jumlah', 14, 2);
            $t->string('satuan', 40);
            $t->timestamps();
        });
        Schema::create('status_histories', function (Blueprint $t) {
            $t->id();
            $t->string('subject_type');
            $t->unsignedBigInteger('subject_id');
            $t->string('dari')->nullable();
            $t->string('ke');
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('sumber');
            $t->text('catatan')->nullable();
            $t->timestamps();
            $t->index(['subject_type', 'subject_id']);
        });
        Schema::create('whatsapp_logs', function (Blueprint $t) {
            $t->id();
            $t->string('dedup_key')->unique();
            $t->string('tujuan', 20);
            $t->string('jenis');
            $t->text('pesan');
            $t->string('status')->default('Antrean');
            $t->text('keterangan')->nullable();
            $t->string('provider_id')->nullable();
            $t->timestamp('dikirim_pada')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['whatsapp_logs', 'status_histories', 'bantuan_items', 'bantuan', 'kunjungan', 'payment_transactions', 'donasi', 'donatur', 'kebutuhan', 'galeri', 'berita', 'kegiatan', 'pengurus', 'settings', 'jenis_kunjungan', 'kategori_bantuan', 'kategori_donasi'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
