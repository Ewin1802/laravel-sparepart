<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderPayment extends Model
{
    protected $fillable = [
        'order_id',
        'client_payment_id',
        'amount',
        'payment_method',
        'paid_at',
        'note',
        'source',
        'id_kasir',
        'nama_kasir',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'id_kasir' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
