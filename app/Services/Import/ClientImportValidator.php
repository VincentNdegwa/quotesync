<?php

namespace App\Services\Import;

class ClientImportValidator
{
    public function validate(array $row, int $lineNumber): array
    {
        $errors = [];

        if (empty($row['company_name'])) {
            $errors[] = 'Company name is required';
        }

        if (! empty($row['email']) && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (! empty($row['country']) && ! $this->isValidCountry($row['country'])) {
            $errors[] = 'Invalid country code (use 2-letter ISO code)';
        }

        return [
            'line' => $lineNumber,
            'data' => $row,
            'errors' => $errors,
            'valid' => empty($errors),
        ];
    }

    private function isValidCountry(string $code): bool
    {
        $validCountries = [
            'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'ES', 'IT', 'NL', 'BE',
            'CH', 'AT', 'DK', 'SE', 'NO', 'FI', 'PL', 'CZ', 'HU', 'RO',
            'BG', 'GR', 'PT', 'IE', 'LU', 'JP', 'CN', 'IN', 'BR', 'AR',
            'MX', 'ZA', 'NZ', 'SG', 'MY', 'TH', 'VN', 'ID', 'PH', 'KR',
            'TW', 'HK', 'AE', 'SA', 'QA', 'KW', 'IL', 'TR', 'RU', 'UA',
        ];

        return in_array(strtoupper(trim($code)), $validCountries, true);
    }
}
