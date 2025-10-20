<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('borrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code')->unique();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('laptop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('return_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usage_purpose');
            $table->enum('status', ['borrowed', 'returned', 'cancelled'])->default('borrowed')->index();
            $table->boolean('was_late')->default(false)->index();
            $table->timestamp('borrowed_at')->index();
            $table->timestamp('due_at')->index();
            $table->timestamp('returned_at')->nullable()->index();
            $table->unsignedInteger('late_minutes')->nullable();
            $table->text('staff_notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['laptop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('borrow_transactions');
    }
};
