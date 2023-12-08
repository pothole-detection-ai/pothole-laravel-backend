<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;

class CustomerController extends ApiController
{
    public function index()
    {
        $data = Customer::where("is_deleted", 0)->get();
        return $this->sendResponse(0, "Customer berhasil ditemukan", $data);
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
        $customer_code = generateFiledCode("CUSTOMER");
        DB::beginTransaction();
        try {
            $data = Customer::create([
                "customer_code" => $customer_code,
                "outlet_code" => $request->outlet_code,
                "name" => $request->name,
                "phone_number" => $request->phone_number ?? "",
                "email" => $request->email ?? "",
                "address" => $request->address ?? "",
            ]);

            DB::commit();
            return $this->sendResponse(0, "Customer berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Customer gagal ditambahkan", $e->getMessage());
        }
        // === END:CREATE PRODUCT CATEGORY LOGIC ===
    }

    public function show(string $customer_code)
    {
        $data = Customer::where("customer_code", $customer_code)
                    ->where("is_deleted", 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Customer tidak ditemukan");
        }
        return $this->sendResponse(0, "Customer berhasil ditemukan", $data);
    }

    public function update(Request $request, string $customer_code)
    {
        $rules = [
            "name" => "required|max:255",
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, "Params not complete", validationMessage($validator->errors()));
        }

        // VALIDATE EXISTING PRODUCT CATEGORY
        $checkProductCategory = Customer::where("customer_code", $customer_code)
                        ->where("is_deleted", 0)->first();
        if (!$checkProductCategory) {
            return $this->sendError(2, "Customer tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $checkProductCategory->update([
                "name" => $request->name,
                "phone_number" => $request->phone_number ?? "",
                "email" => $request->email ?? "",
                "address" => $request->address ?? "",
            ]);

            DB::commit();
            return $this->sendResponse(0, "Customer berhasil diupdate", $checkProductCategory);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Customer gagal diupdate", $e->getMessage());
        }
    }

    public function destroy(string $customer_code)
    {
        $data = Customer::where("customer_code", $customer_code)
                    ->where("is_deleted", 0)
                    ->first();

        if (!$data) {
            return $this->sendError(1, "Customer tidak ditemukan");
        }

        DB::beginTransaction();
        try {
            $data->update([
                "is_deleted" => 1,
            ]);

            DB::commit();
            return $this->sendResponse(0, "Customer berhasil dihapus", $data);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError(2, "Customer gagal dihapus", $e->getMessage());
        }
    }
}
