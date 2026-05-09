<?php

namespace App\Http\Requests\Tasks;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class TaskBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Rule|string>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', Rule::in(['delete', 'update_status'])],
            'task_status_id' => ['required_if:action,update_status', 'integer'],
        ];
    }
}
