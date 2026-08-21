<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('event_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_name');
            $table->dateTime('due_date');
            $table->enum('priority', ['high', 'medium', 'normal'])->default('normal');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('eventId')->constrained('events')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('event_tasks');
    }
};
