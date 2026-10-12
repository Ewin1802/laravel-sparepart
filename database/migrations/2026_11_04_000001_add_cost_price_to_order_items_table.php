<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga beli (modal) per baris penjualan.
 *
 * Diisi otomatis dari products.cost_price saat transaksi dibuat, sehingga
 * laba tetap benar walaupun harga beli produk berubah di kemudian hari.
 * Penjualan lama diisi dari harga beli produk yang tercatat saat migrasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('cost_price', 12, 2)->nullable()->after('price');
        });

        // isi penjualan lama dengan harga beli produk saat ini
        DB::statement('
            UPDATE order_items
            INNER JOIN products ON products.id = order_items.product_id
            SET order_items.cost_price = products.cost_price
            WHERE order_items.cost_price IS NULL
        ');
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
