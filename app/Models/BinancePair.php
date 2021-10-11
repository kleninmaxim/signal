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
        if ($timeframe == '5m') return $this->binanceFiveMinuteCandles();
        elseif ($timeframe == '15m') return $this->binanceFifteenMinuteCandle();
        elseif ($timeframe == '30m') return $this->binanceThirtyMinuteCandle();
        elseif ($timeframe == '1h') return $this->binanceHourCandle();
        elseif ($timeframe == '4h') return $this->binanceFourHourCandle();
        elseif ($timeframe == '1d') return $this->binanceDayCandles();
        elseif ($timeframe == '1w') return $this->binanceWeekCandles();
        elseif ($timeframe == '1M') return $this->binanceMonthCandles();

        return false;
    }

    public function binanceFiveMinuteCandles()
    {
        return $this->hasMany(BinanceFiveMinuteCandle::class);
    }

    public function binanceFifteenMinuteCandle()
    {
        return $this->hasMany(BinanceFifteenMinuteCandle::class);
    }

    public function binanceThirtyMinuteCandle()
    {
        return $this->hasMany(BinanceThirtyMinuteCandle::class);
    }

    public function binanceHourCandle()
    {
        return $this->hasMany(BinanceHourCandle::class);
    }

    public function binanceFourHourCandle()
    {
        return $this->hasMany(BinanceFourHourCandle::class);
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
