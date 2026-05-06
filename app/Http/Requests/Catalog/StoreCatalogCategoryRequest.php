<?php

namespace App\Http\Requests\Catalog;

use App\Http\Requests\FormRequest;
use App\Models\Workspace;
use Illuminate\Validation\Rule;

class StoreCatalogCategoryRequest extends FormRequest
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

        return [
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('catalog_categories', 'name')
                    ->where(fn ($query) => $query
                        ->where('workspace_id', $workspace?->id)
                        ->whereNull('deleted_at')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
