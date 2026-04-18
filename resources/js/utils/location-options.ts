import { countries } from 'countries-list';
import cc from 'currency-codes';
import type { CurrencyOption } from '@/types';

export type CountryOption = {
    code: string;
    label: string;
    currency: string;
};

export const countryOptions: CountryOption[] = Object.entries(countries)
    .map(([code, country]) => ({
        code,
        label: country.name,
        currency: country.currency?.[0] ?? 'USD',
    }))
    .sort((a, b) => a.label.localeCompare(b.label));

export const filterCountryOptions = (query: string): CountryOption[] => {
    const normalizedQuery = query.trim().toLowerCase();

    if (normalizedQuery === '') {
        return countryOptions;
    }

    return countryOptions.filter((country) =>
        country.label.toLowerCase().includes(normalizedQuery),
    );
};

export const currencyOptions: CurrencyOption[] = cc
    .codes()
    .map((code) => {
        const details = cc.code(code);

        return {
            code,
            label: `${code} - ${details?.currency ?? ''}`,
        };
    })
    .sort((a, b) => a.code.localeCompare(b.code));
