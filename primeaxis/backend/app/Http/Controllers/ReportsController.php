<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    /** GET /reports/stats?year=2026 — monthly order stats + distributions */
    public function stats(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        // Monthly breakdown
        $monthly = [];
        for ($m = 1; $m <= 12; $m++) {
            $base      = Order::whereYear('created_at', $year)->whereMonth('created_at', $m);
            $total     = (clone $base)->count();
            $completed = (clone $base)->where('status', 'closed')->count();
            $exception = (clone $base)->where('status', 'exception')->count();

            $monthly[] = [
                'month'     => $m,
                'total'     => $total,
                'completed' => $completed,
                'exception' => $exception,
            ];
        }

        // Status distribution (all orders for year)
        $statusDist = Order::whereYear('created_at', $year)
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        // Route distribution (top 6)
        $routeDist = Order::whereYear('created_at', $year)
            ->selectRaw('route, count(*) as cnt')
            ->groupBy('route')
            ->orderByDesc('cnt')
            ->limit(6)
            ->get(['route', 'cnt']);

        // Totals
        $totalOrders    = Order::whereYear('created_at', $year)->count();
        $totalCompleted = Order::whereYear('created_at', $year)->where('status', 'closed')->count();
        $totalException = Order::whereYear('created_at', $year)->where('status', 'exception')->count();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => [
            'monthly'     => $monthly,
            'status_dist' => $statusDist,
            'route_dist'  => $routeDist,
            'totals'      => [
                'orders'    => $totalOrders,
                'completed' => $totalCompleted,
                'exception' => $totalException,
                'rate'      => $totalOrders > 0 ? round($totalCompleted / $totalOrders * 100, 1) : 0,
            ],
        ]]);
    }

    /** GET /reports/agents?year=2026 — per-agent performance */
    public function agents(Request $request)
    {
        $year = (int) $request->get('year', date('Y'));

        $users = User::where('active', true)->orderBy('name')->get();

        $data = $users->map(function ($user) use ($year) {
            $base      = Order::where('assignee_id', $user->id)->whereYear('created_at', $year);
            $total     = (clone $base)->count();
            $completed = (clone $base)->where('status', 'closed')->count();
            $exception = (clone $base)->where('status', 'exception')->count();

            // Monthly order counts
            $monthly = [];
            for ($m = 1; $m <= 12; $m++) {
                $monthly[] = Order::where('assignee_id', $user->id)
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $m)
                    ->count();
            }

            $resolveRate = $total > 0 ? round($completed / $total * 100) : 0;

            // Derive performance tier
            $perf = $resolveRate >= 85 && $total >= 10 ? 'excellent'
                  : ($resolveRate >= 70 || $total >= 5 ? 'good' : 'normal');

            return [
                'id'           => $user->id,
                'name'         => $user->name,
                'role'         => $user->role,
                'orders'       => $total,
                'completed'    => $completed,
                'exceptions'   => $exception,
                'resolve_rate' => $resolveRate,
                'perf'         => $perf,
                'monthly'      => $monthly,
            ];
        })
        ->filter(fn($u) => $u['orders'] > 0 || $u['role'] !== 'admin')
        ->sortByDesc('orders')
        ->values();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $data]);
    }
}
