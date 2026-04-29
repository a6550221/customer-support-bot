<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_no', 'customer_name', 'route', 'weight',
        'status', 'assignee_id', 'notes',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function trackingEvents()
    {
        return $this->hasMany(TrackingEvent::class);
    }

    // Append assignee_name for convenience
    protected $appends = ['assignee_name'];

    public function getAssigneeNameAttribute()
    {
        return $this->assignee?->name;
    }
}
