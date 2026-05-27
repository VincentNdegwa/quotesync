<?php

namespace App\Ai\Agents\Domain;

use App\Models\Invoice;
use App\Models\User;
use App\Ai\Tools\Invoice\GetInvoiceInsightsTool;
use App\Ai\Tools\Invoice\GetOverdueInvoicesTool;
use App\Ai\Tools\Invoice\GetPaymentSummaryTool;
use App\Ai\Tools\Invoice\SuggestPaymentReminderTool;
use App\Ai\Tools\Invoice\GenerateCreditNoteTool;
use App\Ai\Tools\Invoice\RecordPaymentTool;
use App\Ai\Tools\Invoice\UpdateInvoiceTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class InvoiceAgent implements Agent, HasTools, CanActAsTool
{
    use Promptable;

    public function __construct(
        public readonly ?Invoice $invoice,
        public readonly User $user,
    ) {}

    /**
     * Get the tool-facing name.
     */
    public function name(): string
    {
        return 'invoice_agent';
    }

    /**
     * Get the tool-facing description.
     */
    public function description(): string
    {
        return 'Specialized agent for invoices and payments. Can track overdue invoices, suggest payment reminders, generate credit notes, and analyze payment patterns.';
    }

    /**
     * Get the instructions for the invoice agent.
     */
    public function instructions(): Stringable|string
    {
        $invoice = $this->invoice;
        $user = $this->user;

        if ($invoice) {
            $statusValue = $invoice->status->value;
            $invoiceNumber = $invoice->invoice_number;
            $invoiceTitle = $invoice->title;

            return <<<PROMPT
            You are the Invoice Intelligence Agent for {$user->name}'s invoice management system.
            You are deeply knowledgeable about this specific invoice and help the user understand,
            manage, and take action on everything related to it.

            ## CURRENT CONTEXT
            - Workspace ID: {$user->current_workspace_id}
            - Logged-in User: {$user->name} ({$user->email})
            - Invoice ID: {$invoice->id}
            - Invoice Number: {$invoiceNumber}
            - Title: {$invoiceTitle}
            - Status: {$statusValue}
            - Total: {$invoice->total}
            - Currency: {$invoice->currency}
            - Today: {now()->toFormattedDateString()}

            ## YOUR RESPONSIBILITIES
            1. **Insights** — Proactively surface patterns, risks, and opportunities from the invoice's data.
               Always lead with the most important insight first.
            2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
            3. **Actions** — You CAN read and write data. Use tools to record payments,
               send reminders, generate credit notes, and update invoice status.
               Always confirm before taking a destructive or irreversible action.

            ## RULES
            - Only operate within this invoice's data. Never touch other invoices.
            - Never delete records. You may update status, record payments, send reminders, and generate credit notes only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving payment advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients), tell them which
              agent handles it and that they can find it on the relevant section of the system.

            ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - When the invoice is overdue or at risk, be proactive about flagging it.
            PROMPT;
        }

        return <<<PROMPT
        You are the Invoice Intelligence Agent for {$user->name}'s invoice management system.
        You have access to all invoices in the workspace and can provide insights across the entire invoice portfolio.

        ## CURRENT CONTEXT
        - Workspace ID: {$user->current_workspace_id}
        - Logged-in User: {$user->name} ({$user->email})
        - Today: {now()->toFormattedDateString()}

        ## YOUR RESPONSIBILITIES
        1. **Insights** — Proactively surface patterns, risks, and opportunities across all invoices.
               Identify trends, overdue invoices, late payers, and cash flow opportunities.
        2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
        3. **Actions** — You CAN read and write data. Use tools to record payments,
               send reminders, generate credit notes, and update invoice status.
               Always confirm before taking a destructive or irreversible action.

        ## RULES
            - Operate within the current workspace only.
            - Never delete records. You may update status, record payments, send reminders, and generate credit notes only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving payment advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients), tell them which
              agent handles it and that they can find it on the relevant section of the system.
            - When analyzing multiple invoices, focus on the most critical issues first (overdue, high value).

        ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - Be proactive about flagging overdue invoices and late payers.
        PROMPT;
    }

    /**
     * Get the tools available to the invoice agent.
     *
     * @return array
     */
    public function tools(): iterable
    {
        return [
            new GetInvoiceInsightsTool($this->invoice, $this->user),
            new GetOverdueInvoicesTool($this->invoice, $this->user),
            new GetPaymentSummaryTool($this->invoice, $this->user),
            new SuggestPaymentReminderTool($this->invoice, $this->user),
            new GenerateCreditNoteTool($this->invoice, $this->user),
            new RecordPaymentTool($this->invoice, $this->user),
            new UpdateInvoiceTool($this->invoice, $this->user),
        ];
    }
}
