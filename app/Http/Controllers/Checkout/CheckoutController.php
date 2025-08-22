<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\cart;
use App\Models\Product\Product;
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
                    // Overwrite with current quantity from user
                    $cartItem->quantity = $item['quantity'];
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


    public function removeCartItem(Request $request) 
    {
    
        try {
            $userId = $request->user()->id;
            
            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer|exists:products,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 500,
                    'message' => 'validation failed',
                    'err'     => $validator->errors(),
            ]);
        }

        $productId = $request->input('product_id');

        $cartItem = Cart::where('user_id', $userId)
                        ->where('product_id', $productId)
                        ->first();

        if ($cartItem) {
            $cartItem->delete();
            return response()->json([
                'status'  => 200,
                'message' => 'Item removed from cart successfully',
            ]);
        }

        return response()->json([
            'status'  => 404,
            'message' => 'Item not found in cart',
        ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'something went wrong in server',
                'err'     => $e->getMessage(),
            ]);
        }
    }

    public function getCheckoutItems(Request $request)
    {
        try {
            $cartItems = $request->input('cart'); 

            // Collect product IDs
            $prodIds = array_column($cartItems, 'product_id');

            // Fetch only required fields
            $products = Product::whereIn('id', $prodIds)
                ->get(['id', 'name', 'price', 'discount_percent']);
            
            // Merge products with their quantities
            $finalData = [];
            foreach ($products as $product) {
                $quantity = collect($cartItems)->firstWhere('product_id', $product->id)['quantity'];
                $finalData[] = [
                    'product'  => $product,
                    'quantity' => $quantity
                ];
            }

            return response()->json([
                'status'  => 200,
                'message' => 'Items collected',
                'data'    => $finalData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error fetching checkout items',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function checkout(request $request)
    {

        return $request->all();
        try {
            $validator = Validator::make($request->all(), [
                'billingInfo' => 'required|array',
                'billingInfo.name' => 'required|string|max:255',
                'billingInfo.email' => 'required|email|max:255',
                'billingInfo.phone' => 'required|string|max:20',
                'billingInfo.address' => 'required|string|max:500',

                'shippingInfo' => 'required|array',
                'shippingInfo.name' => 'required|string|max:255',
                'shippingInfo.email' => 'required|email|max:255',
                'shippingInfo.phone' => 'required|string|max:20',
                'shippingInfo.address' => 'required|string|max:500',

                'paymentMethod' => 'required|string|in:now,later',
                'transactionId' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => 400,
                    'message' => 'Validation failed',
                    'err'     => $validator->errors(),
                ]);
            }

          

            return response()->json([
                'status'  => 200,
                'message' => 'Checkout successful',
                'data'    => $validator->validated(),
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
