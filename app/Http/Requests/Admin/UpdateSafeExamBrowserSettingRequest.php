<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSafeExamBrowserSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('admin.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'seb_enabled' => ['nullable', 'boolean'],
            'seb_config_link' => ['nullable', 'url', 'max:500'],
            'seb_browser_exam_key' => ['nullable', 'string', 'max:255'],
            'seb_exit_key_combination' => ['nullable', 'string', 'max:100'],
            'seb_config_password' => ['nullable', 'string', 'max:255'],
            'seb_additional_notes' => ['nullable', 'string', 'max:2000'],
            'seb_config_file' => ['nullable', 'file', 'max:4096', 'mimes:seb,json,xml,cfg'],
            'seb_remove_config' => ['nullable', 'boolean'],
            'seb_clear_password' => ['nullable', 'boolean'],
        ];
    }
}
