<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;

class CategoryController extends ApiController
{
    public function index()
    {
        $data = Category::where('is_deleted', 0)->get();
        return $this->sendResponse(0, "Category berhasil ditemukan", $data);
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
        // === START:CREATE CATEGORY LOGIC ===
        $category_code = generateFiledCode('CATEGORY');
        DB::beginTransaction();
        try {
            $data = Category::create([
                'category_code' => $category_code,
                'outlet_code' => $request->outlet_code,
                'name' => $request->name,
            ]);
            
            DB::commit();
            return $this->sendResponse(0, "Category berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Category gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE CATEGORY LOGIC ===
    }

    public function show(string $category_code)
    {
        $data = Category::where('category_code', $category_code)
                    ->where('is_deleted', 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Category tidak ditemukan");
        }
        return $this->sendResponse(0, "Category berhasil ditemukan", $data);
    }

    public function update(Request $request, string $category_code)
    {
        $rules = [
            'name' => 'required|max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING CATEGORY
        $checkCategory = Category::where('category_code', $category_code)
                        ->where('is_deleted', 0)->first();
        if (!$checkCategory) {
            return $this->sendError(2, 'Category tidak ditemukan');
        }

        DB::beginTransaction();
        try {
            $checkCategory->update([
                'name' => $request->name,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Category berhasil diupdate", $checkCategory);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Category gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $category_code)
    {
        $data = Category::where('category_code', $category_code)
                    ->where('is_deleted', 0)
                    ->first();
        
        if (!$data) {
            return $this->sendError(1, "Category tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                'is_deleted' => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Category berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Category gagal dihapus", $e->getMessage());
        }
    }
}
