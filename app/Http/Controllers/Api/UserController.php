<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ApiController;

class UserController extends ApiController
{

    public function index()
    {
        $data = User::where('is_deleted', 0)->get();
        return $this->sendResponse(0, "User berhasil ditemukan", $data);
    }

    // public function store(Request $request)
    // {
    //     $rules = [
    //         'name' => 'required',
    //         'email' => 'required|unique:users',
    //         'phone_number' => 'required|unique:users',
    //         'password' => 'required',
    //     ];

    //     $validator = validateThis($request, $rules);

    //     if ($validator->fails()) {
    //         return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
    //     }

    //     $user_code = generateFiledCode('USER');
    //     $role = 'CASHIER';

    //     DB::beginTransaction();
    //     try {
    //         $data = User::create([
    //             'user_code' => $user_code,
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'phone_number' => $request->phone_number,
    //             'password' => bcrypt($request->password),
    //             'role' => $role,
    //         ]);

    //         DB::commit();
    //         return $this->sendResponse(0, "User berhasil ditambahkan", $data);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->sendError(2, "User gagal ditambahkan", $e->getMessage());
    //     }
    // }

    // public function show(string $user_code)
    // {
    //     $data = User::where('user_code', $user_code)
    //                 ->where('is_deleted', 0)
    //                 ->first();
        
    //     if (!$data) {
    //         return $this->sendError(1, "User tidak ditemukan");
    //     }
    //     return $this->sendResponse(0, "User berhasil ditemukan", $data);
    // }

    // public function update(Request $request, string $user_code)
    // {
    //     $rules = [
    //         'name' => 'required',
    //         'email' => 'required',
    //         'phone_number' => 'required',
    //         'password' => 'required',
    //     ];

    //     $old_data = User::where('user_code', $user_code)
    //                      ->where('is_deleted', 0)
    //                      ->first();
    //     if(!$old_data){
    //         return $this->sendError(1, "User tidak ditemukan");
    //     }

    //     if($old_data->email != $request->email){
    //         $rules['email'] = 'required|unique:users';
    //     }

    //     if($old_data->phone_number != $request->phone_number){
    //         $rules['phone_number'] = 'required|unique:users';
    //     }

    //     $validator = validateThis($request, $rules);

    //     if ($validator->fails()) {
    //         return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $user = User::where('user_code', $user_code)->first();
    //         $user->update([
    //             'name' => $request->name,
    //             'email' => $request->email,
    //             'phone_number' => $request->phone_number,
    //             'password' => bcrypt($request->password),
    //         ]);

    //         DB::commit();
    //         return $this->sendResponse(0, "User berhasil diupdate", $user);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->sendError(2, "User gagal diupdate", $e->getMessage());
    //     }
    // }

    // public function destroy(string $user_code)
    // {
    //     $data = User::where('user_code', $user_code)
    //                 ->where('is_deleted', 0)
    //                 ->first();
        
    //     if (!$data) {
    //         return $this->sendError(1, "User tidak ditemukan");
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $data->update([
    //             'is_deleted' => 1,
    //         ]);

    //         DB::commit();
    //         return $this->sendResponse(0, "User berhasil dihapus", $data);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return $this->sendError(2, "User gagal dihapus", $e->getMessage());
    //     }
    // }
}
