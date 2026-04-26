<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    return $user !== null;
});

Broadcast::channel('agent.{agentId}', function ($user, $agentId) {
    return (int) $user->id === (int) $agentId;
});

Broadcast::channel('chat.{sessionId}', function ($user, $sessionId) {
    return true;
});
