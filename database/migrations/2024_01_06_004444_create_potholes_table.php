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
        Schema::create('potholes', function (Blueprint $table) {
            $table->id();
            $table->string('pothole_code', 255);
            $table->string('pothole_detection_code', 255)->nullable(); // detection_code from detections table
            $table->string('pothole_object_number', 255)->nullable(); // dalam 1 deteksi foto bisa jadi ada beberapa pothole
            $table->string('pothole_type', 255)->nullable(); // SERIOUSLY_DAMAGED or LESS_DAMAGED
            $table->string('pothole_length', 255)->nullable();
            $table->string('pothole_width', 255)->nullable();
            $table->string('pothole_height', 255)->nullable(); // info => pothole depth
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('potholes');
    }
};
