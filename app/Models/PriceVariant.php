<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceVariant extends Model
{
    use HasFactory;
    protected $table = 'price_variants';
    protected $guarded = ['id'];
}
