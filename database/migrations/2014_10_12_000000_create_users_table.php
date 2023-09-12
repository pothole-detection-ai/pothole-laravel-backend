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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('user_code', 255)->unique();
            $table->string('name', 255);
            $table->string('email', 255)->unique();
            $table->string('whatsapp_number', 255)->unique();
            $table->string('password', 255);
            $table->string('user_type', 255);
            $table->boolean('is_have_outlet')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
