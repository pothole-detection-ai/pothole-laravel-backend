<?php

namespace App\Http\Controllers\Api;

use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;

class OutletController extends ApiController
{
    public function index()
    {
        $data = Outlet::where('is_deleted', 0)->get();
        return $this->sendResponse(0, "Outlet berhasil ditemukan", $data);
    }

    
    public function store(Request $request)
    {
        // === START:VALIDATION ===
        $rules = [
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'whatsapp_number' => 'max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        $user = Auth::user();
        $user_code = $user->user_code;

        // === END:VALIDATION ===
        // === START:CREATE OUTLET LOGIC ===
        $outlet_code = generateFiledCode('OUTLET');
        DB::beginTransaction();
        try {
            $data = Outlet::create([
                'outlet_code' => $outlet_code,
                'user_code' => $user_code,
                'name' => $request->name,
                'address' => $request->address,
                'whatsapp_number' => $request->whatsapp_number ?? null,
            ]);
            
            DB::commit();
            return $this->sendResponse(0, "Outlet berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Outlet gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE OUTLET LOGIC ===
    }

    public function show(string $outlet_code)
    {
        $data = Outlet::where('outlet_code', $outlet_code)
                    ->where('is_deleted', 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Outlet tidak ditemukan");
        }
        return $this->sendResponse(0, "Outlet berhasil ditemukan", $data);
    }

    public function update(Request $request, string $outlet_code)
    {
        $rules = [
            'name' => 'required|max:255',
            'address' => 'required|max:255',
            'whatsapp_number' => 'max:255',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING OUTLET
        $checkOutlet = Outlet::where('outlet_code', $outlet_code)
                        ->where('is_deleted', 0)->first();
        if (!$checkOutlet) {
            return $this->sendError(2, 'Outlet tidak ditemukan');
        }

        DB::beginTransaction();
        try {
            $checkOutlet->update([
                'name' => $request->name,
                'address' => $request->address,
                'whatsapp_number' => $request->whatsapp_number ?? null,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Outlet berhasil diupdate", $checkOutlet);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Outlet gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $outlet_code)
    {
        $data = Outlet::where('outlet_code', $outlet_code)
                    ->where('is_deleted', 0)
                    ->first();
        
        if (!$data) {
            return $this->sendError(1, "Outlet tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                'is_deleted' => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Outlet berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Outlet gagal dihapus", $e->getMessage());
        }
    }
}
