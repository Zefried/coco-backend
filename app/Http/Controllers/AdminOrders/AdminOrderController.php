<?php

namespace App\Http\Controllers\AdminOrders;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\ProductImage\ProductImage;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{

    public function fetchAllUserOrders(Request $request)
    {
        try {
            $page = (int) $request->query('page', 1); // Cast to integer
            $perPage = (int) $request->query('perPage', 5); // Cast to integer
            $skip = ($page - 1) * $perPage;

            // Fetch paginated orders with related users
            $orders = Order::with('user:id,name')
                ->orderBy('created_at', 'desc') 
                ->skip($skip)
                ->take($perPage)
                ->get(['id', 'user_id', 'products', 'delivery_status', 'payment_status']);

            $totalOrders = Order::count();

            // Collect product IDs from current page orders
            $productIds = [];
            foreach ($orders as $order) {
                $decoded = $order->products ? json_decode($order->products, true) : [];
                foreach ($decoded as $item) {
                    if (isset($item['product_id'])) {
                        $productIds[] = $item['product_id'];
                    }
                }
            }

            // Get product info + images
            $productNameAndImage = Product::whereIn('id', $productIds)
                ->with('images')
                ->get(['id', 'name']);

            return response()->json([
                'status'  => 200,
                'message' => 'orders fetched successfully',
                'data'    => [
                    'orders'     => $orders,
                    'products'   => $productNameAndImage,
                    'totalPages' => ceil($totalOrders / $perPage)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'something went wrong in server',
                'err'     => $e->getMessage()
            ]);
        }
    }

    public function updateOrderStatus(Request $request, $orderId)
    {
     
        $field = $request->input('field'); // e.g., 'delivery_status' or 'payment_status'
        $value = $request->input('value'); // new value for that field
    
       
        if (!in_array($field, ['delivery_status', 'payment_status'])) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid field'
            ]);
        }

        try {
            $order = Order::findOrFail($orderId);
            $order->update([$field => $value]);

            return response()->json([
                'status'  => 200,
                'message' => 'Order status updated successfully',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Failed to update order status',
                'err'     => $e->getMessage()
            ]);
        }
    }

    public function searchOrders(Request $request){
        $query = $request->input('q'); // matches frontend

        if(!$query) {
            return response()->json([
                'status'  => 200,
                'message' => 'No query provided',
                'data'    => []
            ]);
        }

        $searchData = Order::where('id', 'LIKE', '%'.$query.'%')
            ->orWhere('item_name', 'LIKE', '%'.$query.'%')
            ->limit(50)
            ->get();

        return response()->json([
            'status'  => 200,
            'message' => 'Orders fetched successfully',
            'data'    => $searchData
        ]);
    }

    public function selectOrder($orderId) {
        try {

            $order = Order::find($orderId);
            if (!$order) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Order not found'
                ]);
            }

            $user = User::find($order->user_id, ['name']);

            $decodedProducts = json_decode($order->products, true) ?? [];
            $productIds = array_map(fn($p) => $p['product_id'], $decodedProducts);

            $products = Product::whereIn('id', $productIds)
                ->with('images')
                ->get();

            return response()->json([
                'status' => 200,
                'order' => $order,
                'user' => $user,
                'products' => $products
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    public function findFullInfo($orderId)
    {
        try {
            $orderData = Order::find($orderId);
            if (!$orderData) {
                return response()->json([
                    'status'  => 500,
                    'message' => 'Order not found',
                ]);
            }
            $userId = $orderData->user_id;
            $userInfo = User::find($userId, ['name', 'email', 'phone']); 

            if (!$userInfo) {
                return response()->json([
                    'status'  => 500,
                    'message' => 'User not found',
                ]);
            }

            $decoded = json_decode($orderData->products, true) ?? [];
            $productData = Product::whereIn('id', array_column($decoded, 'product_id'))
                ->with('images')
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'images' => $p->images,
                    'quantity' => collect($decoded)->firstWhere('product_id', $p->id)['quantity'] ?? 0
                ]);

            return response()->json([
                'status' => 200,
                'message' => 'Order full info fetched successfully',
                'data' => [
                    'order' => $orderData,
                    'user' => $userInfo,
                    'products' => $productData
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'status'  => 500,
                'message' => 'Something went wrong in server',
                'err'     => $e->getMessage(),
            ]);
        }
    }




}
