<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TinkoffTicker extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;

    public function getCandles($timeframe)
    {
        if ($timeframe == '1d') return $this->tinkoffDayCandles();
        elseif ($timeframe == '1w') return $this->tinkoffWeekCandles();
        elseif ($timeframe == '1M') return $this->tinkoffMonthCandles();

        return null;
    }

    public function tinkoffDayCandles()
    {
        return $this->hasMany(TinkoffDayCandle::class);
    }

    public function tinkoffWeekCandles()
    {
        return $this->hasMany(TinkoffWeekCandle::class);
    }

    public function tinkoffMonthCandles()
    {
        return $this->hasMany(TinkoffMonthCandle::class);
    }
}
