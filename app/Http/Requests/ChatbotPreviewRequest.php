<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('chatbot') ?? false;
    }

    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'max:255'],
        ];
    }
}
