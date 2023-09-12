<?php

namespace App\Http\Controllers\Api;

use App\Models\PriceVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;

class PriceVariantController extends ApiController
{
    public function index()
    {
        $data = PriceVariant::where('is_deleted', 0)->get();
        return $this->sendResponse(0, "Variasi Harga berhasil ditemukan", $data);
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
        // === START:CREATE PRICE VARIANT LOGIC ===
        $price_variant_code = generateFiledCode('PRICE_VARIANT');
        DB::beginTransaction();
        try {
            $data = PriceVariant::create([
                'price_variant_code' => $price_variant_code,
                'outlet_code' => $request->outlet_code,
                'name' => $request->name,
            ]);
            
            DB::commit();
            return $this->sendResponse(0, "Variasi Harga berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Variasi Harga gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE PRICE VARIANT LOGIC ===
    }

    public function show(string $price_variant_code)
    {
        $data = PriceVariant::where('price_variant_code', $price_variant_code)
                    ->where('is_deleted', 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Variasi Harga tidak ditemukan");
        }
        return $this->sendResponse(0, "Variasi Harga berhasil ditemukan", $data);
    }

    public function update(Request $request, string $price_variant_code)
    {
        $rules = [
            'name' => 'required|max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING PRICE VARIANT
        $checkPriceVariant = PriceVariant::where('price_variant_code', $price_variant_code)
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
            return $this->sendResponse(0, "Variasi Harga berhasil diupdate", $checkPriceVariant);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Variasi Harga gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $price_variant_code)
    {
        $data = PriceVariant::where('price_variant_code', $price_variant_code)
                    ->where('is_deleted', 0)
                    ->first();
        
        if (!$data) {
            return $this->sendError(1, "Variasi Harga tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                'is_deleted' => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Variasi Harga berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Variasi Harga gagal dihapus", $e->getMessage());
        }
    }
}
