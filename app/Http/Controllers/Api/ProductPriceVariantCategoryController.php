<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Models\ProductPriceVariantCategory;

class ProductPriceVariantCategoryController extends ApiController
{
    public function index()
    {
        $data = ProductPriceVariantCategory::where('is_deleted', 0)->get();
        return $this->sendResponse(0, "Kategori Variasi Harga Produk berhasil ditemukan", $data);
    }


    public function store(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            'outlet_code' => 'required|max:255',
            'name' => 'required|max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        // === END:VALIDATION ===
        // === START:CREATE PRODUCT PRICE VARIANT CATEGORY LOGIC ===
        $product_price_variant_category_code = generateFiledCode('PRODUCT_PRICE_VARIANT_CATEGORY');
        DB::beginTransaction();
        try {
            $data = ProductPriceVariantCategory::create([
                'product_price_variant_category_code' => $product_price_variant_category_code,
                'outlet_code' => $request->outlet_code,
                'name' => $request->name,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Kategori Variasi Harga Produk berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Kategori Variasi Harga Produk gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE PRODUCT PRICE VARIANT CATEGORY LOGIC ===
    }

    public function show(string $product_price_variant_category_code)
    {
        $data = ProductPriceVariantCategory::where('product_price_variant_category_code', $product_price_variant_category_code)
                    ->where('is_deleted', 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Kategori Variasi Harga Produk tidak ditemukan");
        }
        return $this->sendResponse(0, "Kategori Variasi Harga Produk berhasil ditemukan", $data);
    }

    public function update(Request $request, string $product_price_variant_category_code)
    {
        $rules = [
            'name' => 'required|max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING PRODUCT PRICE VARIANT CATEGORY
        $checkPriceVariant = ProductPriceVariantCategory::where('product_price_variant_category_code', $product_price_variant_category_code)
                        ->where('is_deleted', 0)->first();
        if (!$checkPriceVariant) {
            return $this->sendError(2, 'PriceVariant tidak ditemukan');
        }

        DB::beginTransaction();
        try {
            $checkPriceVariant->update([
                'name' => $request->name,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Kategori Variasi Harga Produk berhasil diupdate", $checkPriceVariant);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Kategori Variasi Harga Produk gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $product_price_variant_category_code)
    {
        $data = ProductPriceVariantCategory::where('product_price_variant_category_code', $product_price_variant_category_code)
                    ->where('is_deleted', 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Kategori Variasi Harga Produk tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                'is_deleted' => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Kategori Variasi Harga Produk berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Kategori Variasi Harga Produk gagal dihapus", $e->getMessage());
        }
    }
}
