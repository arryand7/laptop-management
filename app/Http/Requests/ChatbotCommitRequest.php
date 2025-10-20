<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChatbotCommitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('chatbot') ?? false;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'uuid'],
        ];
    }
}
