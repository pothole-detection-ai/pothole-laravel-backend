<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;
use App\Models\ProductPriceVariant;

class ProductPriceVariantController extends ApiController
{
    public function index()
    {
        $data = ProductPriceVariant::where("is_deleted", 0)->get();
        return $this->sendResponse(0, "Produk Variasi Harga berhasil ditemukan", $data);
    }


    public function store(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            "outlet_code" => "required|max:255",
            "product_price_variant_category_code" => "required|max:255",
            "price" => "required|max:255",
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, "Params not complete", validationMessage($validator->errors()));
        }

        // === END:VALIDATION ===
        // === START:CREATE PRODUCT PRICE VARIANT LOGIC ===
        $product_price_variant_code = generateFiledCode("PRODUCT_PRICE_VARIANT");
        DB::beginTransaction();
        try {
            $data = ProductPriceVariant::create([
                "product_price_variant_code" => $product_price_variant_code,
                "outlet_code" => $request->outlet_code,
                "product_price_variant_category_code" => $request->product_price_variant_category_code,
                "price" => $request->price,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Produk Variasi Harga berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Produk Variasi Harga gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE PRODUCT PRICE VARIANT LOGIC ===
    }

    public function show(string $product_price_variant_code)
    {
        $data = ProductPriceVariant::where("product_price_variant_code", $product_price_variant_code)
                    ->where("is_deleted", 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Produk Variasi Harga tidak ditemukan");
        }
        return $this->sendResponse(0, "Produk Variasi Harga berhasil ditemukan", $data);
    }

    public function update(Request $request, string $product_price_variant_code)
    {
        $rules = [
            "price" => "required|max:255",
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, "Params not complete", validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING PRODUCT PRICE VARIANT
        $checkPriceVariant = ProductPriceVariant::where("product_price_variant_code", $product_price_variant_code)
                        ->where("is_deleted", 0)->first();
        if (!$checkPriceVariant) {
            return $this->sendError(2, "PriceVariant tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $checkPriceVariant->update([
                "price" => $request->price,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Produk Variasi Harga berhasil diupdate", $checkPriceVariant);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Produk Variasi Harga gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $product_price_variant_code)
    {
        $data = ProductPriceVariant::where("product_price_variant_code", $product_price_variant_code)
                    ->where("is_deleted", 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Produk Variasi Harga tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                "is_deleted" => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Produk Variasi Harga berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Produk Variasi Harga gagal dihapus", $e->getMessage());
        }
    }
}
