<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [

        // =====================================================
        // IDEMPOTENCY
        // =====================================================

        'client_order_id',

        // =====================================================
        // MEMBER
        // =====================================================

        'member_code',

        // =====================================================
        // PAYMENT
        // =====================================================

        'payment_amount',
        'sub_total',
        'tax',
        'discount',
        'discount_amount',
        'service_charge',
        'total',
        'payment_method',
        'total_item',

        // =====================================================
        // TABLE / CUSTOMER
        // =====================================================

        'table_number',
        'customer_name',

        // =====================================================
        // STATUS
        // =====================================================

        'status',

        // =====================================================
        // CASHIER
        // =====================================================

        'id_kasir',
        'nama_kasir',

        // =====================================================
        // TRANSACTION
        // =====================================================

        'transaction_time',
    ];

    protected $casts = [

        'payment_amount' => 'integer',

        'sub_total' => 'integer',

        'tax' => 'integer',

        'discount' => 'integer',

        'discount_amount' => 'decimal:2',

        'service_charge' => 'integer',

        'total' => 'integer',

        /*
        |--------------------------------------------------------------------------
        | TOTAL ITEM
        |--------------------------------------------------------------------------
        |
        | DIUBAH dari 'integer' -> 'decimal:2'. Sebelumnya, setiap kali
        | $order->total_item diakses (termasuk di index.blade.php),
        | Eloquent otomatis MEMBULATKAN nilai desimal (2.90 -> 2)
        | sebelum ditampilkan — walau di database sudah benar tersimpan
        | 2.90. Ini akar masalah "total_item selalu kebaca bulat" untuk
        | produk yang dijual per ons/gram.
        |
        */

        'total_item' => 'decimal:2',

        'table_number' => 'integer',

        'id_kasir' => 'integer',
    ];

    // =========================================================
    // ORDER ITEMS
    // =========================================================

    public function orderItems(): HasMany
    {
        return $this->hasMany(
            OrderItem::class,
            'order_id'
        );
    }
}
