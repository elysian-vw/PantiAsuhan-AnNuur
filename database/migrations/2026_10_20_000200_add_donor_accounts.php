<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('donatur')->index();
            $table->string('account_status')->default('aktif');
            $table->unsignedInteger('auth_version')->default(0);
        });
        // Every pre-existing account belongs to the existing administrator system.
        DB::table('users')->update(['role' => 'pengurus']);
        Schema::table('donatur', function (Blueprint $table) {
            $table->string('jenis')->default('biasa')->index();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->restrictOnDelete();
        });
        Schema::table('donasi', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('ownership_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ownership_verified_at')->nullable();
        });
        Schema::create('donor_account_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donatur_id')->constrained('donatur')->restrictOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('donor_account_audits');
        Schema::table('donasi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('owner_user_id');
            $table->dropConstrainedForeignId('ownership_verified_by');
            $table->dropColumn('ownership_verified_at');
        });
        Schema::table('donatur', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropUnique(['user_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('jenis');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'account_status', 'auth_version']);
        });
    }
};
