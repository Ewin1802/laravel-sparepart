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

            // Cash | Transfer | Tempo
            $table->string('payment_method');

            // =====================================================
            // PIUTANG (penjualan Tempo)
            // =====================================================
            //
            // payment_status : paid | partial | unpaid
            // paid_amount    : total uang yang sudah diterima.
            //                  Non-tempo = total. Tempo = DP + cicilan
            //                  (jumlah dari tabel order_payments).
            // due_date       : jatuh tempo pelunasan (khusus Tempo)
            // paid_off_at    : kapan nota tempo lunas
            //

            $table->string('payment_status', 20)->default('paid')->index();
            $table->integer('paid_amount')->default(0);
            $table->date('due_date')->nullable()->index();
            $table->timestamp('paid_off_at')->nullable();

            $table->decimal('total_item', 10, 2);

            // =====================================================
            // TABLE / CUSTOMER
            // =====================================================
            $table->integer('table_number')->nullable();
            $table->string('customer_name')->nullable();

            // nomor HP untuk penagihan piutang (opsional)
            $table->string('customer_phone', 30)->nullable();

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
            $table->index(['id_kasir', 'transaction_time']);

            // daftar piutang: filter metode + status
            $table->index(['payment_method', 'payment_status']);
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
