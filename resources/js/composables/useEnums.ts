import { usePage } from '@inertiajs/vue3';
import type { FollowUpChannelEnum, GlobalEnums, InvoiceStatusEnum, QuoteActivityTypeEnum, QuoteFollowUpStatusEnum, QuoteStatusEnum, TrackingEventTypeEnum } from '@/types/quotes';

export function useEnums() {
    const page = usePage();
    const enums = page.props.enums as GlobalEnums;

    const getQuoteStatus = (value: string): QuoteStatusEnum | undefined => {
        return enums.quoteStatus.find((status) => status.value === value);
    };

    const getQuoteActivityType = (value: string): QuoteActivityTypeEnum | undefined => {
        return enums.quoteActivityType.find((type) => type.value === value);
    };

    const getFollowUpChannel = (value: string): FollowUpChannelEnum | undefined => {
        return enums.followUpChannel.find((channel) => channel.value === value);
    };

    const getQuoteFollowUpStatus = (value: string): QuoteFollowUpStatusEnum | undefined => {
        return enums.quoteFollowUpStatus.find((status) => status.value === value);
    };

    const getTrackingEventType = (value: string): TrackingEventTypeEnum | undefined => {
        return enums.trackingEventType.find((type) => type.value === value);
    };

    const getInvoiceStatus = (value: string): InvoiceStatusEnum | undefined => {
        return enums.invoiceStatus.find((status) => status.value === value);
    };

    return {
        enums,
        getQuoteStatus,
        getQuoteActivityType,
        getFollowUpChannel,
        getQuoteFollowUpStatus,
        getTrackingEventType,
        getInvoiceStatus,
    };
}
