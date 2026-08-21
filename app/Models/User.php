<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'phone_number',
        'profile_image',
        'work_or_study',
        'password',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $appends = ['profile_image_url'];
    protected $hidden = [
        'password',
        'remember_token',
        'profile_image',
    ];
    public function getProfileImageUrlAttribute() {
        return $this->profile_image
            ? asset('storage/' . $this->profile_image)
            : null;
    }
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    public function roles() {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function verificationCodes() {
        return $this->hasMany(VerificationCode::class);
    }

    public function notifications() {
        return $this->hasMany(Notification
        ::class);
    }

    public function providerRequests() {
        return $this->hasMany(ProviderRequest::class);
    }

    public function wallet() {
        return $this->hasOne(Wallet::class);
    }

    public function services() {
        return $this->hasMany(Service::class, 'userId');
    }

    public function bookings() {
        return $this->hasMany(Booking::class, 'userId');
    }

    public function reviews() {
        return $this->hasMany(Review::class);
    }
    public function favoriteServices() {
        return $this->belongsToMany(Service::class, 'favorites', 'user_id', 'service_id')
            ->withTimestamps();
    }

    public function events() {
        return $this->hasMany(Event::class);
    }

    /* public function userEvents()
    {
        return $this->hasMany(UserEvent::class);
    }
 */
    public function hasRole(string $roleType): bool {
        return $this->roles()->where('role_type', $roleType)->exists();
    }
}
