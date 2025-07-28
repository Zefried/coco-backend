<?php

namespace App\Http\Controllers\AdminAuth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Exception;

class AdminAuthController extends Controller
{
    public function resource(Request $request)
    {
        try {
            $type = $request->type;

            if ($type === 'register') {
                return $this->adminRegister($request);
            }

            if ($type === 'login') {
                return $this->adminLogin($request);
            }

            return response()->json([
                'status' => 400,
                'message' => 'invalid request type',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    private function adminRegister(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'name' => 'required|string',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:3'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'pswView' => $request->password,
                'role' => 'admin',
            ]);

            return response()->json([
                'status' => 200,
                'message' => 'admin registered successfully',
                'data' => $user
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    private function adminLogin(Request $request)
    {
        try {
            $validator = validator($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'message' => 'validation failed',
                    'errors' => $validator->errors()
                ]);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 401,
                    'message' => 'invalid credentials'
                ]);
            }

            $token = $user->createToken('admin-token')->plainTextToken;

            return response()->json([
                'status' => 200,
                'message' => 'login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

}
