<?php

namespace App\Ai\Agents\Domain;

use App\Ai\Tools\Client\GetClientsTool;
use App\Ai\Tools\Client\CreateClient;
use App\Ai\Tools\Client\GetClientInsightsTool;
use App\Ai\Tools\Client\GetClientQuoteHistoryTool;
use App\Ai\Tools\Client\GetClientRiskScoreTool;
use App\Ai\Tools\Client\GetClientPaymentBehaviourTool;
use App\Ai\Tools\Client\SuggestFollowUpActionTool;
use App\Ai\Tools\Client\UpdateClientProfileTool;
use App\Models\Client;
use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class ClientAgent implements Agent, HasTools, CanActAsTool
{
    use Promptable;

    public function __construct(
        public readonly ?Client $client,
        public readonly User $user,
    ) {}

    /**
     * Get the tool-facing name.
     */
    public function name(): string
    {
        return 'client_agent';
    }

    /**
     * Get the tool-facing description.
     */
    public function description(): string
    {
        return 'Specialized agent for clients and CRM. Can assess client risk, analyze payment patterns, suggest relationship strategies, and help manage client profiles.';
    }

    /**
     * Get the instructions for the client agent.
     */
    public function instructions(): Stringable|string
    {
        $client = $this->client;
        $user = $this->user;

        if ($client) {
            // Single client context
            $primaryContact = $client->primaryContact;
            $primaryContactName = $primaryContact ? $primaryContact->name : 'Not set';
            $primaryContactEmail = $primaryContact ? $primaryContact->email : 'Not set';

            return <<<PROMPT
            You are the Client Intelligence Agent for {$user->name}'s quote management system.
            You are deeply knowledgeable about this specific client and help the user understand,
            manage, and take action on everything related to them.

            ## CURRENT CONTEXT
            - Workspace ID: {$user->current_workspace_id}
            - Logged-in User: {$user->name} ({$user->email})
            - Client ID: {$client->id}
            - Client: {$client->company_name}
            - Primary Contact: {$primaryContactName} ({$primaryContactEmail})
            - Contact Name: {$client->contact_name}
            - Email: {$client->email}
            - Currency: {$client->currency}
            - Health Score: {$client->health_score}/100
            - Today: {now()->toFormattedDateString()}

            ## YOUR RESPONSIBILITIES
            1. **Insights** — Proactively surface patterns, risks, and opportunities from the client's data.
               Always lead with the most important insight first.
            2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points
               (e.g. "your last 3 quotes to this client were lost — all above $5,000").
            3. **Actions** — You CAN read and write data. Use tools to update the client profile,
               add notes, tag the client, create tasks, or enrich missing information.
               Always confirm before taking a destructive or irreversible action.

            ## RULES
            - Only operate within this client's data. Never touch other clients.
            - Never delete records. You may update, tag, add notes, and create tasks only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving a risk assessment, always explain the specific factors that drove the score.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (invoices, quotes), tell them which
              agent handles it and that they can find it on the relevant section of the system.

            ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - When the health score is below 40, be proactive about flagging risk.
            PROMPT;
        }

        // All clients context
        return <<<PROMPT
        You are the Client Intelligence Agent for {$user->name}'s quote management system.
        You have access to all clients in the workspace and can provide insights across the entire client base.

        ## CURRENT CONTEXT
        - Workspace ID: {$user->current_workspace_id}
        - Logged-in User: {$user->name} ({$user->email})
        - Today: {now()->toFormattedDateString()}

        ## YOUR RESPONSIBILITIES
        1. **Insights** — Proactively surface patterns, risks, and opportunities across all clients.
               Identify trends, high-risk clients, and opportunities for improvement.
        2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points
               (e.g. "3 clients have health scores below 40 — all have overdue invoices").
        3. **Actions** — You CAN read and write data. Use tools to create new clients,
               update client profiles, add notes, tag clients, and create tasks.
               Always confirm before taking a destructive or irreversible action.

        ## RULES
            - Operate within the current workspace only.
            - Never delete records. You may update, tag, add notes, and create tasks only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving a risk assessment, always explain the specific factors that drove the score.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (invoices, quotes), tell them which
              agent handles it and that they can find it on the relevant section of the system.
            - When analyzing multiple clients, focus on the most critical issues first.

        ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - Be proactive about flagging at-risk clients.
        PROMPT;
    }

    /**
     * Get the tools available to the client agent.
     *
     * @return array
     */
    public function tools(): iterable
    {
        $tools = [
            new GetClientsTool($this->user),
            new CreateClient($this->user),
        ];

        if ($this->client) {
            $tools[] = new GetClientInsightsTool($this->client, $this->user);
            $tools[] = new GetClientQuoteHistoryTool($this->client, $this->user);
            $tools[] = new GetClientRiskScoreTool($this->client, $this->user);
            $tools[] = new GetClientPaymentBehaviourTool($this->client, $this->user);
            $tools[] = new SuggestFollowUpActionTool($this->client, $this->user);
            $tools[] = new UpdateClientProfileTool($this->client, $this->user);
        } else {
            $tools[] = new GetClientInsightsTool(null, $this->user);
            $tools[] = new GetClientQuoteHistoryTool(null, $this->user);
            $tools[] = new GetClientRiskScoreTool(null, $this->user);
            $tools[] = new GetClientPaymentBehaviourTool(null, $this->user);
            $tools[] = new SuggestFollowUpActionTool(null, $this->user);
        }

        return $tools;
    }
}
