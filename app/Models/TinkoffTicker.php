<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TinkoffTicker extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function tinkoff_day_candles()
    {
        return $this->hasMany(TinkoffDayCandle::class);
    }

    public function tinkoff_four_hour_candles()
    {
        return $this->hasMany(TinkoffFourHourCandle::class);
    }

    public function tinkoff_hour_candles()
    {
        return $this->hasMany(TinkoffHourCandle::class);
    }
}
