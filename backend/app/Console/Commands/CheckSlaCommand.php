<?php

namespace App\Console\Commands;

use App\Jobs\CheckSlaDeadlines;
use Illuminate\Console\Command;

class CheckSlaCommand extends Command
{
    protected $signature   = 'tickets:check-sla';
    protected $description = 'Check SLA deadlines and send warning emails';

    public function handle(): void
    {
        CheckSlaDeadlines::dispatch();
        $this->info('SLA check dispatched.');
    }
}
