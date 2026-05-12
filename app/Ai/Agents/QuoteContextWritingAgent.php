<?php

namespace App\Ai\Agents;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

class QuoteContextWritingAgent implements Agent
{
    use Promptable;

    public function __construct(
        public string $blockType,
        public ?array $quoteContext = null,
        public ?string $locale = null,
        public ?string $customPrompt = null
    ) {}

    public function instructions(): Stringable|string
    {
        $blockInstructions = match ($this->blockType) {
            'cover_message' => 'Write a professional cover message for a quote. Keep it concise and direct. Do NOT use email format (no Subject line, no Dear, no Best regards, no signatures). Do NOT use placeholders like [Client Name] or [Project Name]. Do NOT use examples with "e.g." or similar. Do NOT include a section header or title like "Cover Message". Write the actual content as if it will appear directly on the quote document.',
            'terms' => 'Write professional terms and conditions for a quote. Keep it concise and clear. Include standard clauses about payment, changes to scope, warranties, and liability. Do NOT use placeholders or examples with "e.g.". Do NOT include a section header or title like "Terms and Conditions" or "Terms". Write the actual terms.',
            'notes' => 'Write helpful notes for the quote. Include any important details about the work, timeline, or special considerations. Keep it concise. Do NOT use placeholders or examples. Do NOT include a section header or title like "Notes".',
            'payment_terms' => 'Write clear payment terms for the quote. Include deposit requirements, payment schedule, and accepted payment methods. Keep it concise. Do NOT use placeholders or examples. Do NOT include a section header or title like "Payment Terms".',
            default => 'Write professional text for this quote section. Keep it concise and direct. Do NOT use placeholders or examples. Do NOT include a section header or title.',
        };

        $instructions = "You are writing content for a business quote. {$blockInstructions}";

        if ($this->customPrompt) {
            $instructions .= "\n\nCustom instruction (this overrides the default style guidelines above): {$this->customPrompt}";
        } else {
            $instructions .= "\n\nStyle guidelines: Write naturally and directly as if this is the final content that will appear on the quote. Avoid email formatting, placeholders, or example text.";
        }

        if ($this->quoteContext) {
            $contextParts = [];
            if (isset($this->quoteContext['client'])) {
                $client = $this->quoteContext['client'];
                $companyName = $client['company_name'] ?? 'Unknown';
                $contextParts[] = "Client: {$companyName}";
                if (isset($client['email'])) {
                    $contextParts[] = "Client Email: {$client['email']}";
                }
            }

            if (isset($this->quoteContext['total'])) {
                $currency = $this->quoteContext['currency'] ?? 'USD';
                $contextParts[] = "Total Amount: {$currency} {$this->quoteContext['total']}";
            }

            if (isset($this->quoteContext['line_items']) && is_array($this->quoteContext['line_items']) && count($this->quoteContext['line_items']) > 0) {
                $contextParts[] = 'Line Items:';
                foreach ($this->quoteContext['line_items'] as $item) {
                    $itemName = $item['name'] ?? 'Item';
                    $quantity = $item['quantity'] ?? 1;
                    $unitPrice = $item['unit_price'] ?? 0;
                    $contextParts[] = "- {$itemName}: {$quantity} x {$unitPrice}";
                }
            }

            if (! empty($contextParts)) {
                $instructions .= "\n\nQuote Context:\n".implode("\n", $contextParts);
            }
        }

        if ($this->locale) {
            $instructions .= "\n\nTranslate the text to {$this->locale}.";
        }

        \Log::info("Ai write instruction", [
            'instruction' => $instructions
        ]);

        return $instructions;
    }
}
