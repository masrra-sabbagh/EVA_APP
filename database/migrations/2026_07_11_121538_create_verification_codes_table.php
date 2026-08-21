<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('type');
            $table->string('phone_change')->nullable();
            $table->boolean('is_used')->default(false);
            $table->timestamp('expired_at');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('verification_codes');
    }
};
