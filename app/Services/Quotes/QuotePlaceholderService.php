<?php

namespace App\Services\Quotes;

class QuotePlaceholderService
{
    /**
     * Available placeholders for quote follow-up messages.
     * Keys are the placeholder names (without braces), values are descriptions.
     */
    public static function getAvailablePlaceholders(): array
    {
        return [
            'quote_number' => 'Quote number (e.g., QUO-001)',
            'quote_title' => 'Quote title',
            'quote_link' => 'Public link to view the quote',
            'client_name' => 'Client company name',
            'client_contact' => 'Client contact person name',
            'client_email' => 'Client email address',
            'total' => 'Quote total amount',
            'currency' => 'Quote currency code',
            'valid_until' => 'Quote valid until date',
            'issue_date' => 'Quote issue date',
            'company_name' => 'Your company name',
            'user_name' => 'Your name (sender)',
            'user_email' => 'Your email address',
        ];
    }

    /**
     * Get placeholder descriptions for UI display.
     */
    public static function getPlaceholderDescriptions(): array
    {
        return self::getAvailablePlaceholders();
    }

    /**
     * Extract all placeholders from a template string.
     */
    public static function extractPlaceholders(string $template): array
    {
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $template, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Validate that all placeholders in the template are allowed.
     */
    public static function validatePlaceholders(string $template): array
    {
        $placeholders = self::extractPlaceholders($template);
        $allowed = array_keys(self::getAvailablePlaceholders());
        $invalid = array_diff($placeholders, $allowed);

        return [
            'valid' => empty($invalid),
            'invalid' => array_values($invalid),
            'all' => $placeholders,
        ];
    }

    /**
     * Replace placeholders with actual quote data.
     */
    public static function replacePlaceholders(string $template, array $data): string
    {
        $placeholders = self::getAvailablePlaceholders();
        
        foreach ($placeholders as $key => $description) {
            $value = $data[$key] ?? '';
            $template = str_replace('{' . $key . '}', $value, $template);
        }

        return $template;
    }
}
