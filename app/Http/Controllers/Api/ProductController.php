<?php

namespace App\Http\Controllers\Api;

use App\Models\Outlet;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use App\Models\ProductPriceVariant;
use App\Http\Controllers\ApiController;
use App\Models\ProductPriceVariantCategory;
use Illuminate\Support\Facades\Storage;

class ProductController extends ApiController
{
    public function index()
    {
        $select = [
            'products.*',
            'product_categories.name as category_name',
            'outlets.name as outlet_name',
        ];

        $data = Product::where('products.is_deleted', 0)
            ->leftJoin('product_categories', 'products.category_code', '=', 'product_categories.product_category_code')
            ->leftJoin('outlets', 'products.outlet_code', '=', 'outlets.outlet_code')
            ->select($select)
            ->get();

        // if $data->is_price_variant == 1, get price variant
        foreach ($data as $data_item) {
            if ($data_item->is_price_variant == 1) {
                // select product_price_variant_category_code, product_price_variant_category_name, price
                $select = [
                    'product_price_variants.product_price_variant_category_code',
                    'product_price_variant_categories.name as product_price_variant_category_name',
                    'product_price_variants.price',
                ];
                $data_item->price_variant = ProductPriceVariant::where('product_price_variants.product_code', $data_item->product_code)
                    ->where('product_price_variants.is_deleted', 0)
                    ->leftJoin('product_price_variant_categories', 'product_price_variants.product_price_variant_category_code', '=', 'product_price_variant_categories.product_price_variant_category_code')
                    ->select($select)
                    ->get();
            }
        }

        return $this->sendResponse(0, "Product berhasil ditemukan", $data);
    }


