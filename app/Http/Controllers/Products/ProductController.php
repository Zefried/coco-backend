<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\ProductImage\ProductImage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'category_id'      => 'required|exists:categories,id',
                'subcategory_id'   => 'required|exists:sub_categories,id',
                'name'             => 'required|string|max:255',
                'description'      => 'nullable|string',
                'clay_type'        => 'nullable|string',
                'firing_method'    => 'nullable|string',
                'glaze_type'       => 'nullable|string',
                'dimensions'       => 'nullable|string',
                'weight'           => 'nullable|string',
                'price'            => 'required|numeric',
                'discount_percent' => 'nullable|numeric',
                'stock_quantity'   => 'required|integer',
                'is_fragile'       => 'required',
                'is_handmade'      => 'required',
                'images'           => 'required|array|max:5',
                'images.*'         => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'validation failed',
                    'err' => $validator->errors(),
                ]);
            }

            $product = Product::create([
                'category_id'      => $request->category_id,
                'subcategory_id'   => $request->subcategory_id,
                'name'             => $request->name,
                'description'      => $request->description,
                'clay_type'        => $request->clay_type,
                'firing_method'    => $request->firing_method,
                'glaze_type'       => $request->glaze_type,
                'dimensions'       => $request->dimensions,
                'weight'           => $request->weight,
                'price'            => $request->price,
                'discount_percent' => $request->discount_percent,
                'stock_quantity'   => $request->stock_quantity,
                'is_fragile'       => $request->boolean('is_fragile'),
                'is_handmade'      => $request->boolean('is_handmade'),
            ]);

            $this->storeImages($request, $product->id);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'product created successfully',
                'data' => $product,
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage(),
            ]);
        }
    }

    public function storeImages(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'images'   => 'required|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            throw new Exception(json_encode($validator->errors()));
        }

        $folderPath = public_path('images');
        if (!File::exists($folderPath)) {
            File::makeDirectory($folderPath, 0755, true);
        }

        foreach ($request->file('images') as $image) {
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move($folderPath, $filename);

            ProductImage::create([
                'product_id' => $productId,
                'image'      => $filename,
            ]);
        }
    }

    public function viewProducts(Request $request)
    {
    try {
        if ($request->has('sub_category_id')) {
            $products = Product::with('images')
                ->where('subcategory_id', $request->sub_category_id)
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Products by subcategory fetched successfully',
                'data' => $products
            ]);
        }

        if ($request->has('category_id')) {
            $products = Product::with('images')
                ->where('category_id', $request->category_id)
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Products by category fetched successfully',
                'data' => $products
            ]);
        }

        return response()->json([
            'status' => 400,
            'message' => 'No category_id or sub_category_id provided',
            'data' => []
        ]);
    } catch (Exception $e) {
        return response()->json([
            'status' => 500,
            'message' => 'Something went wrong in server',
            'err' => $e->getMessage()
        ]);
    }
    }

 
    public function fetchSingleProduct($id)
    {
        try {
            $product = Product::with('images')
                ->where('id', $id)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found',
                ]);
            }

            return response()->json([
                'status' => 200,
                'message' => 'Product fetched successfully',
                'data' => $product
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    public function fetchByStaticCategory(Request $request)
    {
        try {
         
            $validator = Validator::make($request->all(), [
                'category_title' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Validation failed',
                    'err' => $validator->errors(),
                ]);
            }

        
            $products = Product::with('images')
                ->whereRaw('LOWER(name) = ?', [strtolower($request->category_title)])
                ->get();

            return response()->json([
                'status' => 200,
                'message' => 'Products fetched successfully',
                'data' => $products
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }


}
