<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_device_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Token FCM dari device Flutter. UNIQUE penting supaya
            // satu device (token) gak kedaftar dobel kalau logout-login
            // beda akun di HP yang sama — updateOrCreate() di controller
            // akan mengganti kepemilikan token ke user yang login terakhir.
            $table->string('token')->unique();

            $table->string('platform', 20)->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_device_tokens');
    }
};
