<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderRequest extends Model {
    use HasFactory;

    protected $fillable = [
        'id_card',
        'ownership',
        'request_status',
        'has_accepted_terms',
        'user_id',
    ];
    protected $appends = [
        'id_card_url',
        'ownership_url',
    ];
    protected $hidden = [
        'id_card',
        'ownership',
    ];
    public function getIdCardUrlAttribute() {
        return $this->id_card ? asset('storage/' . $this->id_card) : null;
    }
    public function getOwnershipUrlAttribute() {
        return $this->ownership ? asset('storage/' . $this->ownership) : null;
    }

    protected $casts = [
        'has_accepted_terms' => 'boolean',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }
}
