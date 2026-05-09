<?php

namespace App\Http\Requests\Tasks;

use App\Http\Requests\FormRequest;

class UpdateTaskRequest extends FormRequest
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
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['sometimes', 'exists:users,id'],
            'due_date' => ['nullable', 'date'],
            'task_status_id' => ['nullable', 'exists:task_statuses,id'],
            'taskable_type' => ['sometimes', 'string', 'in:quote,invoice'],
            'taskable_id' => ['sometimes', 'integer'],
        ];
    }
}
