<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickReply extends Model
{
    protected $fillable = ['title', 'content', 'agent_id', 'is_global'];

    protected $casts = ['is_global' => 'boolean'];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
