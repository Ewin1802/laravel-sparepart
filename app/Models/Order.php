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
        // PIUTANG (penjualan tempo)
        // =====================================================

        'payment_status',
        'paid_amount',
        'due_date',
        'customer_phone',
        'paid_off_at',

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

        'paid_amount' => 'integer',

        'due_date' => 'date:Y-m-d',

        'paid_off_at' => 'datetime',
    ];

    /** Metode bayar untuk penjualan tempo (hutang pelanggan). */
    public const METHOD_CREDIT = 'Tempo';

    protected $appends = ['remaining_amount'];

    // =========================================================
    // PEMBAYARAN (CICILAN) NOTA TEMPO
    // =========================================================

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class, 'order_id')->orderBy('paid_at');
    }

    /** Sisa yang belum dibayar (0 kalau lunas / lebih bayar). */
    public function getRemainingAmountAttribute(): int
    {
        return max(0, (int) $this->total - (int) $this->paid_amount);
    }

    public function isCredit(): bool
    {
        return strcasecmp((string) $this->payment_method, self::METHOD_CREDIT) === 0;
    }

    public function scopeCredit($query)
    {
        return $query->whereRaw('LOWER(payment_method) = ?', [strtolower(self::METHOD_CREDIT)]);
    }

    public function scopeOpenReceivable($query)
    {
        return $query->whereIn('payment_status', ['unpaid', 'partial']);
    }

    /**
     * Hitung ulang paid_amount & payment_status dari tabel order_payments.
     * Order non-tempo (Cash/Transfer) selalu lunas sebesar totalnya.
     */
    public function syncPaymentState(): void
    {
        if (! $this->isCredit()) {
            $this->forceFill([
                'paid_amount' => (int) $this->total,
                'payment_status' => 'paid',
                'paid_off_at' => $this->paid_off_at ?? now(),
            ])->saveQuietly();

            return;
        }

        $paid = (int) $this->payments()->sum('amount');
        $total = (int) $this->total;

        $status = $paid <= 0 ? 'unpaid' : ($paid >= $total ? 'paid' : 'partial');

        $this->forceFill([
            'paid_amount' => $paid,
            'payment_status' => $status,
            'paid_off_at' => $status === 'paid'
                ? ($this->paid_off_at ?? $this->payments()->max('paid_at') ?? now())
                : null,
        ])->saveQuietly();
    }

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
