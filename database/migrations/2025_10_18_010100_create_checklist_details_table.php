<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklist_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_session_id')->constrained('checklist_sessions')->cascadeOnDelete();
            $table->foreignId('laptop_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['found', 'missing', 'borrowed'])->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['checklist_session_id', 'laptop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklist_details');
    }
};
