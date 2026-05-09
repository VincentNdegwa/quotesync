<?php

namespace App\Http\Requests\Portal;

use App\Http\Requests\FormRequest;

class SendPortalInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
        ];
    }
}
