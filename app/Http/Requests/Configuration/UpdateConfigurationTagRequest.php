<?php

namespace App\Http\Requests\Configuration;

use App\Models\ConfigurationTag;
use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfigurationTagRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $workspace = $user?->currentWorkspace;

        if (! $user || ! $workspace instanceof Workspace) {
            return false;
        }

        return $user->belongsToWorkspace($workspace)
            && ($workspace->owner_id === $user->id || $user->hasRole('admin', $workspace) || $user->hasRole('manager', $workspace));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Workspace|null $workspace */
        $workspace = $this->user()?->currentWorkspace;
        /** @var ConfigurationTag|null $tag */
        $tag = $this->route('tag');

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('configuration_tags', 'name')
                    ->ignore($tag?->id)
                    ->where(fn ($query) => $query
                        ->where('workspace_id', $workspace?->id)
                        ->whereNull('deleted_at')),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
