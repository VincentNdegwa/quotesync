<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GenerateCreditNoteTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Generates a credit note for an invoice. Requires invoice_id, amount, and reason. '
            . 'The credit note is created as a draft for review before finalizing.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [
                'amount' => $schema->number()
                    ->description('The amount to credit.')
                    ->required(),
                'reason' => $schema->string()
                    ->description('The reason for the credit note.')
                    ->required(),
            ];
        }

        return [
            'invoice_id' => $schema->integer()
                ->description('The invoice ID to generate a credit note for.')
                ->required(),
            'amount' => $schema->number()
                ->description('The amount to credit.')
                ->required(),
            'reason' => $schema->string()
                ->description('The reason for the credit note.')
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
        $amount = $request['amount'];
        $reason = $request['reason'];

        $balanceDue = $invoice->total - $invoice->paid_amount;

        if ($amount > $balanceDue) {
            return "Error: Credit note amount ({$amount}) exceeds balance due ({$balanceDue}).";
        }

        $output = "Credit Note Preview for Invoice #{$invoice->invoice_number}\n";
        $output .= "=====================================================\n";
        $output .= "Invoice ID: {$invoice->id}\n";
        $output .= "Invoice Total: {$invoice->total} {$invoice->currency}\n";
        $output .= "Balance Due: {$balanceDue} {$invoice->currency}\n";
        $output .= "Credit Amount: {$amount} {$invoice->currency}\n";
        $output .= "Reason: {$reason}\n";
        $output .= "\nNote: This is a preview. Confirm with the user before creating the credit note.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $invoiceId = $request['invoice_id'];
        $amount = $request['amount'];
        $reason = $request['reason'];

        $invoice = Invoice::where('workspace_id', $this->user->current_workspace_id)
            ->find($invoiceId);

        if (!$invoice) {
            return "Invoice with ID {$invoiceId} not found.";
        }

        $balanceDue = $invoice->total - $invoice->paid_amount;

        if ($amount > $balanceDue) {
            return "Error: Credit note amount ({$amount}) exceeds balance due ({$balanceDue}).";
        }

        $output = "Credit Note Preview for Invoice #{$invoice->invoice_number}\n";
        $output .= "=====================================================\n";
        $output .= "Invoice ID: {$invoice->id}\n";
        $output .= "Invoice Total: {$invoice->total} {$invoice->currency}\n";
        $output .= "Balance Due: {$balanceDue} {$invoice->currency}\n";
        $output .= "Credit Amount: {$amount} {$invoice->currency}\n";
        $output .= "Reason: {$reason}\n";
        $output .= "\nNote: This is a preview. Confirm with the user before creating the credit note.";

        return $output;
    }
}
