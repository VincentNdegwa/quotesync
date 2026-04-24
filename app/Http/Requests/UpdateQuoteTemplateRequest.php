<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Validation\Rule;

class UpdateQuoteTemplateRequest extends FormRequest
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
            && ($workspace->owner_id === $user->id
                || $user->hasRole('admin', $workspace)
                || $user->hasRole('manager', $workspace));
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'industry' => ['nullable', 'string', 'max:120'],
            'cover_message' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'layout' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.line_items' => ['nullable', 'array'],
        ];
    }

    /**
     * Get validated data with field mapping from QuoteBuilderState to template fields.
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        // Map QuoteBuilderState fields to template fields
        return [
            'name' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'industry' => $data['industry'] ?? null,
            'cover_message' => $data['cover_message'] ?? null,
            'terms' => $data['terms'] ?? null,
            'notes' => $data['notes'] ?? null,
            'layout' => $data['layout'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sections' => $data['sections'] ?? [],
        ];
    }
}
