<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        try {
            $products = Product::all();
            return response()->json([
                'success' => true,
                'data' => $products
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Store a new product
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'id_specialmenu' => 'nullable|exists:special_menu,id',
                'id_category' => 'nullable|exists:categories,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'prices.*.size' => 'required|string',
                'prices.*.price' => 'required|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('products', 'public');
                $validated['image'] = url('storage/' . $imagePath);
            }

            $product = Product::create($validated);
            return response()->json([
                'success' => true,
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Show a single product
    public function show($id)
    {
        try {
            $product = Product::findOrFail($id);
            return response()->json([
                'success' => true,
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    // Update a product
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'id_specialmenu' => 'nullable|exists:special_menu,id',
                'id_category' => 'nullable|exists:categories,id',
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'rating' => 'nullable|numeric|min:0|max:5',
                'prices.*.size' => 'required|string',
                'prices.*.price' => 'required|numeric',
                'size' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $product = Product::findOrFail($id);

            if ($request->hasFile('image')) {
                // Delete old image
                if ($product->image) {
                    Storage::delete(str_replace('/storage/', '', $product->image));
                }
                $path = $request->file('image')->store('products', 'public');
                $validated['image'] = url('storage/' . $path);
            }

            $product->update($validated);
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete image if exists
        if ($product->image) {
            $oldImagePath = str_replace(url('storage/'), '', $product->image);
            Storage::disk('public')->delete($oldImagePath);
        }


        $product->delete();

        return response()->json(['message' => 'Product deleted successfully'], 200);
    }
}
