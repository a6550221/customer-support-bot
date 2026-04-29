<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackingEvent extends Model
{
    protected $fillable = ['order_id', 'text', 'type'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
