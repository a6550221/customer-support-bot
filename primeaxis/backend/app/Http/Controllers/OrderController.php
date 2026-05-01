<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('assignee:id,name');

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('order_no', 'like', "%{$request->search}%")
                  ->orWhere('customer_name', 'like', "%{$request->search}%");
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->route) {
            $query->where('route', $request->route);
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $orders]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:100',
            'from_city'     => 'required|string',
            'to_city'       => 'required|string',
            'weight'        => 'required|numeric|min:0.1',
        ]);

        $lastOrder = Order::orderBy('id', 'desc')->first();
        $nextNum   = $lastOrder ? (intval(substr($lastOrder->order_no, -4)) + 1) : 900;
        $orderNo   = 'PA-' . date('Y') . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        $order = Order::create([
            'order_no'      => $orderNo,
            'customer_name' => $request->customer_name,
            'route'         => "{$request->from_city} → {$request->to_city}",
            'weight'        => $request->weight . ' kg',
            'status'        => 'pending',
            'assignee_id'   => $request->user()->id,
            'notes'         => $request->notes,
        ]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $order], 201);
    }

    public function show(Order $order)
    {
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $order->load('assignee:id,name', 'trackingEvents')]);
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->only(['customer_name', 'route', 'weight', 'notes']));
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $order->fresh()]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:transit,pending,active,exception,closed',
            'note'   => 'nullable|string',
        ]);

        $statusLabels = [
            'transit'   => '運輸中',
            'pending'   => '待取件',
            'active'    => '已派送',
            'exception' => '異常',
            'closed'    => '已結單',
        ];

        $order->update(['status' => $request->status]);

        // Add tracking event
        $order->trackingEvents()->create([
            'text' => $request->note ?: "狀態更新為「{$statusLabels[$request->status]}」",
            'type' => $request->status === 'exception' ? 'danger' : ($request->status === 'closed' ? 'success' : 'primary'),
        ]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $order->fresh()->load('trackingEvents')]);
    }

    public function tracking(Order $order)
    {
        return response()->json(['code' => 200, 'message' => 'success', 'data' => $order->trackingEvents()->orderBy('created_at', 'desc')->get()]);
    }

    public function addEvent(Request $request, Order $order)
    {
        $request->validate([
            'text' => 'required|string|max:500',
            'type' => 'nullable|in:primary,success,warning,danger',
        ]);

        $event = $order->trackingEvents()->create([
            'text' => $request->text,
            'type' => $request->get('type', 'primary'),
        ]);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $event]);
    }
}
