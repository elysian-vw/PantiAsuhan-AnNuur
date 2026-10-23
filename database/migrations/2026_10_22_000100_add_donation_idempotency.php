<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donasi', function (Blueprint $table) {
            $table->string('idempotency_key', 64)->nullable();
            $table->char('request_hash', 64)->nullable();
            $table->unique(['owner_user_id', 'idempotency_key'], 'donasi_owner_request_unique');
        });
    }

    public function down(): void
    {
        Schema::table('donasi', function (Blueprint $table) {
            $table->dropUnique('donasi_owner_request_unique');
            $table->dropColumn(['idempotency_key', 'request_hash']);
        });
    }
};
