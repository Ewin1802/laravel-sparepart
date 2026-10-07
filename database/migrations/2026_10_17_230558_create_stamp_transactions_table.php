<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamp_transactions', function (Blueprint $table) {
            $table->id();

            // =====================================================
            // MEMBER
            // =====================================================

            $table->foreignId('member_barcode_id')
                ->constrained('member_barcodes')
                ->cascadeOnDelete();

            // =====================================================
            // ORDER
            // =====================================================

            /*
             * Untuk stamp dari pembelian.
             *
             * NULL jika transaksi adalah redeem Mystery Box.
             */
            $table->foreignId('order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();

            // =====================================================
            // TYPE
            // =====================================================

            $table->enum('type', [
                'earn',
                'redeem',
            ]);

            // =====================================================
            // JUMLAH STAMP
            // =====================================================

            /*
             * Contoh:
             *
             * earn   = 1
             * redeem = 5
             *
             * Nilai selalu positif.
             * Arah perubahan ditentukan oleh type.
             */
            $table->unsignedInteger('amount');

            // =====================================================
            // KETERANGAN
            // =====================================================

            $table->string('note')->nullable();

            $table->timestamps();

            // =====================================================
            // INDEX
            // =====================================================

            $table->index([
                'member_barcode_id',
                'type',
            ]);

            /*
             * Satu order hanya boleh memberikan
             * stamp satu kali.
             */
            $table->unique([
                'member_barcode_id',
                'order_id',
                'type',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamp_transactions');
    }
};
