<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    protected $fillable = ['order_id', 'text', 'type', 'user_id'];

    protected $appends = ['editor_name'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getEditorNameAttribute(): string
    {
        return $this->user?->name ?? '系統';
    }
}
