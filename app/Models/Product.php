<?php

namespace App\Models;

use App\Support\Barcode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'code',
        'barcode',
        'name',
        'description',
        'price',
        'cost_price',
        'image',
        'stock',
        'status',
        'is_favorite',
        'base_unit',
    ];

        /**
     * Produk tanpa barcode otomatis mendapat kode toko EAN-13 berawalan "20".
     * Berlaku dari form, import Excel, seeder, maupun API.
     */
    protected static function booted(): void
    {
        // produk baru: id baru ada setelah tersimpan
        static::created(function (Product $product) {
            if (blank($product->barcode)) {
                $product->barcode = Barcode::internal($product->id);
                $product->saveQuietly();
            }
        });

        // produk lama yang disimpan dengan barcode kosong → isi kode toko
        static::saving(function (Product $product) {
            if ($product->exists && blank($product->barcode)) {
                $product->barcode = Barcode::internal($product->id);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Gambar SVG barcode produk ini (kosong kalau tidak ada). */
    public function barcodeSvg(float $height = 40): string
    {
        return $this->barcode ? Barcode::svg($this->barcode, $height) : '';
    }
}
