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
        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->string('detection_code', 255);
            $table->string('detection_latitude', 255);
            $table->string('detection_longitude', 255);
            $table->string('detection_image', 255);
            $table->string('detection_type', 255); // CAPTURE or REALTIME
            $table->string('detection_algorithm', 255); // YOLOV8-DEPTHINFO or MASKRCNN-SONARROBOT
            $table->string('detection_by', 255);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detections');
    }
};
