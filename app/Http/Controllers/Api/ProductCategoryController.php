<?php

namespace App\Http\Controllers\Api;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;

class ProductCategoryController extends ApiController
{
    public function index()
    {
        $data = ProductCategory::where("is_deleted", 0)->get();
        return $this->sendResponse(0, "Kategori Produk berhasil ditemukan", $data);
    }


    public function store(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            "outlet_code" => "required|max:255",
            "name" => "required|max:255",
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, "Params not complete", validationMessage($validator->errors()));
        }

        // === END:VALIDATION ===
        // === START:CREATE PRODUCT CATEGORY LOGIC ===
        $product_category_code = generateFiledCode("PRODUCT_CATEGORY");
        DB::beginTransaction();
        try {
            $data = ProductCategory::create([
                "product_category_code" => $product_category_code,
                "outlet_code" => $request->outlet_code,
                "name" => $request->name,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Kategori Produk berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Kategori Produk gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE PRODUCT CATEGORY LOGIC ===
    }

    public function show(string $product_category_code)
    {
        $data = ProductCategory::where("product_category_code", $product_category_code)
                    ->where("is_deleted", 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Kategori Produk tidak ditemukan");
        }
        return $this->sendResponse(0, "Kategori Produk berhasil ditemukan", $data);
    }

    public function update(Request $request, string $product_category_code)
    {
        $rules = [
            "name" => "required|max:255",
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, "Params not complete", validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING PRODUCT CATEGORY
        $checkProductCategory = ProductCategory::where("product_category_code", $product_category_code)
                        ->where("is_deleted", 0)->first();
        if (!$checkProductCategory) {
            return $this->sendError(2, "Kategori Produk tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $checkProductCategory->update([
                "name" => $request->name,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Kategori Produk berhasil diupdate", $checkProductCategory);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Kategori Produk gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $product_category_code)
    {
        $data = ProductCategory::where("product_category_code", $product_category_code)
                    ->where("is_deleted", 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Kategori Produk tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                "is_deleted" => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Kategori Produk berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Kategori Produk gagal dihapus", $e->getMessage());
        }
    }
}
