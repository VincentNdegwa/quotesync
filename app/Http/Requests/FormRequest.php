<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest as BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Inertia\Inertia;

class FormRequest extends BaseFormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        if ($this->isInertiaRequest()) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => $validator->errors()->first() ?: __('Please fix the highlighted errors and try again.'),
            ]);

            throw new HttpResponseException(back()->withInput($this->input()));
        }

        if ($this->expectsJson() || $this->ajax() || $this->wantsJson()) {
            throw new HttpResponseException(response()->json([
                'message' => $validator->errors()->first() ?: __('The given data was invalid.'),
                'errors' => $validator->errors()->toArray(),
            ], 422));
        }

        parent::failedValidation($validator);
    }

    protected function isInertiaRequest(): bool
    {
        return $this->header('X-Inertia') === 'true';
    }
}
