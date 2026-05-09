<?php

namespace App\Http\Requests\TaskStatuses;

use App\Http\Requests\FormRequest;

class ReorderTaskStatusesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>|string>
     */
    public function rules(): array
    {
        return [
            'taskStatuses' => ['required', 'array'],
            'taskStatuses.*.id' => ['required', 'integer', 'exists:task_statuses,id'],
            'taskStatuses.*.sort_order' => ['required', 'integer', 'min:1'],
        ];
    }
}
