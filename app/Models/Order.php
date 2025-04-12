<?php

namespace App\Models;

use App\Types\OrderStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'status',
        'number_pages'
    ];

    protected $casts = [
        'status' => OrderStatusEnum::class
    ];

    public function file()
    {
    return $this->hasMany(File::class);
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->order_id = str_pad(strval(random_int(1, 999999)), 6, '0', STR_PAD_LEFT);
        });
    }
}
