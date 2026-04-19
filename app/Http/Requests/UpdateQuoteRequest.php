<?php

namespace App\Http\Requests;

use App\Models\Workspace;
use Illuminate\Validation\Rule;

class UpdateQuoteRequest extends FormRequest
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
                || $user->hasRole('manager', $workspace)
                || $user->hasRole('rep', $workspace));
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
            'number' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'sent', 'viewed', 'won', 'lost', 'expired'])],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace?->id)
                    ->whereNull('deleted_at')),
            ],
            'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'currency' => ['nullable', 'string', 'size:3'],
            'valid_until' => ['nullable', 'date'],
            'cover_message' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'layout' => ['nullable', 'array'],
            'layout_snapshot' => ['nullable', 'array'],
            'template_id' => [
                'nullable',
                'integer',
                Rule::exists('quote_templates', 'id')->where('workspace_id', $workspace?->id),
            ],
            'requires_deposit' => ['nullable', 'boolean'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.line_items' => ['required', 'array'],
            'sections.*.line_items.*.catalog_item_id' => [
                'nullable',
                'integer',
                Rule::exists('catalog_items', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace?->id)
                    ->whereNull('deleted_at')),
            ],
            'sections.*.line_items.*.name' => ['required', 'string', 'max:255'],
            'sections.*.line_items.*.description' => ['nullable', 'string'],
            'sections.*.line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'sections.*.line_items.*.unit' => ['nullable', 'string', 'max:30'],
            'sections.*.line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'sections.*.line_items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sections.*.line_items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'sections.*.line_items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'sections.*.line_items.*.total' => ['nullable', 'numeric', 'min:0'],
            'sections.*.line_items.*.is_optional' => ['nullable', 'boolean'],
            'sections.*.line_items.*.notes' => ['nullable', 'string'],
            'sections.*.line_items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'sections.*.line_items.*.taxes' => ['nullable', 'array'],
            'sections.*.line_items.*.taxes.*.tax_id' => [
                'nullable',
                'integer',
                Rule::exists('taxes', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace?->id)
                    ->whereNull('deleted_at')),
            ],
            'sections.*.line_items.*.taxes.*.tax_label' => ['required', 'string', 'max:120'],
            'sections.*.line_items.*.taxes.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
