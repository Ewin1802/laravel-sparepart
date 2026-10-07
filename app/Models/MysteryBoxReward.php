<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MysteryBoxReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_barcode_id',
        'product_id',
        'product_name',
        'product_price',
        'status',
        'redeemed_at',
    ];

    protected $casts = [
        'product_price' => 'decimal:2',
        'redeemed_at' => 'datetime',
    ];

    public function memberBarcode()
    {
        return $this->belongsTo(
            MemberBarcode::class,
            'member_barcode_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(
            Product::class,
            'product_id'
        );
    }
}
