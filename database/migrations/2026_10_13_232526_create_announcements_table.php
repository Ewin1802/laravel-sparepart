<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('message');
            $table->string('image')->nullable();

            // Kalau nonaktif, gak ditampilkan di app member dan
            // gak kirim notifikasi.
            $table->boolean('is_active')->default(true);

            // Dipakai untuk urutan tampil (terbaru dulu) dan bisa
            // dipakai kalau nanti mau jadwalkan tayang di masa depan.
            $table->timestamp('published_at')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
