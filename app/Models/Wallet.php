<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model {
    use HasFactory;

    protected $fillable = [
        'balance',
        'held_balance',
        'user_id',
    ];

    protected $casts = [
        'balance'      => 'decimal:2',
        'held_balance' => 'decimal:2',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function transactionRequests() {
        return $this->hasMany(TransactionRequest::class);
    }
}
