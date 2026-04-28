<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = [
        'session_token', 'visitor_name', 'visitor_email', 'visitor_phone', 'notes',
        'source_url', 'browser', 'agent_id', 'ticket_id', 'status', 'accepted_at', 'closed_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }
}
