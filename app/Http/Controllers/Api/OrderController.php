<?php

namespace App\Http\Controllers\Api;

use App\Events\AddNewOrder;
use App\Events\OrderPaid;
use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Events\CreditCardToKitchen;
use App\Events\OrderCreated;
use App\Models\OrderItem;
use App\Models\StoreOrders;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display of popular order
     */
    public function popularOrder()
    {

        $pupular = OrderItem::select(
            'products.id as product_id',
            'products.name as product_name',
            DB::raw('COUNT(order_items.id) as total')
        )
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total')
            ->get();

        return response()->json([
            'data' => 'success',
            'popular' => $pupular
        ]);
    }

    /**
     * Summary of AddNewOrder
     */
    public function addNewOrder(Request $request)
    {
        $order = $request->input('order');
        $userId = $request->input('user_id');
        broadcast(new AddNewOrder($order, $userId))->toOthers();
        return response()->json(['message' => 'Order broadcasted']);
    }

    /**
     * Summary of getDraftOrder
     */
    public function resetProcessStatus(Request $request)
    {
        $userId = $request->user_id;

        if (!$userId) {
            return response()->json(['message' => 'user_id missing'], 400);
        }

        // Reset all orders for this user
        StoreOrders::where('user_id', $userId)
            ->update(['process_status' => 'Free']);

        return response()->json(['message' => 'Process status reset'], 200);
    }


    public function getDraftOrder(Request $request, $userId)
    {
        $orders = StoreOrders::where('user_id', $userId)
            ->where('status', false)
            ->get();

        if ($orders->isNotEmpty()) {
            return response()->json([
                'message' => 'Draft orders found',
                'orders' => $orders
            ], 200);
        }

        return response()->json([
            'message' => 'No draft orders found',
            'orders' => [],
        ], 200);
    }

    public function addItems(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'items' => 'nullable|array',
            'order_number' => 'required|string',
            'status' => 'nullable|boolean',
            'order_paid' => 'nullable|boolean',
        ]);

        $userId = $validated['user_id'];
        $items = $validated['items'] ?? [];
        $orderNumber = $validated['order_number'];
        $status = $validated['status'] ?? false;
        $orderPaid = $validated['order_paid'] ?? false;

        try {
            DB::beginTransaction();

            $order = StoreOrders::where('user_id', $userId)
                ->where('order_number', $orderNumber)
                ->first();

            if ($order) {
                // ✅ Always update the actual values from request
                $order->update([
                    'items' => $items,
                    'status' => $status,
                    'order_paid' => $orderPaid,
                ]);

                Log::info("✅ Updated order {$orderNumber} for user {$userId} | order_paid={$orderPaid}");
            } else {
                // Create new order
                $order = StoreOrders::create([
                    'user_id' => $userId,
                    'order_number' => $orderNumber,
                    'items' => $items,
                    'status' => $status,
                    'order_paid' => $orderPaid,
                ]);

                Log::info("🆕 Created new order {$orderNumber} for user {$userId} | order_paid={$orderPaid}");
            }

            DB::commit();

            // Broadcast updates
            broadcast(new OrderCreated($userId, $items, $orderNumber))->toOthers();
            broadcast(new AddNewOrder($order, $userId))->toOthers();

            return response()->json([
                'message' => 'Order synced successfully',
                'order' => $order,
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ addItems error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to sync order',
                'error' => $e->getMessage(),
            ], 500);
        }
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
        return response()->json(['message' => 'Order created successfully', 'id' => $lastOrderId], 200);
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
            'order_number' => 'required|string',
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
        $order->order_number = $request->order_number;
        $order->save();

        $processStatus = 'Cashier Approve';

        if (in_array($request->payment_type, ['credit_card', 'scan'])) {
            $processStatus = 'At Kitchen';
        }

        // Update store order process status
        StoreOrders::where('order_number', $request->order_number)
            ->update([
                'process_status' => $processStatus,
            ]);

        // Save order items
        foreach ($request->items as $item) {
            $order->items()->create([
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
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
            'order_number' => $request->order_number,
            'status' => 'pending',
        ]);

        event(new CreditCardToKitchen($kichen, $order->user_id));
        event(new OrderPaid($order->order_number, $order->user_id));

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
