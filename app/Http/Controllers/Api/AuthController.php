<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ApiController;

class AuthController extends ApiController
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function register(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        $user_code = generateFiledCode('USER');
        $role = 'MEMBER';

        try {
            $data_user = User::create([
                'user_code' => $user_code,
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => $role,
                'is_deleted' => false,
            ]);

            $token = Auth::login($data_user);

            $data = [
                'data' => $data_user,
                'token' => $token
            ];

            return $this->sendResponse(0, "User berhasil ditambahkan", $data);
        } catch (\Exception $e) {
            return $this->sendError(2, "User gagal ditambahkan", $e->getMessage());
        }
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required',
            'password' => 'required',
        ];

        $validator = validateThis($request, $rules);

        if ($validator->fails()) {
            return $this->sendError(1, 'Params not complete', validationMessage($validator->errors()));
        }

        $credentials = $request->only(['email', 'password']);

        $token = Auth::attempt($credentials);

        if (!$token) {
            return $this->sendError(2, 'Email atau password salah');
        }

        $data = [
            'data' => Auth::user(),
            'token' => $token
        ];

        return $this->sendResponse(0, "Login berhasil", $data);
    }



}
