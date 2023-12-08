<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPriceVariantCategory extends Model
{
    use HasFactory;
    protected $table = 'product_price_variant_categories';
    protected $guarded = ['id'];
}
