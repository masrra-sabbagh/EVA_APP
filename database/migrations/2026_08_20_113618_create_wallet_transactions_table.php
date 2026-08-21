<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', [
                'deposit',
                'withdraw',
                'booking_hold',
                'booking_refund',
                'booking_forfeit',
                'commission',
                'payout',
            ]);
            $table->decimal('amount', 12, 2);
            $table->nullableMorphs('reference');
            $table->decimal('balance_after', 12, 2);
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
    }
};
