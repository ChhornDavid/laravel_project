<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use Illuminate\Http\Request;

class KitchenOrderController extends Controller
{
    public function index()
    {
        return response()->json(KitchenOrder::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array',
        ]);

        $order = KitchenOrder::create([
            'order_id' => $request->order_id,
            'user_id' => $request->user_id,
            'items' => json_encode($request->items),
        ]);

        return response()->json($order, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,preparing,completed',
        ]);

        try {
            $order = KitchenOrder::findOrFail($id);
            $order->update(['status' => $request->status]);

            return response()->json(['message' => 'Status updated successfully', 'order' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating status', 'error' => $e->getMessage()], 500);
        }
    }
}
