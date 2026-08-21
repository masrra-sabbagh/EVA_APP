<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionRequest extends Model {
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'status',
        'amount',
        'transfer_number',
        'receipt_image',
        'withdraw_destination',
        'rejection_reason',
    ];
    protected $hidden = ['receipt_image'];
    protected $appends = ['receipt_image_url'];
    public function getReceiptImageUrlAttribute() {
        return $this->receipt_image ? asset('storage/' . $this->receipt_image) : null;
    }

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function wallet() {
        return $this->belongsTo(Wallet::class);
    }
}
