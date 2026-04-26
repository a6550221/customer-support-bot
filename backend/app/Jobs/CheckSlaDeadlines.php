<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class CheckSlaDeadlines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $tickets = Ticket::whereIn('status', ['open', 'pending'])
            ->whereNotNull('sla_due_at')
            ->where('sla_due_at', '<=', now()->addHour())
            ->with('agent')
            ->get();

        foreach ($tickets as $ticket) {
            if ($ticket->agent) {
                Mail::to($ticket->agent->email)->queue(new \App\Mail\SlaWarning($ticket));
            }
        }
    }
}
