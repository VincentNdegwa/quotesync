<?php

namespace App\Http\Requests\InvoiceReminders;

use App\Http\Requests\FormRequest;

class UpdateInvoiceReminderSequenceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'steps' => ['required', 'array'],
            'steps.*.id' => ['sometimes', 'integer', 'exists:invoice_reminder_steps,id'],
            'steps.*.day_offset' => ['required', 'integer'],
            'steps.*.channel' => ['required', 'string', 'in:email'],
            'steps.*.reminder_type' => ['required', 'string', 'in:before_due,on_due,after_due'],
            'steps.*.subject' => ['required', 'string', 'max:255'],
            'steps.*.message_template' => ['required', 'string'],
            'steps.*.send_automatically' => ['required', 'boolean'],
            'steps.*.sort_order' => ['required', 'integer'],
        ];
    }
}
