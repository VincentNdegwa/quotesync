<?php

namespace App\Http\Requests\Quotes;

use App\Enums\QuoteStatus;
use App\Http\Requests\FormRequest;
use App\Models\Workspace;
use Illuminate\Validation\Rule;

class UpdateQuoteStatusRequest extends FormRequest
{
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

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(array_column(QuoteStatus::cases(), 'value'))],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
