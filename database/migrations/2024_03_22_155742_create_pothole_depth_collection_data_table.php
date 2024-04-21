<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pothole_depth_collection_data', function (Blueprint $table) {
            $table->id();
            $table->string('pothole_depth_code', 255);
            $table->string('pothole_depth_1', 255)->nullable();
            $table->string('pothole_depth_2', 255)->nullable();
            $table->string('pothole_depth_3', 255)->nullable();
            $table->string('pothole_depth_4', 255)->nullable();
            $table->string('pothole_depth_latitude', 255)->nullable();
            $table->string('pothole_depth_longitude', 255)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pothole_depth_collection_data');
    }
};
