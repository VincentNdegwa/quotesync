<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class WritingAssistantAgent implements Agent
{
    use Promptable;

    public function __construct(
        public string $action,
        public ?string $locale = null
    ) {}

    public function instructions(): Stringable|string
    {
        $instructions = match ($this->action) {
            'clearer' => 'Rewrite the text to be clearer and more concise.',
            'formal' => 'Rewrite the text to be more professional and formal.',
            'friendly' => 'Rewrite the text to be friendlier and more conversational.',
            'shorter' => 'Rewrite the text to be shorter while preserving meaning.',
            'rewrite' => 'Rewrite the text from scratch, improving clarity and tone.',
            default => 'Improve the text.',
        };

        if ($this->locale) {
            $instructions .= " Translate the text to {$this->locale}.";
        }

        return $instructions;
    }
}
