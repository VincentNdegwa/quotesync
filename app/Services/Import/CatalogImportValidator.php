<?php

namespace App\Services\Import;

class CatalogImportValidator
{
    public function validate(array $row, int $lineNumber): array
    {
        $errors = [];

        if (empty($row['name'])) {
            $errors[] = 'Name is required';
        }

        if (!empty($row['unit_price']) && !is_numeric($row['unit_price'])) {
            $errors[] = 'Unit price must be a number';
        }

        if (!empty($row['cost_price']) && !is_numeric($row['cost_price'])) {
            $errors[] = 'Cost price must be a number';
        }

        if (!empty($row['unit']) && !$this->isValidUnit($row['unit'])) {
            $errors[] = 'Invalid unit (must be one of: hr, day, unit, sqm, kg, m, lot, month)';
        }

        return [
            'line' => $lineNumber,
            'data' => $row,
            'errors' => $errors,
            'valid' => empty($errors),
        ];
    }

    private function isValidUnit(string $unit): bool
    {
        $validUnits = ['hr', 'day', 'unit', 'sqm', 'kg', 'm', 'lot', 'month'];

        return in_array(strtolower(trim($unit)), $validUnits, true);
    }
}
