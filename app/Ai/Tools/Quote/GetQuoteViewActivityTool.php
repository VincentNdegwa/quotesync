<?php

namespace App\Ai\Tools\Quote;

use App\Models\Quote;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class GetQuoteViewActivityTool implements Tool
{
    public function __construct(
        private readonly ?Quote $quote,
        private readonly User $user,
    ) {}

    public function description(): string
    {
        return 'Returns the view history for a quote: when it was sent, how many times it was opened, '
            . 'time between send and first view, time between last view and now. '
            . 'Useful for gauging client intent.';
    }

    public function schema(JsonSchema $schema): array
    {
        if ($this->quote) {
            return [];
        }

        return [
            'quote_id' => $schema->integer()
                ->description('The quote ID to get view activity for.')
                ->required(),
        ];
    }

    public function handle(Request $request): string
    {
        if ($this->quote) {
            return $this->handleSingle();
        }

        return $this->handleWorkspace($request);
    }

    private function handleSingle(): string
    {
        $quote = $this->quote;

        $output = "View Activity for Quote #{$quote->number}\n";
        $output .= "====================================\n";
        $output .= "Status: {$quote->status->value}\n";
        $output .= "Created: {$quote->created_at->toFormattedDateString()}\n";

        if ($quote->sent_at) {
            $output .= "Sent: {$quote->sent_at->toFormattedDateString()}\n";
        } else {
            $output .= "Sent: Not sent yet\n";
        }

        if ($quote->viewed_at) {
            $output .= "First Viewed: {$quote->viewed_at->toFormattedDateString()}\n";

            if ($quote->sent_at) {
                $daysToFirstView = $quote->sent_at->diffInDays($quote->viewed_at);
                $output .= "Time to first view: {$daysToFirstView} days\n";
            }
        } else {
            $output .= "First Viewed: Not viewed yet\n";
        }

        if ($quote->updated_at) {
            $output .= "Last Activity: {$quote->updated_at->toFormattedDateString()}\n";
        }

        $output .= "\nEngagement Level: ";

        if ($quote->status->value === 'viewed') {
            $output .= "High - Client has viewed the quote\n";
        } elseif ($quote->status->value === 'sent') {
            $output .= "Pending - Quote sent but not yet viewed\n";
        } elseif ($quote->status->value === 'won') {
            $output .= "Converted - Quote was won\n";
        } elseif ($quote->status->value === 'lost') {
            $output .= "Lost - Quote was lost\n";
        } else {
            $output .= "Unknown\n";
        }

        return $output;
    }

    private function handleWorkspace(Request $request): string
    {
        $quoteId = $request['quote_id'];

        $quote = Quote::where('workspace_id', $this->user->current_workspace_id)
            ->find($quoteId);

        if (!$quote) {
            return "Quote with ID {$quoteId} not found.";
        }

        $output = "View Activity for Quote #{$quote->number}\n";
        $output .= "====================================\n";
        $output .= "Status: {$quote->status->value}\n";
        $output .= "Created: {$quote->created_at->toFormattedDateString()}\n";

        if ($quote->sent_at) {
            $output .= "Sent: {$quote->sent_at->toFormattedDateString()}\n";
        } else {
            $output .= "Sent: Not sent yet\n";
        }

        if ($quote->viewed_at) {
            $output .= "First Viewed: {$quote->viewed_at->toFormattedDateString()}\n";

            if ($quote->sent_at) {
                $daysToFirstView = $quote->sent_at->diffInDays($quote->viewed_at);
                $output .= "Time to first view: {$daysToFirstView} days\n";
            }
        } else {
            $output .= "First Viewed: Not viewed yet\n";
        }

        if ($quote->updated_at) {
            $output .= "Last Activity: {$quote->updated_at->toFormattedDateString()}\n";
        }

        $output .= "\nEngagement Level: ";

        if ($quote->status->value === 'viewed') {
            $output .= "High - Client has viewed the quote\n";
        } elseif ($quote->status->value === 'sent') {
            $output .= "Pending - Quote sent but not yet viewed\n";
        } elseif ($quote->status->value === 'won') {
            $output .= "Converted - Quote was won\n";
        } elseif ($quote->status->value === 'lost') {
            $output .= "Lost - Quote was lost\n";
        } else {
            $output .= "Unknown\n";
        }

        return $output;
    }
}
