<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLendingSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasModule('admin.settings') ?? false;
    }

    public function rules(): array
    {
        $mode = $this->input('lending_due_mode');

        return [
            'lending_due_mode' => ['required', Rule::in(['relative', 'daily', 'fixed'])],
            'lending_due_days' => [
                Rule::requiredIf($mode === 'relative'),
                'nullable',
                'integer',
                'min:1',
                'max:30',
            ],
            'lending_due_time' => [
                Rule::requiredIf($mode === 'relative'),
                'nullable',
                'date_format:H:i',
            ],
            'lending_due_time_daily' => [
                Rule::requiredIf($mode === 'daily'),
                'nullable',
                'date_format:H:i',
            ],
            'lending_due_date' => [
                Rule::requiredIf($mode === 'fixed'),
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }
}
