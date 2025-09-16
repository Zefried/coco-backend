<?php

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Models\cart;
use App\Models\Order\Order;
use App\Models\Payment\Payment;
use App\Models\Product\Product;
use App\Models\ProductImage\ProductImage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function userCart(Request $request)
    {

        try {
            $userId = $request->user()->id;
           
            $cartItems = Cart::where('user_id', $userId)
            ->with('product')
            ->get();
            
            return response()->json([
                'status'  => 200,
                'message' => 'Cart items fetched successfully',
                'data'    => $cartItems,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong on the server',
                'error'   => 'Internal Server Error'
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

    public function checkout(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'billingInfo.name' => 'required|string',
                'billingInfo.phone' => 'required|string',
                'billingInfo.address' => 'required|string',
                'billingInfo.pincode' => 'required|string',
                'paymentMethod' => 'required|in:now,later',
                'checkoutItems' => 'required|array|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'validation failed',
                    'err' => $validator->errors()
                ]);
            }

            DB::beginTransaction();

            $userId = $request->user()->id;
            $products = [];

            $totalAmount = 0;

            foreach ($request->checkoutItems as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                $unitPrice = $product['price'];

                $products[] = [
                    'product_id' => $product['id'],
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice
                ];

                $totalAmount += $quantity * $unitPrice;
            }

            $order = Order::create([
                'user_id' => $userId,
                'products' => json_encode($products),
                'address' => $request->billingInfo['address'],
                'pin' => $request->billingInfo['pincode'],
                'item_name' => implode(', ', array_map(fn($item) => $item['product']['name'], $request->checkoutItems)),
                'payment_id' => $request->transactionId,
                'delivery_status' => 'pending',
                'payment_status' => $request->paymentMethod === 'now' ? 'paid' : 'pending',
                'order_date' => now()
            ]);

            Payment::create([
                'order_id' => $order->id,
                'total_amount' => $totalAmount,
                'payment_method' => $request->paymentMethod,
                'transaction_id' => $request->transactionId
            ]);

            Cart::where('user_id', $userId)->delete();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Checkout completed successfully',
                'data' => [
                    'order_id' => $order->id,
                    'total_amount' => $totalAmount
                ]
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    public function userOrders(Request $request)
    {
        try {
            $userId = $request->user()->id;
            $orders = Order::where('user_id', $userId)->with('payment')->get();

            $productIds = [];
            foreach ($orders as $ord) {
                $decoded = $ord->products ? json_decode($ord->products, true) : [];
                foreach ($decoded as $p) {
                    $productIds[] = $p['product_id'];
                }
            }

            $images = ProductImage::whereIn('product_id', $productIds)->get();

            foreach ($orders as $ord) {
                $decoded = $ord->products ? json_decode($ord->products, true) : [];
                foreach ($decoded as &$p) {
                    $p['images'] = $images->where('product_id', $p['product_id'])->values();
                }
                $ord->products = $decoded;
            }

            return response()->json([
                'status' => 200,
                'message' => 'User products fetched successfully',
                'data' => $orders
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    public function updateCartQuantity(Request $request)
    {
        try{
            $userId = $request->user()->id;
            $item = Cart::where('product_id', $request->product_id)->first();

            if ($item) {
                $item->quantity = $request->input('quantity');
                $item->save();
            } else {
                $item = Cart::create([
                    'user_id' => $userId,
                    'product_id' => $request->input('product_id'),
                    'quantity'   => $request->input('quantity'),
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Cart updated successfully',
                'data' => $item
            ]);
                
        }catch(Exception $e){
            return response()->json([
                'status' => 500,
                'message' => 'problem in updateCartQuantity method'
            ]);
        }
        
    }













}
