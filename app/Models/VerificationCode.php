<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model {
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'phone_change',
        'is_used',
        'expired_at',
        'user_id',
    ];

    protected $casts = [
        'is_used'    => 'boolean',
        'expired_at' => 'datetime',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool {
        return now()->greaterThan($this->expired_at);
    }
    public function providerRequests() {
        return $this->hasMany(ProviderRequest::class);
    }
}
