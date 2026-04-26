<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['session_id', 'sender_type', 'sender_id', 'content', 'is_read'];

    protected $casts = ['is_read' => 'boolean'];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
