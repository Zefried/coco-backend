<?php

namespace App\Http\Controllers\userAuth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserAuthController extends Controller
{
    public function userRegister(Request $request)
    {
        try {
           $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'phone' => 'required|string|unique:users,phone|regex:/^[0-9]{10}$/',
                'password' => 'required|string|min:3',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 422,
                    'errors' => $validator->errors()
                ]);
            }

            $user = User::create([
                'name'     => $request->name,
                'phone'    => $request->phone,
                'password' => Hash::make($request->password),
                'pswView'  => $request->password,
                'role' => 'user',
            ]);

            if($user){
                $data = $this->autoLogin($user);
                
                return response()->json([
                    'status'  => 201,
                    'message' => 'User registered successfully',
                    'user'    => $user,
                    'autoLogin' => $data,
                ], 201);
            }



        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'error'  => $e->getMessage(),
                'message' => 'Something went wrong while registering',
            ]);
        }
    }

    public function autoLogin($user)
    {
        // Delete existing tokens
        $user->tokens()->delete();

        // Create a new token
        $token = $user->createToken('authToken')->plainTextToken;

        return [
            'token' => $token,
            'user'  => $user
        ];
    }

    public function userLogin(Request $request)
    {
        $phone = $request->input('phone');

        // Validate the request
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|regex:/^[0-9]{10,15}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->errors()
            ]);
        }

        $user = User::where('phone', $phone)->first();

        if(!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found',
            ]);
        }else{

            $loginData = $this->autoLogin($user);
            
            return response()->json([
                'status' => 200,
                'message' => 'User found',
                'data' => $loginData,
            ]);
         
        }
       
       

        return response()->json([
            'status' => 401,
            'message' => 'Invalid credentials',
        ]);
    }

}
