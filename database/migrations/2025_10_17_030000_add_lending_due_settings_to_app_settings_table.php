<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('lending_due_mode')->default('relative');
            $table->unsignedTinyInteger('lending_due_days')->nullable();
            $table->time('lending_due_time')->nullable();
            $table->dateTime('lending_due_date')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'lending_due_mode',
                'lending_due_days',
                'lending_due_time',
                'lending_due_date',
            ]);
        });
    }
};
