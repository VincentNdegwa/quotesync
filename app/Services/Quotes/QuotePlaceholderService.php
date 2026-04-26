<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use App\Models\User;
use App\Models\Workspace;

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
     * Build placeholder data array from quote and context.
     */
    public static function buildPlaceholderData(
        Quote $quote,
        ?Workspace $workspace = null,
        ?User $user = null,
        ?string $quoteLink = null
    ): array {
        $companyName = (string) ($workspace?->display_name ?: $workspace?->name ?: config('app.name'));

        return [
            'quote_number' => (string) ($quote->number ?? 'Draft'),
            'quote_title' => (string) $quote->title,
            'quote_link' => $quoteLink ?? '',
            'client_name' => (string) ($quote->client?->contact_name ?: $quote->client?->company_name ?: 'Client'),
            'client_contact' => (string) ($quote->client?->contact_name ?? ''),
            'client_email' => (string) ($quote->client?->email ?? ''),
            'total' => number_format((float) $quote->total, 2).' '.($quote->currency ?? ''),
            'currency' => (string) ($quote->currency ?? ''),
            'valid_until' => (string) ($quote->valid_until?->toDateString() ?? 'N/A'),
            'issue_date' => (string) ($quote->created_at?->toDateString() ?? 'N/A'),
            'company_name' => $companyName,
            'user_name' => (string) ($user?->name ?? ''),
            'user_email' => (string) ($user?->email ?? ''),
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

    /**
     * Replace placeholders using quote and context directly.
     */
    public static function replacePlaceholdersFromQuote(
        string $template,
        Quote $quote,
        ?Workspace $workspace = null,
        ?User $user = null,
        ?string $quoteLink = null
    ): string {
        $data = self::buildPlaceholderData($quote, $workspace, $user, $quoteLink);
        return self::replacePlaceholders($template, $data);
    }
}
