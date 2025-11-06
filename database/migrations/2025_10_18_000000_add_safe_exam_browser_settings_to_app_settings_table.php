<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->boolean('seb_enabled')->default(false);
            $table->string('seb_config_link')->nullable();
            $table->string('seb_browser_exam_key')->nullable();
            $table->string('seb_exit_key_combination')->nullable();
            $table->string('seb_config_password')->nullable();
            $table->string('seb_client_config_path')->nullable();
            $table->text('seb_additional_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'seb_enabled',
                'seb_config_link',
                'seb_browser_exam_key',
                'seb_exit_key_combination',
                'seb_config_password',
                'seb_client_config_path',
                'seb_additional_notes',
            ]);
        });
    }
};
