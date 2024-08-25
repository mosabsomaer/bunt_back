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
            if (Carbon::parse($machine->last_ping)->diffInMinutes(Carbon::now()) > 1) {
                $machine->status = 'Lost Connection';
            } else {
                $machine->status = ($machine->paper < 250 || $machine->ink < 25 || $machine->coins >= 400) ? 'Resource Alert' : 'Active';
            }
        });
    }
}
