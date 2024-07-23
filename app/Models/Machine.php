<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Machine extends Model
{
    use HasFactory;
    protected $fillable = [
        'status',
        'name',
        'paper',
        'coins',
        'ink',
        'last_ping'
    ];


    protected static function boot()
    {
        parent::boot();
        static::retrieved(function (Machine $machine) {
            // now $machine will definitely be a model instance
            if (Carbon::parse($machine->last_ping)->diffInMinutes(Carbon::now()) > 1) {
                $machine->status = 'Lost Connection';
            }
            else{
                $machine->status = 'Active';
            }
        });
    }
}
