<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class UpdateInvoiceTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Updates allowed fields on an invoice. Does NOT change status. '
            . 'Only updates fields the user has explicitly confirmed.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [
                'fields' => $schema->object()
                    ->description('Fields to update.')
                    ->required(),
            ];
        }

        return [
            'invoice_id' => $schema->integer()
                ->description('The invoice ID to update.')
                ->required(),
            'fields' => $schema->object()
                ->description('Fields to update.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->invoice) {
            return $this->handleSingle($request);
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(Request $request): string
    {
        $invoice = $this->invoice;
        $fields = $request['fields'] ?? [];

        if (empty($fields)) {
            return 'No fields provided to update.';
        }

        $allowedFields = ['title', 'due_date', 'discount_amount', 'notes', 'terms'];
        $updates = [];

        foreach ($fields as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[$key] = $value;
            }
        }

        if (empty($updates)) {
            return 'No valid fields provided. Allowed fields: ' . implode(', ', $allowedFields);
        }

        $output = "Preview of changes to Invoice #{$invoice->invoice_number}:\n";
        $output .= "============================================\n";

        foreach ($updates as $key => $value) {
            $oldValue = $invoice->$key ?? 'Not set';
            $output .= "- {$key}: '{$oldValue}' → '{$value}'\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying changes.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $invoiceId = $request['invoice_id'];
        $fields = $request['fields'] ?? [];

        $invoice = Invoice::where('workspace_id', $this->user->current_workspace_id)
            ->find($invoiceId);

        if (!$invoice) {
            return "Invoice with ID {$invoiceId} not found.";
        }

        if (empty($fields)) {
            return 'No fields provided to update.';
        }

        $allowedFields = ['title', 'due_date', 'discount_amount', 'notes', 'terms'];
        $updates = [];

        foreach ($fields as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[$key] = $value;
            }
        }

        if (empty($updates)) {
            return 'No valid fields provided. Allowed fields: ' . implode(', ', $allowedFields);
        }

        $output = "Preview of changes to Invoice #{$invoice->invoice_number}:\n";
        $output .= "============================================\n";

        foreach ($updates as $key => $value) {
            $oldValue = $invoice->$key ?? 'Not set';
            $output .= "- {$key}: '{$oldValue}' → '{$value}'\n";
        }

        $output .= "\nNote: This is a preview. Confirm with the user before applying changes.";

        return $output;
    }
}
