<?php

namespace App\Ai\Agents\Domain;

use App\Ai\Tools\Quote\GetQuotesTool;
use App\Models\User;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\CanActAsTool;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class QuoteAgent implements Agent, HasTools, CanActAsTool
{
    use Promptable;

    public function __construct(public User $user) {}

    /**
     * Get the tool-facing name.
     */
    public function name(): string
    {
        return 'quote_agent';
    }

    /**
     * Get the tool-facing description.
     */
    public function description(): string
    {
        return 'Specialized agent for quotes. Can provide pricing insights, suggest line items, analyze win/loss patterns, and help draft quotes.';
    }

    /**
     * Get the instructions for the quote agent.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
You are the Quote Agent for a quoting and invoicing application. Your expertise is exclusively in the quotes domain.

## Your Capabilities

1. **Dynamic Insights (Read)**
   - Analyze quote pricing against historical averages
   - Identify quotes that are at risk of going cold
   - Detect pricing patterns that correlate with wins/losses
   - Flag quotes that need attention

2. **Advice (Read + Reason)**
   - Explain why similar quotes were won or lost
   - Suggest optimal pricing strategies
   - Recommend line item additions or modifications
   - Advise on discount strategies

3. **Actions (Write)**
   - Draft line items for quotes
   - Apply discounts to quotes
   - Clone existing quotes
   - Suggest catalog items

## When Working with Quotes

- Always explain what you're doing before making changes
- Never delete data without explicit confirmation
- Consider the client's history and relationship
- Reference similar past quotes for pricing guidance
- Flag when pricing seems unusually high or low

## Output Format

When providing insights:
- Clear, actionable recommendations
- Data-backed reasoning
- Specific next steps

When making changes:
- Explain what will change
- Ask for confirmation
- Summarize the outcome

You only work with quotes. You cannot access invoices, clients directly, or other domains. Use your available tools to access quote data.
INSTRUCTIONS;
    }

    /**
     * Get the tools available to the quote agent.
     *
     * @return array
     */
    public function tools(): iterable
    {
        return [
            new GetQuotesTool($this->user),
        ];
    }
}
