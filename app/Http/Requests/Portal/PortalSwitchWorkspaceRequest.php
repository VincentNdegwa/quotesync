<?php

namespace App\Http\Requests\Portal;

use App\Http\Requests\FormRequest;

class PortalSwitchWorkspaceRequest extends FormRequest
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
            'workspace_id' => ['required', 'exists:workspaces,id'],
        ];
    }
}
