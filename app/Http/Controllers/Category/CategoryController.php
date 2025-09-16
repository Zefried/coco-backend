<?php

namespace App\Http\Controllers\Category;

use App\Http\Controllers\Controller;
use App\Models\Category\Category;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function resource(Request $request)
    {
        try {
            $type = $request->type;

            if ($type === 'store') {
                return $this->addCategory($request);
            }

            if ($type === 'fetch') {
                return $this->fetchCategory($request);
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

    private function addCategory($request)
    {
        try {
            $isBulk = is_array($request->name);
            $items = $isBulk ? $this->buildCategoryList($request) : [$request->all()];
            $results = [];

            foreach ($items as $index => $item) {
                $validator = Validator::make($item, [
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

                $results[] = $this->processCategory($item);
            }

            return response()->json([
                'status' => 200,
                'message' => $isBulk ? 'Bulk categories processed' : 'Category processed',
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

    private function buildCategoryList($request)
    {
        $list = [];
        foreach ($request->name as $i => $name) {
            $list[] = [
                'name' => $name,
                'slug' => $request->slug[$i] ?? Str::slug($name),
                'description' => $request->description[$i] ?? null,
                'status' => $request->status[$i] ?? true,
            ];
        }
        return $list;
    }

    private function processCategory($data)
    {
        $existing = Category::whereRaw('LOWER(name) = ?', [strtolower($data['name'])])->first();

        if ($existing) {
            $existing->update($data);
            return ['name' => $data['name'], 'action' => 'updated'];
        } else {
            Category::create($data);
            return ['name' => $data['name'], 'action' => 'created'];
        }
    }


    public function fetchCategory()
    {
        try {
            $categories = Category::all();

            return response()->json([
                'status' => 200,
                'message' => 'All categories fetched',
                'data' => $categories
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'something went wrong in server',
                'err' => $e->getMessage()
            ]);
        }
    }
    
    public function deleteCategory(Request $request, $id)
    {
        try {
            $category = Category::find($id);
            if (!$category) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Category not found'
                ]);
            }

            $category->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Category deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ]);
        }
    }


}
