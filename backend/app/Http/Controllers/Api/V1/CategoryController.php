<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children')->whereNull('parent_id')->get();
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $categories]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($request->only('name', 'parent_id'));
        return response()->json(['code' => 201, 'message' => 'Created.', 'data' => $category], 201);
    }

    public function show(Category $category)
    {
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $category->load('children')]);
    }

    public function update(Request $request, Category $category)
    {
        $request->validate(['name' => 'required|string|max:100']);
        $category->update($request->only('name', 'parent_id'));
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $category->fresh()]);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json(['code' => 200, 'message' => 'Deleted.', 'data' => null]);
    }
}
