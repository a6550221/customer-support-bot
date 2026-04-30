<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FollowupTask extends Model
{
    protected $fillable = ['title', 'order_no', 'customer', 'priority', 'status', 'due_time', 'note', 'assignee_id'];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    // Expose assignee name as a flat attribute
    protected $appends = ['assignee_name'];

    public function getAssigneeNameAttribute(): ?string
    {
        return $this->assignee?->name;
    }
}
