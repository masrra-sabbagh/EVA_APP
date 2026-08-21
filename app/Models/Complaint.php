<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'provider_id',
        'content',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function provider() {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function booking() {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
}
