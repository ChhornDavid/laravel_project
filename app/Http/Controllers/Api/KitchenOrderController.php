<?php

namespace App\Http\Controllers\Api;

use App\Events\EventForRobot;
use App\Http\Controllers\Controller;
use App\Models\KitchenOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
            'status' => 'required|in:pending,accepted,preparing,completed',
        ]);

        try {
            $order = KitchenOrder::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $order->update(['status' => $request->status]);

            // Send real-time event
            event(new EventForRobot($order));

            // Handle robot actions
            if ($request->status === 'preparing') {
                $this->callRobotToKitchen($order);
            } elseif ($request->status === 'completed') {
                $this->sendRobotToTable($order);
            }

            return response()->json(['message' => 'Status updated successfully', 'order' => $order], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error updating status', 'error' => $e->getMessage()], 500);
        }
    }

    private function callRobotToKitchen($order)
    {
        Http::post('http://172.19.202.96/call', [
            'order_id' => $order->id,
            'action' => 'pickup'
        ]);
    }

    private function sendRobotToTable($order)
    {
        Http::post('http://172.19.202.96/deliver', [
            'order_id' => $order->id,
            'table_number' => $order->table_number,
            'action' => 'delivery'
        ]);
    }
}