    public function store(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            'outlet_code' => 'required|max:255',
            'name' => 'required|max:255',
            'selling_price' => 'required|max:11',
            'is_price_variant' => 'required',
            'category_code' => 'max:255',
            'capital_price' => 'max:255',
            'sku' => 'max:255',
            'stock' => 'max:255',
            // 'photo' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        $outlet = Outlet::where('outlet_code', $request->outlet_code)->first();
        if (!$outlet) {
            return $this->sendError(2, 'Outlet tidak ditemukan');
        }

        if ($request->category_code != null) {
            $product_category = ProductCategory::where('category_code', $request->category_code)->first();
            if (!$product_category) {
                return $this->sendError(3, 'Kategori Produk tidak ditemukan');
            }
        }
        // === END:VALIDATION ===
        // === START:CREATE PRODUCT LOGIC ===
        DB::beginTransaction();
        $product_code = generateFiledCode('PRODUCT');
        try {
            $photoName = generateFiledCode('PRODUCT_IMG');
            $photoPath = storeImage($request->photo, 'products', $photoName);

            $data = Product::create([
                'product_code' => $product_code,
                'outlet_code' => $request->outlet_code,
                'name' => $request->name,
                'selling_price' => $request->selling_price,
                'is_price_variant' => $request->is_price_variant,
                'photo' => $photoPath,
                'category_code' => $request->category_code,
                'capital_price' => $request->capital_price,
                'sku' => $request->sku,
                'stock' => $request->stock,
            ]);

            if ($request->is_price_variant == 1) {
                // product_price_variant_category_code:price|product_price_variant_category_code:price
                // Contoh:
                // PRODUCT_PRICE_VARIANT_CATEGORY-3720231203070925091:15000|PRODUCT_PRICE_VARIANT_CATEGORY-3720231203070925091:20000
                $price_variant_data = $request->price_variant_data;
                if ($price_variant_data == null) {
                    return $this->sendError(4, 'Data variasi harga yang Anda masukkan kosong');
                }
                // check apakah data yang dimasukkan sesuai format product_price_variant_category_code:price|product_price_variant_category_code:price
                if (!preg_match('/^([A-Z0-9_]+-\d+:\d+)(\|[A-Z0-9_]+-\d+:\d+)?$/', $price_variant_data)) {
                    return $this->sendError(5, 'Data variasi harga yang Anda masukkan tidak sesuai format');
                }

                $price_variant_data = explode("|", $price_variant_data);

                foreach ($price_variant_data as $price_variant_data_item) {
                    $price_variant_data_item = explode(":", $price_variant_data_item);
                    // Insert to DB
                    ProductPriceVariant::create([
                        'product_price_variant_code' => generateFiledCode('PRODUCT_PRICE_VARIANT'),
                        'product_code' => $data->product_code,
                        'product_price_variant_category_code' => $price_variant_data_item[0],
                        'price' => $price_variant_data_item[1],
                    ]);
                }
            }
            DB::commit();
            return $this->sendResponse(0, "Product berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Product gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE PRODUCT LOGIC ===
    }

    public function show(string $product_code)
    {
        $select = [
            'products.*',
            'product_categories.name as category_name',
            'outlets.name as outlet_name',
        ];

        $data = Product::where('products.product_code', $product_code)
            ->where('products.is_deleted', 0)
            ->leftJoin('product_categories', 'products.category_code', '=', 'product_categories.product_category_code')
            ->leftJoin('outlets', 'products.outlet_code', '=', 'outlets.outlet_code')
            ->select($select)
            ->first();

        if (!$data) {
            return $this->sendError(1, "Product tidak ditemukan");
        }

        // if $data->is_price_variant == 1, get price variant
        if ($data->is_price_variant == 1) {
            $data->price_variant = ProductPriceVariant::where('product_code', $data->product_code)
                ->where('is_deleted', 0)
                ->get();
        }

        $data->price_variant = ProductPriceVariant::where('product_code', $product_code)
            ->where('is_deleted', 0)
            ->get();

        return $this->sendResponse(0, "Product berhasil ditemukan", $data);
    }



    public function update(Request $request, string $product_code)
    {
        // === START:VALIDATION ===
        $rules = [
            'outlet_code' => 'required|max:255',
            'name' => 'required|max:255',
            'selling_price' => 'required|max:11',
            'is_price_variant' => 'required',
            'category_code' => 'max:255',
            'capital_price' => 'max:255',
            'sku' => 'max:255',
            'stock' => 'max:255',
            // 'photo' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        $outlet = Outlet::where('outlet_code', $request->outlet_code)->first();
        if (!$outlet) {
            return $this->sendError(2, 'Outlet tidak ditemukan');
        }

        if ($request->category_code != null) {
            $product_category = ProductCategory::where('category_code', $request->category_code)->first();
            if (!$product_category) {
                return $this->sendError(3, 'Kategori Produk tidak ditemukan');
            }
        }
        // === END:VALIDATION ===
        // === START:UPDATE PRODUCT LOGIC ===
        DB::beginTransaction();
        try {
            $data = Product::where('product_code', $product_code)
                ->where('is_deleted', 0)
                ->first();

            if (!$data) {
                return $this->sendError(4, "Product tidak ditemukan");
            }

            // If has photo, check old_data, if its not null, delete old photo
            if ($request->photo != null) {
                if ($data->photo != null) {
                    Storage::delete($data->photo);
                }
                $photoName = generateFiledCode('PRODUCT_IMG');
                $photoPath = storeImage($request->photo, 'products', $photoName);
            } else {
                $photoPath = $data->photo;
            }

            $data->update([
                'outlet_code' => $request->outlet_code,
                'name' => $request->name,
                'selling_price' => $request->selling_price,
                'is_price_variant' => $request->is_price_variant,
                'photo' => $photoPath,
                'category_code' => $request->category_code,
                'capital_price' => $request->capital_price,
                'sku' => $request->sku,
                'stock' => $request->stock,
            ]);

            if ($request->is_price_variant == 1) {
                $price_variant_data = $request->price_variant_data;
                if ($price_variant_data == null) {
                    return $this->sendError(4, 'Data variasi harga yang Anda masukkan kosong');
                }
                // check apakah data yang dimasukkan sesuai format product_price_variant_category_code:price|product_price_variant_category_code:price
                if (!preg_match('/^([A-Z0-9_]+-\d+:\d+)(\|[A-Z0-9_]+-\d+:\d+)?$/', $price_variant_data)) {
                    return $this->sendError(5, 'Data variasi harga yang Anda masukkan tidak sesuai format');
                }

                // check if product price variant is exist
                $product_price_variant = ProductPriceVariant::where('product_code', $product_code)
                    ->where('is_deleted', 0)
                    ->get();

                if ($product_price_variant->count() > 0) {
                    // delete all product price variant
                    foreach ($product_price_variant as $product_price_variant_item) {
                        $product_price_variant_item->update([
                            'is_deleted' => 1,
                        ]);
                    }
                }

                $price_variant_data = explode("|", $price_variant_data);

                foreach ($price_variant_data as $price_variant_data_item) {
                    $price_variant_data_item = explode(":", $price_variant_data_item);
                    // Insert to DB
                    ProductPriceVariant::create([
                        'product_price_variant_code' => generateFiledCode('PRODUCT_PRICE_VARIANT'),
                        'product_code' => $data->product_code,
                        'product_price_variant_category_code' => $price_variant_data_item[0],
                        'price' => $price_variant_data_item[1],
                    ]);
                }
            }

            DB::commit();
            return $this->sendResponse(0, "Product berhasil diupdate", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(5, "Product gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $product_code)
    {
        $data = Product::where('product_code', $product_code)
            ->where('is_deleted', 0)
            ->first();

        if (!$data) {
            return $this->sendError(1, "Product tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                'is_deleted' => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Product berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Product gagal dihapus", $e->getMessage());
        }
    }
}
