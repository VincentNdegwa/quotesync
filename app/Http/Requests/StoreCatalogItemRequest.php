<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Validation\Rule;

class StoreCatalogItemRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('catalog_items', 'sku')
                    ->where(fn ($query) => $query
                        ->where('workspace_id', $workspace?->id)
                        ->whereNull('deleted_at')),
            ],
            'unit' => ['required', Rule::in(['hr', 'day', 'unit', 'sqm', 'kg', 'm', 'lot', 'month'])],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'catalog_category_id' => ['nullable', 'integer', Rule::exists('catalog_categories', 'id')->where('workspace_id', $workspace?->id)],
            'tax_ids' => ['nullable', 'array'],
            'tax_ids.*' => [
                'integer',
                Rule::exists('taxes', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace?->id)
                    ->whereNull('deleted_at')),
            ],
            'is_active' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
