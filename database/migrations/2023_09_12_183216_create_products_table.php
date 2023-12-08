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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 255)->unique();
            $table->string('outlet_code', 255)->nullable();
            $table->string('name', 255);
            $table->bigInteger('selling_price');
            $table->boolean('is_price_variant')->default(false);
            $table->string('photo', 255)->nullable();
            $table->string('category_code', 255)->nullable();
            $table->bigInteger('capital_price')->nullable();
            $table->string('sku', 255)->nullable();
            $table->string('stock', 255)->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
