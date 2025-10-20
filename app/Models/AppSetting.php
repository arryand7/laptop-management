<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = [
        'site_name',
        'site_description',
        'logo_path',
        'contact_email',
        'contact_phone',
        'contact_address',
        'lending_due_mode',
        'lending_due_days',
        'lending_due_time',
        'lending_due_date',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'ai_default_provider',
        'openai_model',
        'openai_api_key',
        'gemini_model',
        'gemini_api_key',
        'huggingface_model',
        'huggingface_api_key',
    ];
}
