import { usePage } from '@inertiajs/vue3';
import type { GlobalEnums, QuoteActivityTypeEnum, QuoteStatusEnum } from '@/types/quotes';

export function useEnums() {
    const page = usePage();
    const enums = page.props.enums as GlobalEnums;

    const getQuoteStatus = (value: string): QuoteStatusEnum | undefined => {
        return enums.quoteStatus.find((status) => status.value === value);
    };

    const getQuoteActivityType = (value: string): QuoteActivityTypeEnum | undefined => {
        return enums.quoteActivityType.find((type) => type.value === value);
    };

    return {
        enums,
        getQuoteStatus,
        getQuoteActivityType,
    };
}
