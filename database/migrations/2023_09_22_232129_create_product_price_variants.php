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
        Schema::create('product_price_variants', function (Blueprint $table) {
            $table->id();
            $table->string('product_price_variant_code', 255)->unique();
            $table->string('product_code', 255);
            $table->string('product_price_variant_category_code', 255);
            $table->bigInteger('price');
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_variants');
    }
};
