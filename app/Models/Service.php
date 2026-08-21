<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model {

    use HasFactory;

    protected $fillable = [
        'userId',
        'category',
        'title',
        'description',
        'main_image',
        'price_per_day',
        'is_available',
        'discount_percentage',
        'location'
    ];
    protected $hidden = ['main_image',];
    protected $appends = ['main_image_url'];
    public function getMainImageUrlAttribute() {
        return $this->main_image ? asset('storage/' . $this->main_image) : null;
    }

    protected $casts = [
        'is_available' => 'boolean',
        'price_per_day' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
    ];



    public function provider() {
        return $this->belongsTo(User::class, 'userId');
    }

    public function bookings() {
        return $this->belongsToMany(Booking::class, 'booking_services', 'servId', 'bookingId')
            ->withTimestamps();
    }

    public function reviews() {
        return $this->hasMany(Review::class, 'service_id');
    }
    public function favoritedBy() {
        return $this->belongsToMany(User::class, 'favorites', 'service_id', 'user_id')
            ->withTimestamps();
    }
}
