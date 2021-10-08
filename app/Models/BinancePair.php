<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinancePair extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function getCandles($timeframe)
    {
        if ($timeframe == '1d') return $this->binanceDayCandles();
        elseif ($timeframe == '1w') return $this->binanceWeekCandles();
        elseif ($timeframe == '1M') return $this->binanceMonthCandles();
    }

    public function binanceDayCandles()
    {
        return $this->hasMany(BinanceDayCandle::class);
    }

    public function binanceWeekCandles()
    {
        return $this->hasMany(BinanceWeekCandle::class);
    }

    public function binanceMonthCandles()
    {
        return $this->hasMany(BinanceMonthCandle::class);
    }

}
