<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->string('sso_base_url')->nullable()->after('seb_additional_notes');
            $table->string('sso_client_id')->nullable()->after('sso_base_url');
            $table->string('sso_client_secret')->nullable()->after('sso_client_id');
            $table->string('sso_redirect_uri')->nullable()->after('sso_client_secret');
            $table->string('sso_scopes')->nullable()->after('sso_redirect_uri');
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn([
                'sso_base_url',
                'sso_client_id',
                'sso_client_secret',
                'sso_redirect_uri',
                'sso_scopes',
            ]);
        });
    }
};
