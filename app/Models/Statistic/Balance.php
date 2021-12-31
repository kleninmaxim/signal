<?php

namespace App\Models\Statistic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create($array)
 */
class Balance extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;
}
