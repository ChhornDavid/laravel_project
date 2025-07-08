<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Events\CreditCardToKitchen;
use App\Events\OrderCreated;
use App\Models\StoreOrders;

class OrderController extends Controller
{
    public function getDraftOrder(Request $request, $userId)
    {
        $order = StoreOrders::where('user_id', $userId)
            ->where('status', false)
            ->latest()
            ->first();

        if ($order) {
            return response()->json([
                'message' => 'Draft order found',
                'items' => $order->items,
            ]);
        }

        return response()->json([
            'message' => 'No draft order',
            'items' => []
        ]);
    }

    public function addItems(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'items' => 'required|array',
            'group_key' => 'required'
        ]);

        $userId = $validated['user_id'];
        $items = $validated['items'];
        $groupKey = $validated['group_key'];

        $order = StoreOrders::where('user_id', $userId)
            ->where('status', false)
            ->first();

        if ($order) {
            $order->update(['items' => $items]);
        } else {
            $order = StoreOrders::create([
                'user_id' => $userId,
                'items' => $items,
                'group_key' => $groupKey,
                'status' => false
            ]);
        }

        broadcast(new OrderCreated($userId, $items, $groupKey))->toOthers();

        return response()->json([
            'message' => 'Order saved successfully',
            'order' => $order
        ]);
    }

    /**
     * Display a listing of the orders.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);

        $orders = Order::with('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($orders);
    }

    public function LastOrder()
    {
        $lastOrderId = Order::orderBy('id', 'desc')->value('id');
        return response()->json(['id' => $lastOrderId]);
    }

    /**
     * Store a new order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'payment_type' => 'required|in:cash,credit_card,scan',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_name' => 'required|exists:products,name',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.amount' => 'required|numeric',
            'items.*.size' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the authenticated user
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'User not authenticated'], 401);
        }

        // Create the order
        $order = new Order();
        $order->user_id = $user->id;
        $order->amount = $request->amount;
        $order->payment_type = $request->payment_type;
        $order->save();

        // Save order items
        foreach ($request->items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
                'size' => $item['size'] ?? null,
            ]);
        }

        // Create a kitchen order
        $kichen = KitchenOrder::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'items' => $request->items,
            'status' => 'pending',
        ]);

        event(new CreditCardToKitchen($kichen, $order->user_id));

        // Return the response
        return response()->json([
            'message' => 'Order created successfully',
            'order' => $order->load('items'),
        ], 201);
    }

    /**
     * Display the specified order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $order = Order::with('items')->find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }
    /**
     * Update the specified order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'sometimes|required|numeric',
            'payment_type' => 'sometimes|required|in:cash,credit_card,scan',
            // Add validation for order items
            'items' => 'sometimes|required|array',
            'items.*.product_id' => 'sometimes|required|exists:products,id',
            'items.*.quantity' => 'sometimes|required|integer|min:1',
            'items.*.amount' => 'sometimes|required|numeric',
            'items.*.size' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $order->fill($request->only(['amount', 'payment_type']));
        $order->save();

        // Update or create order items
        if ($request->has('items')) {
            $order->items()->delete(); // Delete old order items

            foreach ($request->items as $item) {
                $order->items()->create($item); // Create new order items
            }
        }

        return response()->json(['message' => 'Order updated successfully', 'order' => $order->load('items')]);
    }

    /**
     * Remove the specified order from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order->delete();
        return response()->json(['message' => 'Order deleted successfully']);
    }
}
