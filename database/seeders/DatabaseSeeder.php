<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\User;
use App\Models\Outlet;
use App\Models\ProductCategory;
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
            'user_code' => generateFiledCode('USER'),
            'name' => "Dicky",
            'email' => "dicky@gmail.com",
            'whatsapp_number' => "082280731079",
            'password' => bcrypt("dicky"),
            'user_type' => "OWNER",
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $outlet = Outlet::create([
            'outlet_code' => generateFiledCode('OUTLET'),
            'user_code' => $user->user_code,
            'name' => "TOKO SAYA NOMOR 1",
            'address' => "JAKARTA SELATAN, DKI JAKARTA",
            'whatsapp_number' => "08111222444555",
        ]);

        $product_categories = ["Makanan", "Snack", "Lainnya"];
        foreach($product_categories as $product_category) {
            ProductCategory::create([
                "product_category_code" => generateFiledCode("PRODUCT_CATEGORY"),
                "outlet_code" => $outlet->outlet_code,
                "name" => $product_category,
            ]);
        }

        $product_price_variant_categories = ["Makan ditempat", "Bungkus", "Delivery"];
        foreach($product_price_variant_categories as $product_price_variant_category) {
            ProductPriceVariantCategory::create([
                "product_price_variant_category_code" => generateFiledCode("PRODUCT_PRICE_VARIANT_CATEGORY"),
                "outlet_code" => $outlet->outlet_code,
                "name" => $product_price_variant_category,
            ]);
        }


        // $price_variant_category = Category::create([]);
        // $category = Category::create([]);
        // $category = Category::create([]);
    }
}
