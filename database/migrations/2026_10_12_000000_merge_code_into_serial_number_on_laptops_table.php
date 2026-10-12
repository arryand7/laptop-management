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
        // 1. Salin nilai code ke serial_number jika serial_number masih kosong
        DB::table('laptops')
            ->whereNull('serial_number')
            ->update([
                'serial_number' => DB::raw('code'),
            ]);

        // 2. Set qr_code agar sama dengan serial_number
        DB::table('laptops')
            ->whereNotNull('serial_number')
            ->update([
                'qr_code' => DB::raw('serial_number'),
            ]);

        // 3. Drop index unique code dan hapus kolom code dari tabel laptops
        Schema::table('laptops', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laptops', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
        });

        DB::table('laptops')->update([
            'code' => DB::raw('serial_number'),
        ]);
    }
};
