<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        $data = [
            'today_orders'     => Order::whereDate('created_at', $today)->count(),
            'pending_orders'   => Order::whereIn('status', ['transit', 'pending'])->count(),
            'completed_orders' => Order::where('status', 'closed')->count(),
            'exception_orders' => Order::where('status', 'exception')->count(),
            'month_orders'     => Order::where('created_at', '>=', $thisMonth)->count(),
        ];

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $data]);
    }

    public function charts(Request $request)
    {
        $days  = $request->integer('days', 30);
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        // Daily order counts
        $dailyOrders = Order::where('created_at', '>=', $start)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->pluck('total', 'date');

        // Status distribution
        $statusDist = Order::groupBy('status')
            ->select('status', DB::raw('count(*) as total'))
            ->pluck('total', 'status');

        return response()->json(['code' => 200, 'message' => 'success', 'data' => [
            'daily_orders' => $dailyOrders,
            'status_dist'  => $statusDist,
        ]]);
    }
}
