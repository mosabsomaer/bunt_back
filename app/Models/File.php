<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $fillable = [
        'file_name',
        'PageCount',
        'JobID',
        'path',
        'copies',
        'color_mode',
        'order_id',
        'price',
    ];


    public function order()
    {
    return $this->belongsTo(Order::class, 'id');
    }
}
