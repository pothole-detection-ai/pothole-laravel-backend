<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Customer;
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
            'name' => "Dicky",
            'email' => "dicky@gmail.com",
            'whatsapp_number' => "082280731079",
            'password' => bcrypt("dicky"),
            'user_type' => "OWNER",
            'is_active' => true,
            'is_deleted' => false,
        ]);

        $outlet = Outlet::create([
            'outlet_code' => 'OUTLET-0001',
            'user_code' => $user->user_code,
            'name' => "TOKO SAYA NOMOR 1",
            'address' => "JAKARTA SELATAN, DKI JAKARTA",
            'whatsapp_number' => "08111222444555",
        ]);

        $product_categories = ["Makanan", "Snack", "Lainnya"];
        $no = 1;
        foreach($product_categories as $product_category) {
            ProductCategory::create([
                "product_category_code" => "PRODUCT_CATEGORY-000".$no++,
                "outlet_code" => $outlet->outlet_code,
                "name" => $product_category,
            ]);
        }

        $product = Product::create([
            "product_code" => "PRODUCT-0001",
            "outlet_code" => $outlet->outlet_code,
            "name" => "Nasi Goreng",
            "selling_price" => 10000,
            "is_price_variant" => 0,
            // "price_variant_data" => "",
            "photo" => "products/PRODUCT-DEFAULT.png",
        ]);

        $product_price_variant_categories = ["Makan ditempat", "Bungkus", "Delivery"];
        $no = 1;
        foreach($product_price_variant_categories as $product_price_variant_category) {
            ProductPriceVariantCategory::create([
                "product_price_variant_category_code" => "PRODUCT_PRICE_VARIANT_CATEGORY-000".$no,
                "outlet_code" => $outlet->outlet_code,
                "name" => $product_price_variant_category,
            ]);

            // INI UNTUK PRODUCT-0001
            ProductPriceVariant::create([
                "product_price_variant_code" => "PRODUCT_PRICE_VARIANT-000" . $no,
                "product_code" => $product->product_code,
                "product_price_variant_category_code" => "PRODUCT_PRICE_VARIANT_CATEGORY-000" . $no,
                "price" => mt_rand(10000, 20000),
            ]);
            $no++;
        }

        $customer = Customer::create([
            "customer_code" => "CUSTOMER-0001",
            "outlet_code" => $outlet->outlet_code,
            "name" => "Joko Widodo",
            "phone_number" => "081234567777",
            "address" => "istana negara",
        ]);



    }
}
