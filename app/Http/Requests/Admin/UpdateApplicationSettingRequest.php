<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApplicationSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('admin.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_description' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:100'],
            'contact_address' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'timezone'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'sso_base_url' => ['nullable', 'url', 'max:255'],
            'sso_client_id' => ['nullable', 'string', 'max:255'],
            'sso_client_secret' => ['nullable', 'string', 'max:255'],
            'sso_redirect_uri' => ['nullable', 'url', 'max:255'],
            'sso_scopes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
