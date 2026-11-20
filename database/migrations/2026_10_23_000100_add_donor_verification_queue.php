<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donatur', function (Blueprint $table) {
            $table->string('status_verifikasi')->default('none')->index();
            $table->timestamp('diajukan_pada')->nullable();
            $table->foreignId('diputuskan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diputuskan_pada')->nullable();
            $table->text('alasan_penolakan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('donatur', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diputuskan_oleh');
            $table->dropIndex(['status_verifikasi']);
            $table->dropColumn([
                'status_verifikasi',
                'diajukan_pada',
                'diputuskan_pada',
                'alasan_penolakan',
            ]);
        });
    }
};

