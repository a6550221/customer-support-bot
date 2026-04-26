<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SlaWarning extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    public function build(): static
    {
        return $this->subject("[SLA Warning] Ticket #{$this->ticket->ticket_no} is overdue")
            ->html("
                <h2>SLA Warning</h2>
                <p>Ticket <strong>{$this->ticket->ticket_no}</strong> - {$this->ticket->subject}</p>
                <p>SLA deadline: <strong>{$this->ticket->sla_due_at}</strong></p>
                <p>Priority: <strong>{$this->ticket->priority}</strong></p>
                <p>Please respond as soon as possible.</p>
            ");
    }
}
