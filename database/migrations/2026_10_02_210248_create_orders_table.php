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
        Schema::create('orders', function (Blueprint $table) {

            // =====================================================
            // PRIMARY KEY
            // =====================================================

            $table->id();

            // =====================================================
            // IDEMPOTENCY / CLIENT ORDER ID
            // =====================================================
            //
            // UUID dibuat oleh aplikasi Flutter SEBELUM pembayaran.
            //
            // Satu UUID = satu transaksi.
            //
            // UNIQUE sangat penting untuk mencegah double order.
            //

            $table->uuid('client_order_id')->unique();

            // =====================================================
            // MEMBER
            // =====================================================

            $table->string('member_code', 100)
                ->nullable()
                ->index();

            // =====================================================
            // PAYMENT
            // =====================================================

            $table->integer('payment_amount');

            $table->integer('sub_total');

            $table->integer('tax');

            $table->integer('discount');

            $table->decimal(
                'discount_amount',
                10,
                2
            )->default(0.00);

            $table->integer('service_charge');

            $table->integer('total');

            $table->string('payment_method');

            $table->decimal('total_item', 10, 2);

            // =====================================================
            // TABLE / CUSTOMER
            // =====================================================
            $table->integer('table_number')->nullable();
            $table->string('customer_name')->nullable();

            // =====================================================
            // STATUS
            // =====================================================
            $table->string('status')->nullable();

            // =====================================================
            // CASHIER
            // =====================================================
            $table->integer('id_kasir');
            $table->string('nama_kasir');

            // =====================================================
            // TRANSACTION TIME
            // =====================================================

            /*
             * Tetap menggunakan string karena aplikasi Anda
             * saat ini sudah mengirim transaction_time dalam
             * format tertentu.
             */
            $table->string('transaction_time');
            $table->timestamps();
            $table->index('transaction_time');
            $table->index(['id_kasir','transaction_time',]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
