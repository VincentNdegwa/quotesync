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
        const numberFormat = getNumberFormat();

        // Check if number is a whole number
        const isWholeNumber = n === Math.floor(n);
        const decimals = isWholeNumber ? 0 : 2;

        // Parse number format to determine separators
        const usesCommaForThousands = numberFormat.includes(',');
        const usesPeriodForDecimal = numberFormat.includes('.');

        // Format the number with custom separators
        const parts = n.toFixed(decimals).split('.');
        const integerPart = parts[0];
        const decimalPart = decimals > 0 ? (parts[1] || '0'.repeat(decimals)) : '';

        // Add thousand separators
        const thousandsSeparator = usesCommaForThousands ? ',' : '.';
        const decimalSeparator = usesPeriodForDecimal ? '.' : ',';

        const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
        const formattedNumber = decimals > 0 ? `${formattedInteger}${decimalSeparator}${decimalPart}` : formattedInteger;

        // Get currency symbol
        const formatter = new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: currencyCode,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });
        const formattedWithSymbol = formatter.format(0);
        const currencySymbol = formattedWithSymbol.replace(/[0-9]/g, '').trim() || currencyCode;

        // Position the currency symbol
        if (position === 'after') {
            return `${formattedNumber} ${currencySymbol}`;
        }

        return `${currencySymbol} ${formattedNumber}`;
    };

    const formatDate = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const dateFormat = getDateFormat();

        // Handle custom date formats
        const day = date.getDate().toString().padStart(2, '0');
        const month = (date.getMonth() + 1).toString().padStart(2, '0');
        const year = date.getFullYear();
        const monthName = date.toLocaleDateString(undefined, { month: 'short' });
        const monthNameLong = date.toLocaleDateString(undefined, { month: 'long' });

        if (dateFormat === 'DD/MM/YYYY') {
            return `${day}/${month}/${year}`;
        }
        if (dateFormat === 'MM/DD/YYYY') {
            return `${month}/${day}/${year}`;
        }
        if (dateFormat === 'YYYY-MM-DD') {
            return `${year}-${month}-${day}`;
        }
        if (dateFormat === 'MMM d, yyyy') {
            return `${monthName} ${day}, ${year}`;
        }
        if (dateFormat === 'MMMM d, yyyy') {
            return `${monthNameLong} ${day}, ${year}`;
        }
        if (dateFormat === 'd MMM yyyy') {
            return `${day} ${monthName} ${year}`;
        }
        if (dateFormat === 'd MMMM yyyy') {
            return `${day} ${monthNameLong} ${year}`;
        }

        // Fallback to default
        return `${monthName} ${day}, ${year}`;
    };

    const formatTime = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        const date = new Date(val);
        const timeFormat = getTimeFormat();

        const hours = date.getHours();
        const minutes = date.getMinutes().toString().padStart(2, '0');

        if (timeFormat === '24h') {
            return `${hours.toString().padStart(2, '0')}:${minutes}`;
        }

        // 12h format
        const displayHours = hours % 12 || 12;
        const ampm = hours >= 12 ? 'PM' : 'AM';
        return `${displayHours}:${minutes} ${ampm}`;
    };

    const formatDateTime = (val: string | null | undefined): string => {
        if (!val) {
            return '—';
        }

        return `${formatDate(val)} ${formatTime(val)}`;
    };

    const formatNumber = (val: number | string | null | undefined, decimals: number = 2): string => {
        const n = Number(val || 0);
        const numberFormat = getNumberFormat();

        // Check if number is a whole number
        const isWholeNumber = n === Math.floor(n);

        // For whole numbers, don't add decimal part
        if (isWholeNumber) {
            const usesCommaForThousands = numberFormat.includes(',');
            const thousandsSeparator = usesCommaForThousands ? ',' : '.';
            const formattedInteger = Math.floor(n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
            return formattedInteger;
        }

        // Parse the number format to determine separators
        const usesCommaForThousands = numberFormat.includes(',');
        const usesPeriodForDecimal = numberFormat.includes('.');

        // Format the number with custom separators
        const parts = n.toFixed(decimals).split('.');
        const integerPart = parts[0];
        const decimalPart = parts[1] || '0'.repeat(decimals);

        // Add thousand separators
        const thousandsSeparator = usesCommaForThousands ? ',' : '.';
        const decimalSeparator = usesPeriodForDecimal ? '.' : ',';

        const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
        return `${formattedInteger}${decimalSeparator}${decimalPart}`;
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

        // Convert to target timezone
        const options: Intl.DateTimeFormatOptions = {
            timeZone: timezone,
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: getTimeFormat() !== '24h',
        };

        const formatted = date.toLocaleString(undefined, options);
        
        // Parse and reformat according to custom date format
        const parts = formatted.split(/[,\s]+/);
        const timePart = parts[parts.length - 1];
        const datePart = parts.slice(0, -1).join(' ');
        
        // Reformat date part according to custom format
        const dateFormat = getDateFormat();
        const dateObj = new Date(formatted);
        const day = dateObj.getDate().toString().padStart(2, '0');
        const month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
        const year = dateObj.getFullYear();
        
        let formattedDate;
        if (dateFormat === 'DD/MM/YYYY') {
            formattedDate = `${day}/${month}/${year}`;
        } else if (dateFormat === 'MM/DD/YYYY') {
            formattedDate = `${month}/${day}/${year}`;
        } else if (dateFormat === 'YYYY-MM-DD') {
            formattedDate = `${year}-${month}-${day}`;
        } else {
            formattedDate = datePart;
        }
        
        return `${formattedDate} ${timePart}`;
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
