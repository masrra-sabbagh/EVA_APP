<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model {
    use HasFactory;

    protected $fillable = [
        'description',
        'nature',
        'category',
        'city',
        'area',
        'start_date',
        'end_date',
        'userId',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /**
     * العلاقة مع المستخدم (منشئ الفعالية)
     */
    public function user() {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * العلاقة مع الحجوزات
     */
    public function bookings() {
        return $this->hasMany(Booking::class, 'eventId');
    }


    public function tasks() {
        return $this->hasMany(EventTask::class, 'eventId');
    }

    /**
     * المستخدمون الحاضرون (للفعاليات الثابتة)
     */
    public function attendees() {
        return $this->belongsToMany(User::class, 'users_events', 'event_id', 'user_id')
            ->withTimestamps();
    }
}
