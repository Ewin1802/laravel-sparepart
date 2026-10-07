<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampTransaction extends Model
{
    protected $fillable = [
        'member_barcode_id',
        'order_id',
        'type',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    // =========================================================
    // MEMBER
    // =========================================================

    public function memberBarcode(): BelongsTo
    {
        return $this->belongsTo(
            MemberBarcode::class
        );
    }

    // =========================================================
    // ORDER
    // =========================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(
            Order::class
        );
    }
}
