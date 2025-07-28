<?php

namespace App\Http\Controllers\SubCategory;

use App\Http\Controllers\Controller;
use App\Models\SubCategory\SubCategory;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubCategoryController extends Controller
{
    
    public function resource(Request $request)
    {
        try {
            $type = $request->type;

            if ($type === 'store') {
                return $this->addSubCategory($request);
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

    private function addSubCategory($request)
    {
        try {
            $isBulk = is_array($request->name);
            $items = $isBulk ? $this->buildSubCategoryList($request) : [$request->all()];
            $results = [];

            foreach ($items as $index => $item) {
                $validator = Validator::make($item, [
                    'category_id' => 'required|exists:categories,id',
                    'name' => 'required|string',
                    'slug' => 'nullable|string',
                    'description' => 'nullable|string',
                    'status' => 'nullable|boolean',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => 422,
                        'message' => "Validation failed at index $index",
                        'errors' => $validator->errors()
                    ]);
                }

                $results[] = $this->processSubCategory($item);
            }

            return response()->json([
                'status' => 200,
                'message' => $isBulk ? 'Bulk sub-categories processed' : 'Sub-category processed',
                'data' => $results
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }

    private function buildSubCategoryList($request)
    {
        $list = [];
        foreach ($request->name as $i => $name) {
            $list[] = [
                'category_id' => $request->category_id[$i],
                'name' => $name,
                'slug' => $request->slug[$i] ?? Str::slug($name),
                'description' => $request->description[$i] ?? null,
                'status' => $request->status[$i] ?? true,
            ];
        }
        return $list;
    }

    private function processSubCategory($data)
    {
        $existing = SubCategory::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->where('category_id', $data['category_id'])
            ->first();

        if ($existing) {
            $existing->update($data);
            return ['name' => $data['name'], 'action' => 'updated'];
        } else {
            SubCategory::create($data);
            return ['name' => $data['name'], 'action' => 'created'];
        }
    }

    public function fetchSubCategory(Request $request)
    {
        try {
            $request->validate([
                'category_id' => 'required|exists:categories,id'
            ]);

            $subCategories = SubCategory::where('category_id', $request->category_id)->get();

            return response()->json([
                'status' => 200,
                'message' => 'Sub-categories fetched successfully',
                'data' => $subCategories
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
