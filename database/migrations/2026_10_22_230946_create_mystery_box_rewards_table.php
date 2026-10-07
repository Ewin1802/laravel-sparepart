<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mystery_box_rewards', function (Blueprint $table) {
            $table->id();

            // Member yang mendapatkan reward
            $table->foreignId('member_barcode_id')
                ->constrained('member_barcodes')
                ->cascadeOnDelete();

            // Produk yang didapat
            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            // Snapshot nama produk
            $table->string('product_name');

            // Snapshot harga normal
            $table->decimal('product_price', 12, 2)
                ->default(0);

            // Status reward
            $table->enum('status', [
                'available',
                'redeemed',
            ])->default('available');

            $table->timestamp('redeemed_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'member_barcode_id',
                'status',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mystery_box_rewards');
    }
};
