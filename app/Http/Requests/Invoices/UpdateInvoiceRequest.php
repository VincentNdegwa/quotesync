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
        $user = $this->user();

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
            'sections.*.line_items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'sections.*.line_items.*.discount_percent' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'sections.*.line_items.*.price_tier_applied' => ['nullable', 'boolean'],
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
            'sections.*.line_items.*.taxes.*.inclusive' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $workspace = $this->user()?->currentWorkspace;
                $user = $this->user();

                $maxDiscount = null;
                if ($user && $workspace) {
                    $pivot = $user->roles()->wherePivot('workspace_id', $workspace->id)->first()?->pivot;
                    $maxDiscount = $pivot->max_discount_percent ?? null;
                }

                $sections = $this->input('sections', []);
                foreach ($sections as $sectionIndex => $section) {
                    if (! isset($section['line_items'])) {
                        continue;
                    }

                    foreach ($section['line_items'] as $lineItemIndex => $lineItem) {
                        // Skip validation if price tier was applied (admin-set pricing)
                        if (isset($lineItem['price_tier_applied']) && $lineItem['price_tier_applied']) {
                            continue;
                        }

                        // Only validate manual discounts against max discount limit
                        if ($maxDiscount !== null && isset($lineItem['discount_percent']) && $lineItem['discount_percent'] > $maxDiscount) {
                            $validator->errors()->add(
                                "sections.{$sectionIndex}.line_items.{$lineItemIndex}.discount_percent",
                                "Discount cannot exceed {$maxDiscount}%."
                            );
                        }
                    }
                }
            },
        ];
    }
}
