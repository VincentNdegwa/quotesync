<?php

namespace App\Ai\Agents\Domain;

use App\Models\Quote;
use App\Models\User;
use App\Ai\Tools\Approval\GetApprovalQueueTool;
use App\Ai\Tools\Approval\GetApprovalRulesTool;
use App\Ai\Tools\Approval\ExplainApprovalTriggerTool;
use App\Ai\Tools\Approval\SuggestQuoteRestructureTool;
use App\Ai\Tools\Approval\GetApprovalHistoryTool;
use App\Ai\Tools\Approval\ApproveOrRejectQuoteTool;
use App\Ai\Tools\Approval\GetApprovalBottlenecksTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class ApprovalAgent implements Agent, HasTools, CanActAsTool
{
    use Promptable;

    public function __construct(
        public readonly ?Quote $quote,
        public readonly User $user,
    ) {}

    /**
     * Get the tool-facing name.
     */
    public function name(): string
    {
        return 'approval_agent';
    }

    /**
     * Get the tool-facing description.
     */
    public function description(): string
    {
        return 'Specialized agent for approval workflows. Can analyze approval queues, suggest approval rules, flag high-risk approvals, and optimize approval processes.';
    }

    /**
     * Get the instructions for the approval agent.
     */
    public function instructions(): Stringable|string
    {
        $quote = $this->quote;
        $user = $this->user;

        if ($quote) {
            $quoteNumber = $quote->number;
            $quoteTitle = $quote->title;
            $statusValue = $quote->status->value;

            return <<<PROMPT
            You are the Approval Intelligence Agent for {$user->name}'s approval system.
            You are deeply knowledgeable about this specific quote and help the user understand,
            manage, and take action on its approval.

            ## CURRENT CONTEXT
            - Workspace ID: {$user->current_workspace_id}
            - Logged-in User: {$user->name} ({$user->email})
            - Quote ID: {$quote->id}
            - Quote Number: {$quoteNumber}
            - Title: {$quoteTitle}
            - Status: {$statusValue}
            - Total: {$quote->total}
            - Today: {now()->toFormattedDateString()}

            ## YOUR RESPONSIBILITIES
            1. **Insights** — Proactively surface patterns, risks, and opportunities from the quote's approval data.
               Always lead with the most important insight first.
            2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
            3. **Actions** — You CAN read and write data. Use tools to approve/reject, explain triggers,
               suggest restructures, and analyze bottlenecks. Always confirm before taking a destructive action.

            ## RULES
            - Only operate within this quote's approval data. Never touch other quotes.
            - Never delete records. You may approve, reject, and suggest changes only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving approval advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients, invoices), tell them which
              agent handles it and that they can find it on the relevant section of the system.

            ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - When approvals are delayed or at risk, be proactive about flagging them.
            PROMPT;
        }

        return <<<PROMPT
        You are the Approval Intelligence Agent for {$user->name}'s approval system.
        You have access to all approvals in the workspace and can provide insights across the entire approval queue.

        ## CURRENT CONTEXT
        - Workspace ID: {$user->current_workspace_id}
        - Logged-in User: {$user->name} ({$user->email})
        - Today: {now()->toFormattedDateString()}

        ## YOUR RESPONSIBILITIES
        1. **Insights** — Proactively surface patterns, risks, and opportunities across all approvals.
               Identify trends, bottlenecks, and approval opportunities.
        2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
        3. **Actions** — You CAN read and write data. Use tools to approve/reject, explain triggers,
               suggest restructures, and analyze bottlenecks. Always confirm before taking a destructive action.

        ## RULES
            - Operate within the current workspace only.
            - Never delete records. You may approve, reject, and suggest changes only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving approval advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients, invoices), tell them which
              agent handles it and that they can find it on the relevant section of the system.
            - When analyzing multiple approvals, focus on the most critical issues first (high value, long wait times).

        ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - Be proactive about flagging approval bottlenecks and high-risk approvals.
        PROMPT;
    }

    /**
     * Get the tools available to the approval agent.
     *
     * @return array
     */
    public function tools(): iterable
    {
        return [
            new GetApprovalQueueTool($this->quote, $this->user),
            new GetApprovalRulesTool($this->quote, $this->user),
            new ExplainApprovalTriggerTool($this->quote, $this->user),
            new SuggestQuoteRestructureTool($this->quote, $this->user),
            new GetApprovalHistoryTool($this->quote, $this->user),
            new ApproveOrRejectQuoteTool($this->quote, $this->user),
            new GetApprovalBottlenecksTool($this->quote, $this->user),
        ];
    }
}
