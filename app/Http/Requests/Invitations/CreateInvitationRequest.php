<?php

namespace App\Http\Requests\Invitations;

use App\Models\Role;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $user?->currentWorkspace;

        if (! $user || ! $workspace instanceof Workspace) {
            return false;
        }

        return $user->belongsToWorkspace($workspace)
            && ($workspace->owner_id === $user->id || $user->hasRole('admin', $workspace));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Workspace|null $workspace */
        $workspace = $this->user()?->currentWorkspace;

        return [
            'email' => ['required', 'email', 'max:255'],
            'role_id' => [
                'required',
                Rule::exists((new Role)->getTable(), 'id')
                    ->where(fn ($query) => $query
                        ->where('workspace_id', $workspace?->id)
                        ->orWhereNull('workspace_id')),
            ],
        ];
    }
}
