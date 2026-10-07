<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('member_barcodes', function (Blueprint $table) {
            $table->id();

            // =====================================================
            // MEMBER
            // =====================================================

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // =====================================================
            // DATA MEMBER
            // =====================================================
            $table->string('phone_number', 20)->unique();
            $table->date('birth_date')->nullable();

            // =====================================================
            // BARCODE
            // =====================================================

            $table->string('code', 100)->unique();

            // =====================================================
            // DISCOUNT MEMBER
            // =====================================================

            $table->enum('discount_type', [
                'percentage',
                'fixed',
            ])->default('percentage');

            /*
             * percentage = 10 berarti 10%
             * fixed      = 10000 berarti Rp10.000
             */
            $table->decimal(
                'discount_value',
                12,
                2
            )->default(0);

            // =====================================================
            // STAMP KUNJUNGAN
            // =====================================================

            /*
             * Jumlah stamp yang sudah dikumpulkan
             *
             * Contoh:
             * 0 / 5
             * 1 / 5
             * 2 / 5
             * ...
             * 5 / 5
             */
            $table->unsignedInteger('stamp_count')
                ->default(0);

            /*
             * Target stamp untuk mendapatkan
             * Mystery Box.
             *
             * Default:
             * 5 kunjungan = 1 Mystery Box
             */
            $table->unsignedInteger('stamp_target')
                ->default(10);

            // =====================================================
            // STATUS BARCODE
            // =====================================================

            $table->boolean('is_active')
                ->default(true);

            // =====================================================
            // MASA BERLAKU
            // =====================================================

            $table->timestamp('valid_from')
                ->nullable();

            $table->timestamp('valid_until')
                ->nullable();

            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================

            $table->index([
                'user_id',
                'is_active'
            ]);

            $table->index('birth_date');

            $table->index('stamp_count');
            $table->timestamp('synced_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_barcodes');
    }
};
