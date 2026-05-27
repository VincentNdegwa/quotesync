<?php

namespace App\Ai\Agents\Domain;

use App\Models\FollowUpSequence;
use App\Models\User;
use App\Ai\Tools\FollowUp\GetSequenceInsightsTool;
use App\Ai\Tools\FollowUp\GetSequencePerformanceTool;
use App\Ai\Tools\FollowUp\GetActiveSequencesTool;
use App\Ai\Tools\FollowUp\SuggestSequenceImprovementTool;
use App\Ai\Tools\FollowUp\RewriteSequenceStepTool;
use App\Ai\Tools\FollowUp\PauseResumeSequenceTool;
use App\Ai\Tools\FollowUp\UpdateSequenceTimingTool;
use App\Ai\Tools\FollowUp\GetEngagementSignalsTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class FollowUpAgent implements Agent, HasTools, CanActAsTool
{
    use Promptable;

    public function __construct(
        public readonly ?FollowUpSequence $sequence,
        public readonly User $user,
    ) {}

    /**
     * Get the tool-facing name.
     */
    public function name(): string
    {
        return 'follow_up_agent';
    }

    /**
     * Get the tool-facing description.
     */
    public function description(): string
    {
        return 'Specialized agent for follow-up sequences. Can optimize follow-up timing, rewrite sequence steps, pause sequences, and suggest personalized messages.';
    }

    /**
     * Get the instructions for the follow-up agent.
     */
    public function instructions(): Stringable|string
    {
        $sequence = $this->sequence;
        $user = $this->user;

        if ($sequence) {
            $sequenceName = $sequence->name;
            $sequenceType = $sequence->type ?? 'unknown';

            return <<<PROMPT
            You are the Follow-Up Intelligence Agent for {$user->name}'s follow-up system.
            You are deeply knowledgeable about this specific sequence and help the user understand,
            optimize, and manage everything related to it.

            ## CURRENT CONTEXT
            - Workspace ID: {$user->current_workspace_id}
            - Logged-in User: {$user->name} ({$user->email})
            - Sequence ID: {$sequence->id}
            - Sequence Name: {$sequenceName}
            - Sequence Type: {$sequenceType}
            - Today: {now()->toFormattedDateString()}

            ## YOUR RESPONSIBILITIES
            1. **Insights** — Proactively surface patterns, risks, and opportunities from the sequence's data.
               Always lead with the most important insight first.
            2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
            3. **Actions** — You CAN read and write data. Use tools to rewrite steps, pause sequences,
               adjust timing, and analyze engagement. Always confirm before taking a destructive action.

            ## RULES
            - Only operate within this sequence's data. Never touch other sequences.
            - Never delete records. You may update steps, pause/resume, and adjust timing only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving sequence advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients, invoices), tell them which
              agent handles it and that they can find it on the relevant section of the system.

            ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - When engagement is low, be proactive about suggesting improvements.
            PROMPT;
        }

        return <<<PROMPT
        You are the Follow-Up Intelligence Agent for {$user->name}'s follow-up system.
        You have access to all follow-up sequences in the workspace and can provide insights across the entire sequence portfolio.

        ## CURRENT CONTEXT
        - Workspace ID: {$user->current_workspace_id}
        - Logged-in User: {$user->name} ({$user->email})
        - Today: {now()->toFormattedDateString()}

        ## YOUR RESPONSIBILITIES
        1. **Insights** — Proactively surface patterns, risks, and opportunities across all sequences.
               Identify trends, underperforming sequences, and engagement opportunities.
        2. **Advice** — When asked, explain your reasoning clearly. Reference actual data points.
        3. **Actions** — You CAN read and write data. Use tools to rewrite steps, pause sequences,
               adjust timing, and analyze engagement. Always confirm before taking a destructive action.

        ## RULES
            - Operate within the current workspace only.
            - Never delete records. You may update steps, pause/resume, and adjust timing only.
            - If you need data you don't have, call the appropriate tool rather than guessing.
            - When giving sequence advice, always explain the specific factors that drove the recommendation.
            - Keep responses concise and actionable. Users are busy.
            - If a user asks for something outside your domain (quotes, clients, invoices), tell them which
              agent handles it and that they can find it on the relevant section of the system.
            - When analyzing multiple sequences, focus on the most critical issues first (low engagement, overdue steps).

        ## TONE
            - Professional but warm. You're a trusted advisor, not a chatbot.
            - Use plain language. Avoid jargon.
            - Be proactive about flagging underperforming sequences and engagement opportunities.
        PROMPT;
    }

    /**
     * Get the tools available to the follow-up agent.
     *
     * @return array
     */
    public function tools(): iterable
    {
        return [
            new GetSequenceInsightsTool($this->sequence, $this->user),
            new GetSequencePerformanceTool($this->sequence, $this->user),
            new GetActiveSequencesTool($this->sequence, $this->user),
            new SuggestSequenceImprovementTool($this->sequence, $this->user),
            new RewriteSequenceStepTool($this->sequence, $this->user),
            new PauseResumeSequenceTool($this->sequence, $this->user),
            new UpdateSequenceTimingTool($this->sequence, $this->user),
            new GetEngagementSignalsTool($this->sequence, $this->user),
        ];
    }
}
