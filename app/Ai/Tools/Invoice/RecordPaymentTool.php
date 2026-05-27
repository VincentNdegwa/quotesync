<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class RecordPaymentTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Records a payment against an invoice. Requires invoice_id, amount, and payment_date. '
            . 'Updates the paid_amount field and optionally changes status to paid if fully paid.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [
                'amount' => $schema->number()
                    ->description('The payment amount.')
                    ->required(),
                'payment_date' => $schema->string()
                    ->description('ISO date format. Default today.')
                    ->nullable(),
            ];
        }

        return [
            'invoice_id' => $schema->integer()
                ->description('The invoice ID to record payment for.')
                ->required(),
            'amount' => $schema->number()
                ->description('The payment amount.')
                ->required(),
            'payment_date' => $schema->string()
                ->description('ISO date format. Default today.')
                ->nullable(),
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
        $paymentDate = $request['payment_date'] ?? now()->toDateString();

        $balanceDue = $invoice->total - $invoice->paid_amount;

        if ($amount > $balanceDue) {
            return "Error: Payment amount ({$amount}) exceeds balance due ({$balanceDue}).";
        }

        $newPaidAmount = $invoice->paid_amount + $amount;
        $newBalance = $invoice->total - $newPaidAmount;
        $willBeFullyPaid = $newBalance <= 0.01;

        $output = "Payment Recording Preview for Invoice #{$invoice->invoice_number}\n";
        $output .= "==========================================================\n";
        $output .= "Current Paid: {$invoice->paid_amount} {$invoice->currency}\n";
        $output .= "Payment Amount: {$amount} {$invoice->currency}\n";
        $output .= "New Paid Amount: {$newPaidAmount} {$invoice->currency}\n";
        $output .= "New Balance: {$newBalance} {$invoice->currency}\n";
        $output .= "Payment Date: {$paymentDate}\n";
        $output .= "Status: " . ($willBeFullyPaid ? 'Will be marked as PAID' : 'Will remain partially paid') . "\n";
        $output .= "\nNote: This is a preview. Confirm with the user before recording the payment.";

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $invoiceId = $request['invoice_id'];
        $amount = $request['amount'];
        $paymentDate = $request['payment_date'] ?? now()->toDateString();

        $invoice = Invoice::where('workspace_id', $this->user->current_workspace_id)
            ->find($invoiceId);

        if (!$invoice) {
            return "Invoice with ID {$invoiceId} not found.";
        }

        $balanceDue = $invoice->total - $invoice->paid_amount;

        if ($amount > $balanceDue) {
            return "Error: Payment amount ({$amount}) exceeds balance due ({$balanceDue}).";
        }

        $newPaidAmount = $invoice->paid_amount + $amount;
        $newBalance = $invoice->total - $newPaidAmount;
        $willBeFullyPaid = $newBalance <= 0.01;

        $output = "Payment Recording Preview for Invoice #{$invoice->invoice_number}\n";
        $output .= "==========================================================\n";
        $output .= "Current Paid: {$invoice->paid_amount} {$invoice->currency}\n";
        $output .= "Payment Amount: {$amount} {$invoice->currency}\n";
        $output .= "New Paid Amount: {$newPaidAmount} {$invoice->currency}\n";
        $output .= "New Balance: {$newBalance} {$invoice->currency}\n";
        $output .= "Payment Date: {$paymentDate}\n";
        $output .= "Status: " . ($willBeFullyPaid ? 'Will be marked as PAID' : 'Will remain partially paid') . "\n";
        $output .= "\nNote: This is a preview. Confirm with the user before recording the payment.";

        return $output;
    }
}
