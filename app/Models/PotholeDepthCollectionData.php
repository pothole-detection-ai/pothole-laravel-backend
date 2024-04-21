<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PotholeDepthCollectionData extends Model
{
    use HasFactory;

    protected $guarded = ['id'];
    protected $table = 'pothole_depth_collection_data';
}
