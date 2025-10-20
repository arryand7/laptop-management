<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('card_code')->nullable()->unique()->after('student_number');
        });

        DB::table('users')
            ->whereNotNull('qr_code')
            ->update([
                'card_code' => DB::raw('qr_code'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['card_code']);
            $table->dropColumn('card_code');
        });
    }
};
