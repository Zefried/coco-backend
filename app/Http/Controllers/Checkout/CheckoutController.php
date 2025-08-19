<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\cart;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    public function addUserCart(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cartItems'             => 'required|array|min:1',
                'cartItems.*.id'        => 'required|integer|exists:products,id',
                'cartItems.*.quantity'  => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Validation failed',
                    'err'     => $validator->errors(),
                ]);
            }

            $userId = $request->user()->id;
            $responseData = [];

            foreach ($request->cartItems as $item) {
                $cartItem = Cart::where('user_id', $userId)
                                ->where('product_id', $item['id'])
                                ->first();

                if ($cartItem) {
                    // Update quantity
                    $cartItem->quantity += $item['quantity'];
                    $cartItem->updated_at = now();
                    $cartItem->save();
                } else {
                    // Insert new
                    $cartItem = Cart::create([
                        'user_id'    => $userId,
                        'product_id' => $item['id'],
                        'quantity'   => $item['quantity'],
                    ]);
                }

                $responseData[] = $cartItem;
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Cart updated successfully',
                'data'    => $responseData,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong on the server',
                'err'     => $e->getMessage(),
            ]);
        }
    }




}
