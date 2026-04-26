<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $today = today();

        $todayTickets     = Ticket::whereDate('created_at', $today)->count();
        $pendingTickets   = Ticket::whereIn('status', ['open', 'pending'])->count();
        $resolvedToday    = Ticket::whereDate('resolved_at', $today)->count();
        $avgFirstResponse = Ticket::whereNotNull('first_response_at')
            ->whereDate('created_at', $today)
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, first_response_at)) as avg_seconds')
            ->value('avg_seconds');

        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => [
                'today_tickets'      => $todayTickets,
                'pending_tickets'    => $pendingTickets,
                'resolved_today'     => $resolvedToday,
                'avg_first_response' => $avgFirstResponse ? round($avgFirstResponse) : null,
            ],
        ]);
    }

    public function trend(Request $request)
    {
        $days = $request->integer('days', 7);

        $trend = Ticket::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDays($days))
        ->groupBy(DB::raw('DATE(created_at)'))
        ->orderBy('date')
        ->get();

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $trend]);
    }

    public function agents()
    {
        $agents = User::where('role', 'agent')
            ->withCount([
                'tickets as active_tickets' => fn($q) => $q->whereIn('status', ['open', 'pending']),
                'tickets as resolved_today' => fn($q) => $q->whereDate('resolved_at', today()),
            ])
            ->get(['id', 'name', 'status', 'avatar']);

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $agents]);
    }

    public function csat()
    {
        $scores = Ticket::whereNotNull('csat_score')
            ->selectRaw('csat_score, COUNT(*) as count')
            ->groupBy('csat_score')
            ->get();

        $total   = $scores->sum('count');
        $avg     = $total > 0 ? $scores->sum(fn($s) => $s->csat_score * $s->count) / $total : 0;

        return response()->json([
            'code'    => 200,
            'message' => 'success',
            'data'    => [
                'average'     => round($avg, 1),
                'total_rated' => $total,
                'breakdown'   => $scores,
            ],
        ]);
    }
}
