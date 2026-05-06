<?php

namespace App\Http\Requests\Invoices;

use App\Enums\InvoiceStatus;
use App\Http\Requests\FormRequest;
use App\Models\Workspace;
use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends FormRequest
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
            'invoice_number' => ['nullable', 'string', 'max:60'],
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_column(InvoiceStatus::cases(), 'value'))],
            'cover_message' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'layout' => ['nullable', 'array'],
            'layout_snapshot' => ['nullable', 'array'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.catalog_item_id' => [
                'nullable',
                'integer',
                Rule::exists('catalog_items', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace?->id)
                    ->whereNull('deleted_at')),
            ],
            'line_items.*.name' => ['required', 'string', 'max:255'],
            'line_items.*.description' => ['nullable', 'string'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit' => ['nullable', 'string', 'max:30'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.total' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'line_items.*.notes' => ['nullable', 'string'],
            'line_items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'line_items.*.taxes' => ['nullable', 'array'],
            'line_items.*.taxes.*.tax_id' => [
                'nullable',
                'integer',
                Rule::exists('taxes', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $workspace?->id)
                    ->whereNull('deleted_at')),
            ],
            'line_items.*.taxes.*.tax_label' => ['required', 'string', 'max:120'],
            'line_items.*.taxes.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'line_items.*.taxes.*.inclusive' => ['nullable', 'boolean'],
        ];
    }
}
