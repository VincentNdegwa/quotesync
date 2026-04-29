export function useFormat(defaultCurrency?: string) {
    const formatCurrency = (val: number | string | null | undefined, currency?: string): string => {
        const n = Number(val || 0);

        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency: currency || defaultCurrency || 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(n);
    };

    const formatDate = (val: string | null | undefined): string => {
        if (!val) {
return '—';
}

        return new Date(val).toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    };

    const formatDateTime = (val: string | null | undefined): string => {
        if (!val) {
return '—';
}

        return new Date(val).toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
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

    return {
        formatCurrency,
        formatDate,
        formatDateTime,
        formatRelativeTime,
    };
}
