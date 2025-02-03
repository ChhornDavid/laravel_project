<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class CategoryController extends Controller
{
    /**
     * Display a listing of the categories.
     */
    public function index()
    {
        $categories = Category::all();
        return response()->json(['data' => $categories], 200);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $validated['image'] = url('storage/' . $imagePath);
        }

        $category = Category::create($validated);
        return response()->json(['data' => $category], 201);
    }

    /**
     * Display the specified category.
     */
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json(['data' => $category], 200);
    }

    /**
     * Update the specified category in storage.
     */

    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'nullable|string|max:255',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($request->has('name')) {
                $category->name = $validated['name'];
            }

            if ($request->hasFile('image')) {
                if ($category->image) {
                    $oldImagePath = parse_url($category->image, PHP_URL_PATH);
                    $oldImagePath = str_replace('/storage/', '', $oldImagePath);
                    Storage::disk('public')->delete($oldImagePath);
                }
                $imagePath = $request->file('image')->store('categories', 'public');
                $category->image = asset('storage/' . $imagePath);
            } else if ($category->image) {
                $category->image = $category->image;
            }

            $category->save();

            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully!',
                'data' => $category
            ]);
        } catch (Exception $e) {
            Log::error('Update Error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update category!',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Remove the specified category from storage.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Delete image if exists
        if ($category->image) {
            $oldImagePath = str_replace(url('storage/'), '', $category->image);
            Storage::disk('public')->delete($oldImagePath);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully'], 200);
    }
}
