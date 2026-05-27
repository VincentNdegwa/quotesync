<?php

namespace App\Ai\Agents\Domain;

use App\Models\User;
use App\Ai\Tools\Team\GetDailyBriefingTool;
use App\Ai\Tools\Team\GetTasksTool;
use App\Ai\Tools\Team\CreateTaskTool;
use App\Ai\Tools\Team\UpdateTaskTool;
use App\Ai\Tools\Team\GetWorkloadSummaryTool;
use App\Ai\Tools\Team\GetTeamMembersTool;
use App\Ai\Tools\Team\GetWorkspaceSummaryTool;
use App\Ai\Tools\Team\AssignQuoteToUserTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class TeamAgent implements Agent, HasTools, CanActAsTool
{
    use Promptable;

    public function __construct(public User $user) {}

    /**
     * Get the tool-facing name.
     */
    public function name(): string
    {
        return 'team_agent';
    }

    /**
     * Get the tool-facing description.
     */
    public function description(): string
    {
        return 'Specialized agent for team tasks and dashboard. Can generate daily briefings, analyze workload, suggest task prioritization, and optimize team productivity.';
    }

    /**
     * Get the instructions for the team agent.
     */
    public function instructions(): Stringable|string
    {
        $user = $this->user;

        return <<<PROMPT
        You are the Team Intelligence Agent for {$user->name}'s team operations system.
        You have access to all team data in the workspace and can provide insights across the entire team.

        ## CURRENT CONTEXT
        - Workspace ID: {$user->current_workspace_id}
        - Logged-in User: {$user->name} ({$user->email})
        - Today: {now()->toFormattedDateString()}

        ## YOUR RESPONSIBILITIES
        1. **Insights** — Proactively surface patterns, risks, and opportunities across team operations.
               Identify trends, workload imbalances, and productivity opportunities.
        2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
        3. **Actions** — You CAN read and write data. Use tools to create tasks, update workloads,
               assign quotes, and generate briefings. Always confirm before taking a destructive action.

        ## RULES
            - Operate within the current workspace only.
            - Never delete records. You may create, update, and reassign tasks only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving team advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients, invoices), tell them which
              agent handles it and that they can find it on the relevant section of the system.
            - When analyzing team workload, focus on the most critical issues first (overloaded members, overdue tasks).

        ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - Be proactive about flagging workload imbalances and productivity opportunities.
        PROMPT;
    }

    /**
     * Get the tools available to the team agent.
     *
     * @return array
     */
    public function tools(): iterable
    {
        return [
            new GetDailyBriefingTool($this->user),
            new GetTasksTool($this->user),
            new CreateTaskTool($this->user),
            new UpdateTaskTool($this->user),
            new GetWorkloadSummaryTool($this->user),
            new GetTeamMembersTool($this->user),
            new GetWorkspaceSummaryTool($this->user),
            new AssignQuoteToUserTool($this->user),
        ];
    }
}
