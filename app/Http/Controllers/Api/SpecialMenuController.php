<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SpecialMenu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SpecialMenuController extends Controller
{
    public function index()
    {
        try {
            Log::info('Fetching all special menus.');
            $menus = SpecialMenu::all();
            return response()->json(['data' => $menus], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching special menus', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Attempting to store a new special menu.', [
                'request' => $request->all(),
            ]);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $specialMenu = SpecialMenu::create($validated);

            Log::info('Special menu created successfully.', ['special_menu_id' => $specialMenu->id]);
            return response()->json(['data' => $specialMenu], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error when storing special menu', [
                'errors' => $e->errors(),
            ]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error storing special menu', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function show(SpecialMenu $specialMenu)
    {
        try {
            Log::info('Fetching a special menu.', ['special_menu_id' => $specialMenu->id]);
            return response()->json(['data' => $specialMenu], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching special menu', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function update(Request $request, SpecialMenu $specialMenu)
    {
        try {
            Log::info('Attempting to update special menu', [
                'request' => $request->all(),
                'special_menu_id' => $specialMenu->id
            ]);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $specialMenu->update($validated);
            Log::info('Special menu updated successfully.', ['special_menu_id' => $specialMenu->id]);
            return response()->json(['data' => $specialMenu], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error when updating special menu', [
                'errors' => $e->errors(),
            ]);
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error updating special menu', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'special_menu_id' => $specialMenu->id
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function destroy(SpecialMenu $specialMenu)
    {
        try {
            Log::info('Attempting to delete special menu', ['special_menu_id' => $specialMenu->id]);
            $specialMenu->delete();
            Log::info('Special menu deleted successfully', ['special_menu_id' => $specialMenu->id]);
            return response()->json(['message' => 'Deleted successfully'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting special menu', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'special_menu_id' => $specialMenu->id
            ]);
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
}
