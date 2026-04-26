<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_no', 'subject', 'status', 'priority', 'category_id',
        'customer_id', 'assigned_agent_id', 'tags', 'first_response_at',
        'sla_due_at', 'resolved_at', 'locked_by', 'locked_at', 'csat_score', 'source',
    ];

    protected $casts = [
        'tags' => 'array',
        'first_response_at' => 'datetime',
        'sla_due_at' => 'datetime',
        'resolved_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'assigned_agent_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public static function generateTicketNo(): string
    {
        $date = now()->format('Ymd');
        $count = self::whereDate('created_at', today())->count() + 1;
        return 'T-' . $date . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function slaHoursMap(): array
    {
        return ['urgent' => 4, 'high' => 8, 'medium' => 24, 'low' => 72];
    }
}
