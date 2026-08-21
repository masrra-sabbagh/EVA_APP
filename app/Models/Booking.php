<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model {
    use HasFactory;

    protected $fillable = [
        'userId',
        'booking_status',
        'start_date',
        'end_date',
        'total_price',
        'paid_amount',
        'is_refunded',
        'eventId',
        'extra_services'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
        'total_price' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'is_refunded' => 'boolean',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }


    public function services() {
        return $this->belongsToMany(Service::class, 'booking_services', 'bookingId', 'servId')
            ->withTimestamps();
    }

    public function event() {
        return $this->belongsTo(Event::class, 'eventId');
    }
}
