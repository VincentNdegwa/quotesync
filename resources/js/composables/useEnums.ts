import { usePage } from '@inertiajs/vue3';
import { Mail, MessageSquare, Phone } from 'lucide-vue-next';
import type {
    CreditNoteStatusEnum,
    FollowUpChannelEnum,
    GlobalEnums,
    InvoiceStatusEnum,
    QuoteActivityTypeEnum,
    QuoteFollowUpStatusEnum,
    QuoteStatusEnum,
    SignalDirectionEnum,
    TrackingEventTypeEnum,
    WinProbabilityConfidenceEnum,
} from '@/types/quotes';

export function useEnums(): Record<string, unknown> {
    const page = usePage();
    const enums = page.props.enums as GlobalEnums;

    const getQuoteStatus = (value: string): QuoteStatusEnum | undefined => {
        return enums.quoteStatus.find((status) => status.value === value);
    };

    const getQuoteActivityType = (
        value: string,
    ): QuoteActivityTypeEnum | undefined => {
        return enums.quoteActivityType.find((type) => type.value === value);
    };

    const getFollowUpChannel = (
        value: string,
    ): FollowUpChannelEnum | undefined => {
        return enums.followUpChannel.find((channel) => channel.value === value);
    };

    const getQuoteFollowUpStatus = (
        value: string,
    ): QuoteFollowUpStatusEnum | undefined => {
        return enums.quoteFollowUpStatus.find(
            (status) => status.value === value,
        );
    };

    const getTrackingEventType = (
        value: string,
    ): TrackingEventTypeEnum | undefined => {
        return enums.trackingEventType.find((type) => type.value === value);
    };

    const getInvoiceStatus = (value: string): InvoiceStatusEnum | undefined => {
        return enums.invoiceStatus.find((status) => status.value === value);
    };

    const getWinProbabilityConfidence = (
        value: string,
    ): WinProbabilityConfidenceEnum | undefined => {
        return enums.winProbabilityConfidence.find(
            (confidence) => confidence.value === value,
        );
    };

    const getSignalDirection = (
        value: string,
    ): SignalDirectionEnum | undefined => {
        return enums.signalDirection.find(
            (direction) => direction.value === value,
        );
    };

    const getCreditNoteStatus = (
        value: string,
    ): CreditNoteStatusEnum | undefined => {
        return enums.creditNoteStatus.find((status) => status.value === value);
    };

    const getFollowUpChannelIcon = (channel: string): unknown => {
        return (
            { email: Mail, whatsapp: MessageSquare, sms: Phone }[channel] ??
            Mail
        );
    };

    const getFollowUpChannelColor = (channel: string): string => {
        const channelEnum = getFollowUpChannel(channel);

        return channelEnum?.color ?? 'text-muted-foreground bg-muted';
    };

    return {
        enums,
        getQuoteStatus,
        getQuoteActivityType,
        getFollowUpChannel,
        getQuoteFollowUpStatus,
        getTrackingEventType,
        getInvoiceStatus,
        getWinProbabilityConfidence,
        getSignalDirection,
        getCreditNoteStatus,
        getFollowUpChannelIcon,
        getFollowUpChannelColor,
    };
}
