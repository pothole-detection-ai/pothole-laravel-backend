<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaklarCollectionData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'saklar_collection_data';
}
