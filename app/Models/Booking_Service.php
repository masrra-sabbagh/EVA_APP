<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking_Service extends Model {
    use HasFactory;

    protected $fillable = [
        'bookingId',
        'servId',
    ];
    public function booking() {
        return $this->belongsTo(Booking::class, 'bookingId');
    }

    public function service() {
        return $this->belongsTo(Service::class, 'servId');
    }
}
