import { usePage } from '@inertiajs/vue3';

export function useFormat(defaultCurrency?: string) {
    const page = usePage();
    const localization = (page.props.localization as Record<string, any>) || {};

    const getDateFormat = () => localization.date_format || 'MMM d, yyyy';
    const getTimeFormat = () => localization.time_format || 'h:mm a';
    const getCurrencyPosition = () => localization.currency_position || 'before';
    const getNumberFormat = () => localization.number_format || '1,234.56';
    const getTimezone = () => localization.timezone || 'UTC';

    const formatCurrency = (val: number | string | null | undefined, currency?: string): string => {
        const n = Number(val || 0);
        const currencyCode = currency || defaultCurrency || 'USD';
        const position = getCurrencyPosition();
        const formatted = new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(n);

        // Handle currency position if needed (Intl.NumberFormat handles most cases)
        return formatted;
    };

    const formatDate = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const dateFormat = getDateFormat();

        // Convert date format to locale options
        const options: Intl.DateTimeFormatOptions = {};
        if (dateFormat.includes('d')) {
            options.day = 'numeric';
        }
        if (dateFormat.includes('M') || dateFormat.includes('MMM') || dateFormat.includes('MMMM')) {
            options.month = dateFormat.includes('MMMM') ? 'long' : dateFormat.includes('MMM') ? 'short' : 'numeric';
        }
        if (dateFormat.includes('y') || dateFormat.includes('Y')) {
            options.year = 'numeric';
        }

        return date.toLocaleDateString(undefined, options);
    };

    const formatTime = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const timeFormat = getTimeFormat();

        const options: Intl.DateTimeFormatOptions = {};
        if (timeFormat.includes('h') || timeFormat.includes('H')) {
            options.hour = timeFormat.includes('h') ? 'numeric' : '2-digit';
            options.hour12 = timeFormat.includes('h');
        }
        if (timeFormat.includes('m')) {
            options.minute = '2-digit';
        }

        return date.toLocaleTimeString(undefined, options);
    };

    const formatDateTime = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const dateFormat = getDateFormat();
        const timeFormat = getTimeFormat();

        const options: Intl.DateTimeFormatOptions = {};

        // Date options
        if (dateFormat.includes('d')) {
            options.day = 'numeric';
        }
        if (dateFormat.includes('M') || dateFormat.includes('MMM') || dateFormat.includes('MMMM')) {
            options.month = dateFormat.includes('MMMM') ? 'long' : dateFormat.includes('MMM') ? 'short' : 'numeric';
        }
        if (dateFormat.includes('y') || dateFormat.includes('Y')) {
            options.year = 'numeric';
        }

        // Time options
        if (timeFormat.includes('h') || timeFormat.includes('H')) {
            options.hour = timeFormat.includes('h') ? 'numeric' : '2-digit';
            options.hour12 = timeFormat.includes('h');
        }
        if (timeFormat.includes('m')) {
            options.minute = '2-digit';
        }

        return date.toLocaleString(undefined, options);
    };

    const formatNumber = (val: number | string | null | undefined, decimals: number = 2): string => {
        const n = Number(val || 0);
        const numberFormat = getNumberFormat();

        // Parse the number format to determine separators
        const usesCommaForThousands = numberFormat.includes(',');
        const usesPeriodForDecimal = numberFormat.includes('.');

        return new Intl.NumberFormat(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(n);
    };

    const formatRelativeTime = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const now = new Date();
        const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);

        if (diffInSeconds < 60) {
            return 'just now';
        }

        const diffInMinutes = Math.floor(diffInSeconds / 60);

        if (diffInMinutes < 60) {
            return `${diffInMinutes}m ago`;
        }

        const diffInHours = Math.floor(diffInMinutes / 60);

        if (diffInHours < 24) {
            return `${diffInHours}h ago`;
        }

        const diffInDays = Math.floor(diffInHours / 24);

        if (diffInDays < 7) {
            return `${diffInDays}d ago`;
        }

        return formatDate(val);
    };

    const convertTimezone = (val: string | null | undefined, targetTimezone?: string): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const timezone = targetTimezone || getTimezone();

        return date.toLocaleString(undefined, {
            timeZone: timezone,
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return {
        formatCurrency,
        formatDate,
        formatTime,
        formatDateTime,
        formatNumber,
        formatRelativeTime,
        convertTimezone,
    };
}
