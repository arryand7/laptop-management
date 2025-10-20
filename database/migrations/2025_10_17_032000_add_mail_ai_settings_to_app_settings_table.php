<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('smtp_host')->nullable();
            $table->unsignedSmallInteger('smtp_port')->nullable();
            $table->string('smtp_encryption')->nullable();
            $table->string('smtp_username')->nullable();
            $table->string('smtp_password')->nullable();

            $table->string('ai_default_provider')->nullable();
            $table->string('openai_model')->nullable();
            $table->text('openai_api_key')->nullable();
            $table->text('gemini_api_key')->nullable();
            $table->text('huggingface_api_key')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'smtp_host',
                'smtp_port',
                'smtp_encryption',
                'smtp_username',
                'smtp_password',
                'ai_default_provider',
                'openai_model',
                'openai_api_key',
                'gemini_api_key',
                'huggingface_api_key',
            ]);
        });
    }
};
