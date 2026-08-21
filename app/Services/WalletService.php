<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

class WalletService {
    public function credit(Wallet $wallet, string $type, float $amount, $reference = null): WalletTransaction {
        return DB::transaction(function () use ($wallet, $type, $amount, $reference) {
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            $wallet->increment('balance', $amount);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'balance_after' => $wallet->fresh()->balance,
            ]);
        });
    }

    public function debit(Wallet $wallet, string $type, float $amount, $reference = null): WalletTransaction {
        return DB::transaction(function () use ($wallet, $type, $amount, $reference) {
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient balance.');
            }

            $wallet->decrement('balance', $amount);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => $type,
                'amount' => $amount,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference?->id,
                'balance_after' => $wallet->fresh()->balance,
            ]);
        });
    }

    public function holdForBooking(Wallet $wallet, float $amount, $booking = null): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $booking) {
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                throw new \Exception('Insufficient balance.');
            }

            $wallet->decrement('balance', $amount);
            $wallet->increment('held_balance', $amount);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'booking_hold',
                'amount' => $amount,
                'reference_type' => $booking ? get_class($booking) : null,
                'reference_id' => $booking?->id,
                'balance_after' => $wallet->fresh()->balance,
            ]);
        });
    }

    public function refundHold(Wallet $wallet, float $amount, $booking = null): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $booking) {
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            $wallet->decrement('held_balance', $amount);
            $wallet->increment('balance', $amount);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'booking_refund',
                'amount' => $amount,
                'reference_type' => $booking ? get_class($booking) : null,
                'reference_id' => $booking?->id,
                'balance_after' => $wallet->fresh()->balance,
            ]);
        });
    }

    public function forfeitHold(Wallet $wallet, float $amount, $booking = null): WalletTransaction {
        return DB::transaction(function () use ($wallet, $amount, $booking) {
            $wallet = Wallet::whereKey($wallet->id)->lockForUpdate()->first();

            $wallet->decrement('held_balance', $amount);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'booking_forfeit',
                'amount' => $amount,
                'reference_type' => $booking ? get_class($booking) : null,
                'reference_id' => $booking?->id,
                'balance_after' => $wallet->fresh()->balance,
            ]);
        });
    }
}
