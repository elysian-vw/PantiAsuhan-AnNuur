<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('saldo_pembukaan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_mulai');
            $table->decimal('nominal', 14, 2);
            $table->string('keterangan')->nullable();
            $table->foreignId('disahkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->date('tanggal')->index();
            $table->foreignId('kategori_pengeluaran_id')->constrained('kategori_pengeluaran')->restrictOnDelete();
            $table->string('keperluan');
            $table->decimal('nominal', 14, 2);
            $table->string('penerima')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status')->default('Draft')->index();
            $table->string('bukti_path')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('donasi', function (Blueprint $table) {
            $table->string('sumber')->default('online')->index();
            $table->string('metode')->default('midtrans')->index();
            $table->string('referensi_manual')->nullable();
            $table->string('bukti_transfer')->nullable();
            $table->foreignId('pencatat_user_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('donasi', function (Blueprint $table) {
            $table->dropIndex(['sumber']);
            $table->dropIndex(['metode']);
            $table->dropConstrainedForeignId('pencatat_user_id');
            $table->dropColumn(['sumber', 'metode', 'referensi_manual', 'bukti_transfer']);
        });

        Schema::dropIfExists('pengeluaran');
        Schema::dropIfExists('saldo_pembukaan');
        Schema::dropIfExists('kategori_pengeluaran');
    }
};
