<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Customer;
use App\Models\Discount;
use App\Models\User;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPriceVariant;
use Illuminate\Database\Seeder;
use App\Models\ProductPriceVariantCategory;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $user = User::create([
            'user_code' => "USER-0001",
            'name' => "Aditya",
            'email' => "aditya@gmail.com",
            'password' => bcrypt("aditya"),
            'role' => 'MEMBER',
            'is_deleted' => false,
        ]);
    }
}
