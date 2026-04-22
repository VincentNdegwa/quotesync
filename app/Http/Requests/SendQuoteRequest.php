<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'to' => ['required', 'email', 'max:255'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message_body' => ['nullable', 'string'],
            'channel' => ['required', Rule::in(['email'])],
            'schedule_enabled' => ['nullable', 'boolean'],
            'send_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
