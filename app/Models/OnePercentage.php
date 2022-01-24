<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static where(string $string, string $pair)
 * @method static create(array $array)
 */
class OnePercentage extends Model
{
    use HasFactory;

    protected $guarded = [];

    public $timestamps = false;
}
