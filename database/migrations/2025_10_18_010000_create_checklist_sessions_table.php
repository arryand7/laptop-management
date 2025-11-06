<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('total_laptops')->default(0);
            $table->unsignedInteger('found_count')->default(0);
            $table->unsignedInteger('missing_count')->default(0);
            $table->unsignedInteger('borrowed_count')->default(0);
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_sessions');
    }
};
