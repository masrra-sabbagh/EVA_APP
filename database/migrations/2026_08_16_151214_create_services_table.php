<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->enum('category', ['dj', 'decoration', 'lighting', 'photographary']);
            $table->foreignId('userId')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('main_image')->nullable();
            $table->decimal('price_per_day', 10, 2);
            $table->boolean('is_available')->default(true);
            $table->decimal('discount_percentage', 5, 2)->nullable()->default(0);
            $table->string('location');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('services');
    }
};
