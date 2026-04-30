<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['session_id', 'from_type', 'sender_name', 'content'];

    public function session()
    {
        return $this->belongsTo(ChatSession::class, 'session_id');
    }
}
