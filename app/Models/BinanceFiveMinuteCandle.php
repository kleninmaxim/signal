<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinanceFiveMinuteCandle extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;
}
