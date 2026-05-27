<?php

namespace App\Ai\Tools\Invoice;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SuggestPaymentReminderTool implements Tool
{
    public function __construct(
        private readonly ?Invoice $invoice,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Suggests optimal timing and content for payment reminders based on invoice aging, '
            . 'client payment history, and current balance. Provides a reminder template.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->invoice) {
            return [];
        }

        return [
            'invoice_id' => $schema->integer()
                ->description('The invoice ID to generate a reminder for.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->invoice) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $invoice = $this->invoice;
        $balanceDue = $invoice->total - $invoice->paid_amount;

        if ($balanceDue <= 0) {
            return "Invoice #{$invoice->invoice_number} is fully paid. No reminder needed.";
        }

        $daysSinceDue = $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0;
        $isOverdue = $daysSinceDue > 0;
        $dueDateFormatted = $invoice->due_date ? $invoice->due_date->toFormattedDateString() : 'Not set';
        $statusText = $isOverdue ? 'OVERDUE' : 'Not yet due';

        $output = "Payment Reminder Suggestion for Invoice #{$invoice->invoice_number}\n";
        $output .= "===========================================================\n";
        $output .= "Balance Due: {$balanceDue} {$invoice->currency}\n";
        $output .= "Due Date: {$dueDateFormatted}\n";
        $output .= "Days Since Due: {$daysSinceDue}\n";
        $output .= "Status: {$statusText}\n\n";

        if ($isOverdue) {
            $output .= "Recommended Action: Send a polite overdue reminder immediately.\n\n";
            $output .= "Suggested Message:\n";
            $output .= "Hi there,\n\n";
            $output .= "This is a friendly reminder that invoice #{$invoice->invoice_number} for {$balanceDue} {$invoice->currency} ";
            $output .= "was due on {$dueDateFormatted}.\n\n";
            $output .= "Please let us know if you have any questions or need any assistance.\n\n";
            $output .= "Thank you!";
        } else {
            $output .= "Recommended Action: Wait until due date approaches (3-5 days before).\n\n";
            $output .= "Suggested Message (for closer to due date):\n";
            $output .= "Hi there,\n\n";
            $output .= "Just a friendly reminder that invoice #{$invoice->invoice_number} for {$balanceDue} {$invoice->currency} ";
            $output .= "is due on {$dueDateFormatted}.\n\n";
            $output .= "Thank you for your business!";
        }

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $invoiceId = $request['invoice_id'];

        $invoice = Invoice::where('workspace_id', $this->user->current_workspace_id)
            ->find($invoiceId);

        if (!$invoice) {
            return "Invoice with ID {$invoiceId} not found.";
        }

        $balanceDue = $invoice->total - $invoice->paid_amount;

        if ($balanceDue <= 0) {
            return "Invoice #{$invoice->invoice_number} is fully paid. No reminder needed.";
        }

        $daysSinceDue = $invoice->due_date ? now()->diffInDays($invoice->due_date) : 0;
        $isOverdue = $daysSinceDue > 0;
        $dueDateFormatted = $invoice->due_date ? $invoice->due_date->toFormattedDateString() : 'Not set';
        $statusText = $isOverdue ? 'OVERDUE' : 'Not yet due';

        $output = "Payment Reminder Suggestion for Invoice #{$invoice->invoice_number}\n";
        $output .= "===========================================================\n";
        $output .= "Balance Due: {$balanceDue} {$invoice->currency}\n";
        $output .= "Due Date: {$dueDateFormatted}\n";
        $output .= "Days Since Due: {$daysSinceDue}\n";
        $output .= "Status: {$statusText}\n\n";

        if ($isOverdue) {
            $output .= "Recommended Action: Send a polite overdue reminder immediately.\n\n";
            $output .= "Suggested Message:\n";
            $output .= "Hi there,\n\n";
            $output .= "This is a friendly reminder that invoice #{$invoice->invoice_number} for {$balanceDue} {$invoice->currency} ";
            $output .= "was due on {$dueDateFormatted}.\n\n";
            $output .= "Please let us know if you have any questions or need any assistance.\n\n";
            $output .= "Thank you!";
        } else {
            $output .= "Recommended Action: Wait until due date approaches (3-5 days before).\n\n";
            $output .= "Suggested Message (for closer to due date):\n";
            $output .= "Hi there,\n\n";
            $output .= "Just a friendly reminder that invoice #{$invoice->invoice_number} for {$balanceDue} {$invoice->currency} ";
            $output .= "is due on {$dueDateFormatted}.\n\n";
            $output .= "Thank you for your business!";
        }

        return $output;
    }
}
