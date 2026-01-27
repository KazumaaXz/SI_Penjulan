<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'booking_trx_id',
        'city',
        'address',
        'post_code',
        'quantity',
        'sub_total_amount',
        'grand_total_amount',
        'discount_amount',
        'is_paid',
        'produk_id',
        'produk_size',
        'promo_code_id',
        'proof',
    ];

    public static function generateUniqueTrxId()
    {
        $prefix = 'TJH';
        do {
            $randomString = $prefix . mt_rand(10001, 99999);
        } while (self::where('booking_trx_id', $randomString)->exists());

        return $randomString;
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }
}
