<?php

namespace App\Http\Controllers\Api;

use App\Events\OrderApprovedCash;
use App\Events\OrderCreated;
use App\Events\OrderSentToKitchen;
use App\Events\PaidOrder;
use App\Events\StoreOrder;
use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use Illuminate\Http\Request;
use App\Models\PendingOrder;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class OrderCashController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'payment_type' => 'required|in:cash,credit_card,scan',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|exists:products,name',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric',
            'items.*.size' => 'nullable|string',
            'paid' => 'required|string|max:255',
            'group_key' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();

        if (!$userId) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Check if another session is already handling payment
        $existingOrder = PendingOrder::where('user_id', $userId)
            ->where('group_key', '!=', $request->group_key)
            ->where('status', 'paid')
            ->first();

        if ($existingOrder) {
            return response()->json([
                'error' => 'Another session is already handling the payment. Only one session per user can submit payment.'
            ], 403);
        }

        $order = new PendingOrder();
        $order->user_id = $userId;
        $order->paid = $request->paid;
        $order->group_key = $request->input('group_key');
        $order->amount = $request->amount;
        $order->payment_type = $request->payment_type;
        $order->items = json_encode($request->items);
        $order->save();

        event(new OrderApprovedCash($order, $order->user_id));
        event(new PaidOrder($order->user_id, $order->paid));

        //No Realtime event data
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

        $pendingOrder->update(['status' => 'approved']);
        Log::info("Order Approved Cash", ['order' => $order]);

        $kitchen = KitchenOrder::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'items' => $items,
            'status' => 'pending',
        ]);

        event(new OrderSentToKitchen($kitchen, $order->user_id));

        $order->status = 'paid';
        $order->save();
        event(new StoreOrder($order));

        return response()->json(['message' => 'Order approved and sent to kitchen!', 'order' => $order], 200);
    }

    public function declineOrder($id)
    {
        $pendingOrder = $this->findPendingOrder($id);
        if (!$pendingOrder) {
            return response()->json(['message' => 'Pending order not found or already processed.'], 404);
        }

        $pendingOrder->status = 'declined';
        $pendingOrder->save();

        Log::info("Order Declined", ['orderId' => $id]);
        broadcast(new OrderApprovedCash(['orderId' => $id],[]))->toOthers();
        return response()->json(['message' => 'Order declined.'], 200);
    }

    public function listPendingOrders()
    {
        $pendingOrders = PendingOrder::where('status', 'pending')->get()->map(function ($order) {
            $order->amount = (float)$order->amount;
            return $order;
        });

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
