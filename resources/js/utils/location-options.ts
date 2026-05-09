import { countries } from 'countries-list';
import cc from 'currency-codes';
import type { CurrencyOption } from '@/types';

export type CountryOption = {
    code: string;
    label: string;
    currency: string;
};

export type LanguageOption = {
    code: string;
    label: string;
};

export const countryOptions: CountryOption[] = Object.entries(countries)
    .map(([code, country]) => ({
        code,
        label: country.name,
        currency: country.currency[0] || 'USD',
    }))
    .sort((a, b) => a.label.localeCompare(b.label));

const COMMON_COUNTRY_CODES = [
    'US',
    'GB',
    'CA',
    'AU',
    'DE',
    'FR',
    'IT',
    'ES',
    'NL',
    'BE',
    'CH',
    'SE',
    'NO',
    'DK',
    'IE',
    'AE',
    'SA',
    'IN',
    'SG',
    'NG',
] as const;

const countrySearchCache = new Map<string, CountryOption[]>();

const mapCommonCountries = (options: CountryOption[]): CountryOption[] => {
    const optionByCode = new Map(
        options.map((country) => [country.code, country]),
    );

    return COMMON_COUNTRY_CODES.map((code) => optionByCode.get(code)).filter(
        (country): country is CountryOption => country !== undefined,
    );
};

export const commonCountryOptions: CountryOption[] =
    mapCommonCountries(countryOptions);

export const filterCountryOptions = (query: string): CountryOption[] => {
    const normalizedQuery = query.trim().toLowerCase();

    if (normalizedQuery === '') {
        return countryOptions;
    }

    const cached = countrySearchCache.get(normalizedQuery);

    if (cached) {
        return cached;
    }

    const filtered = countryOptions.filter((country) =>
        country.label.toLowerCase().includes(normalizedQuery),
    );

    countrySearchCache.set(normalizedQuery, filtered);

    return filtered;
};

export const getCountryOptions = (
    query: string,
    commonLimit = 20,
    options: CountryOption[] = countryOptions,
): CountryOption[] => {
    const normalizedQuery = query.trim();

    if (normalizedQuery === '') {
        if (options === countryOptions) {
            return commonCountryOptions.slice(0, Math.max(commonLimit, 1));
        }

        return options.slice(0, Math.max(commonLimit, 1));
    }

    if (options === countryOptions) {
        return filterCountryOptions(normalizedQuery);
    }

    const needle = normalizedQuery.toLowerCase();

    return options.filter((country) => {
        return (
            country.label.toLowerCase().includes(needle) ||
            country.code.toLowerCase().includes(needle)
        );
    });
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

const COMMON_CURRENCY_CODES = [
    'USD',
    'EUR',
    'GBP',
    'CAD',
    'AUD',
    'JPY',
    'CNY',
    'INR',
    'NGN',
    'CHF',
    'SEK',
    'NOK',
    'DKK',
    'SGD',
    'AED',
    'SAR',
    'ZAR',
    'NZD',
    'HKD',
    'BRL',
] as const;

const currencySearchCache = new Map<string, CurrencyOption[]>();

const mapCommonCurrencies = (options: CurrencyOption[]): CurrencyOption[] => {
    const optionByCode = new Map(
        options.map((currency) => [currency.code, currency]),
    );

    return COMMON_CURRENCY_CODES.map((code) => optionByCode.get(code)).filter(
        (currency): currency is CurrencyOption => currency !== undefined,
    );
};

export const commonCurrencyOptions: CurrencyOption[] =
    mapCommonCurrencies(currencyOptions);

export const filterCurrencyOptions = (query: string): CurrencyOption[] => {
    const normalizedQuery = query.trim().toLowerCase();

    if (normalizedQuery === '') {
        return currencyOptions;
    }

    const cached = currencySearchCache.get(normalizedQuery);

    if (cached) {
        return cached;
    }

    const filtered = currencyOptions.filter((currency) => {
        return (
            currency.label.toLowerCase().includes(normalizedQuery) ||
            currency.code.toLowerCase().includes(normalizedQuery)
        );
    });

    currencySearchCache.set(normalizedQuery, filtered);

    return filtered;
};

export const getCurrencyOptions = (
    query: string,
    commonLimit = 20,
    options: CurrencyOption[] = currencyOptions,
): CurrencyOption[] => {
    const normalizedQuery = query.trim();

    if (normalizedQuery === '') {
        if (options === currencyOptions) {
            return commonCurrencyOptions.slice(0, Math.max(commonLimit, 1));
        }

        return options.slice(0, Math.max(commonLimit, 1));
    }

    if (options === currencyOptions) {
        return filterCurrencyOptions(normalizedQuery);
    }

    const needle = normalizedQuery.toLowerCase();

    return options.filter((currency) => {
        return (
            currency.label.toLowerCase().includes(needle) ||
            currency.code.toLowerCase().includes(needle)
        );
    });
};

export const translationLanguageOptions: LanguageOption[] = [
    { code: 'en', label: 'English' },
    { code: 'fr', label: 'French' },
    { code: 'es', label: 'Spanish' },
    { code: 'pt', label: 'Portuguese' },
];

const languageSearchCache = new Map<string, LanguageOption[]>();

export const filterLanguageOptions = (query: string): LanguageOption[] => {
    const normalizedQuery = query.trim().toLowerCase();

    if (normalizedQuery === '') {
        return translationLanguageOptions;
    }

    const cached = languageSearchCache.get(normalizedQuery);

    if (cached) {
        return cached;
    }

    const filtered = translationLanguageOptions.filter((language) => {
        return (
            language.label.toLowerCase().includes(normalizedQuery) ||
            language.code.toLowerCase().includes(normalizedQuery)
        );
    });

    languageSearchCache.set(normalizedQuery, filtered);

    return filtered;
};

export const getLanguageOptions = (
    query: string,
    options: LanguageOption[] = translationLanguageOptions,
): LanguageOption[] => {
    const normalizedQuery = query.trim();

    if (normalizedQuery === '') {
        return options;
    }

    if (options === translationLanguageOptions) {
        return filterLanguageOptions(normalizedQuery);
    }

    const needle = normalizedQuery.toLowerCase();

    return options.filter((language) => {
        return (
            language.label.toLowerCase().includes(needle) ||
            language.code.toLowerCase().includes(needle)
        );
    });
};
