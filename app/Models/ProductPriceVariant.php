<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceVariant extends Model
{
    use HasFactory;
    protected $table = 'product_price_variants';
    protected $guarded = ['id'];
}
