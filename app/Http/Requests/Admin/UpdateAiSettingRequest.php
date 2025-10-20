<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('admin.settings') ?? false;
    }

    public function rules(): array
    {
        return [
            'ai_default_provider' => ['nullable', Rule::in(['openai', 'gemini', 'huggingface'])],
            'openai_model' => ['nullable', 'string', 'max:100'],
            'openai_api_key' => ['nullable', 'string'],
            'gemini_model' => ['nullable', 'string', 'max:100'],
            'gemini_api_key' => ['nullable', 'string'],
            'huggingface_model' => ['nullable', 'string', 'max:150'],
            'huggingface_api_key' => ['nullable', 'string'],
        ];
    }
}
