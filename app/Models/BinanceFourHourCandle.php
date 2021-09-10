<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinanceFourHourCandle extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;
}
