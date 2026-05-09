<?php

namespace App\Http\Requests\Quotes;

use App\Enums\TrackingEventType;
use App\Http\Requests\FormRequest;

class StoreQuoteTrackingEventsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'events' => ['required', 'array', 'max:50'],
            'events.*.event_type' => ['required', 'string', 'in:'.implode(',', TrackingEventType::values())],
            'events.*.duration_seconds' => ['sometimes', 'integer', 'min:0'],
            'events.*.section_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'events.*.scroll_depth_percent' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'events.*.occurred_at' => ['sometimes', 'date'],
        ];
    }
}
