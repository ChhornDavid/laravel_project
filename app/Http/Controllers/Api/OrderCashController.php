<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderApproved;
use App\Events\OrderApprovedCash;
use App\Events\OrderDeclined;
use App\Events\OrderCreated;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PendingOrder;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderCashController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'payment_type' => 'required|in:cash,credit_card,scan',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric',
            'items.*.size' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get authenticated user ID
        $userId = auth()->id(); // Ensure the request has authentication

        if (!$userId) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        $order = new PendingOrder();
        $order->user_id = $userId;
        $order->amount = $request->amount;
        $order->payment_type = $request->payment_type;
        $order->items = json_encode($request->items);
        $order->save();

        //broadcast(new OrderApprovedCash($order))->toOthers();

        return response()->json(['message' => 'Order created successfully', 'order' => $order], 201);
    }

    public function approveOrder($id)
    {
        $pendingOrder = $this->findPendingOrder($id);
        if (!$pendingOrder) {
            return response()->json(['message' => 'Pending order not found or already processed.'], 404);
        }
        $items = json_decode($pendingOrder->items, true);

        // Create a new order
        $order = Order::create([
            'user_id' => $pendingOrder->user_id,
            'amount' => $pendingOrder->amount,
            'payment_type' => $pendingOrder->payment_type,
        ]);

        // Insert order items
        foreach ($items as $item) {
            $order->items()->create($item);
        }
        // Mark the pending order as approved
        $pendingOrder->update(['status' => 'approved']);

        // Broadcast the OrderApproved event with the updated order data
        broadcast(new OrderApprovedCash($order))->toOthers();

        return response()->json(['message' => 'Order approved successfully!', 'order' => $order], 200);
    }

    public function declineOrder($id)
    {
        $pendingOrder = $this->findPendingOrder($id);
        if (!$pendingOrder) {
            return response()->json(['message' => 'Pending order not found or already processed.'], 404);
        }

        return response()->json(['message' => 'Order declined.'], 200);
    }

    // List all pending orders for admin
    public function listPendingOrders()
    {
        $pendingOrders = PendingOrder::where('status', 'pending')->get()->map(function ($order) {
            $order->amount = (float)$order->amount;
            return $order;
        });
        event(new OrderApprovedCash($pendingOrders));

        return response()->json($pendingOrders);
    }

    private function findPendingOrder($id)
    {
        $pendingOrder = PendingOrder::find($id);
        if (!$pendingOrder || $pendingOrder->status !== 'pending') {
            return null;
        }
        return $pendingOrder;
    }
}
