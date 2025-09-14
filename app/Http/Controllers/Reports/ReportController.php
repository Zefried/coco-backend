<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportController extends Controller
{

    public function getReport(Request $request)
    {
        try {
            $from = $request->input('from');
            $to = $request->input('to');

            $query = Order::query();

            if ($from && $to) {
                $fromDate = Carbon::parse($from)->startOfDay();
                $toDate = Carbon::parse($to)->endOfDay();
                $query->whereBetween('created_at', [$fromDate, $toDate]);
            }

            $orders = $query->get();

            $totalOrders         = $orders->count();
            $totalProcessingOrders  = $orders->where('delivery_status', 'Processing')->count();
            $totalPendingOrders  = $orders->where('delivery_status', 'pending')->count();
            $totalCompletedOrders= $orders->where('delivery_status', 'delivered')->count();
            $totalShippedOrders  = $orders->where('delivery_status', 'shipped')->count();

            // Calculate total revenue
            $totalRevenue = 0;
            foreach ($orders as $order) {
                if ($order->delivery_status === 'delivered' && $order->payment_status === 'paid') {
                    $products = json_decode($order->products, true);
                    foreach ($products as $product) {
                        $totalRevenue += $product['quantity'] * $product['unit_price'];
                    }
                }
            }

            // Product count
            $totalProducts = Product::count();

            return response()->json([
                'status' => 200,
                'data' => [
                    'totalOrders'         => $totalOrders,
                    'totalShippedOrders'  => $totalShippedOrders,
                    'totalPendingOrders'  => $totalPendingOrders,
                    'totalCompletedOrders'=> $totalCompletedOrders,
                    'totalRevenue'        => $totalRevenue,
                    'totalProducts'       => $totalProducts,
                    'totalProcessingOrders' => $totalProcessingOrders
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => $e->getMessage()
            ]);
        }
    }


}
