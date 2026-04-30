<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    protected $fillable = ['order_no', 'customer_name', 'customer_phone', 'status', 'assignee_id'];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'session_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }
}
