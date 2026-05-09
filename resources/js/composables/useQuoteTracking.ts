type TrackingEvent = {
    event_type: string;
    duration_seconds?: number;
    section_name?: string | null;
    scroll_depth_percent?: number;
    metadata?: Record<string, unknown>;
    occurred_at?: string;
};

type TrackingOptions = {
    quoteUuid: string;
    endpoint: string;
    flushInterval?: number;
};

const BATCH_MAX = 20;

export function useQuoteTracking(
    options: TrackingOptions,
): Record<string, unknown> {
    const queue: TrackingEvent[] = [];
    const flushInterval = options.flushInterval ?? 5000;
    let timer: ReturnType<typeof setInterval> | null = null;
    let startTime = Date.now();

    const enqueue = (event: TrackingEvent): void => {
        queue.push(event);

        if (queue.length >= BATCH_MAX) {
            flush();
        }
    };

    const flush = (): void => {
        if (queue.length === 0) {
            return;
        }

        const batch = queue.splice(0, queue.length);

        const payload = JSON.stringify({ events: batch });

        if (typeof navigator.sendBeacon === 'function') {
            const formData = new FormData();
            formData.append('events', JSON.stringify(batch));
            navigator.sendBeacon(options.endpoint, formData);
        } else {
            fetch(options.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true,
            }).catch(() => {
                // silently ignore tracking failures
            });
        }
    };

    const trackView = (): void => {
        startTime = Date.now();
        enqueue({ event_type: 'view' });
    };

    const trackSectionVisible = (sectionName: string): void => {
        enqueue({ event_type: 'section_visible', section_name: sectionName });
    };

    const trackScrollDepth = (percent: number): void => {
        enqueue({ event_type: 'scroll_depth', scroll_depth_percent: percent });
    };

    const trackTimeSpent = (): void => {
        const seconds = Math.round((Date.now() - startTime) / 1000);
        enqueue({ event_type: 'time_spent', duration_seconds: seconds });
    };

    const trackLinkClick = (label: string): void => {
        enqueue({ event_type: 'link_click', metadata: { label } });
    };

    const start = (): void => {
        trackView();
        timer = setInterval(() => {
            trackTimeSpent();
        }, flushInterval);

        document.addEventListener('visibilitychange', handleVisibilityChange);
    };

    const stop = (): void => {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }

        trackTimeSpent();
        flush();
        document.removeEventListener(
            'visibilitychange',
            handleVisibilityChange,
        );
    };

    const handleVisibilityChange = (): void => {
        if (document.visibilityState === 'hidden') {
            trackTimeSpent();
            flush();
        }
    };

    return {
        start,
        stop,
        flush,
        trackView,
        trackSectionVisible,
        trackScrollDepth,
        trackTimeSpent,
        trackLinkClick,
    };
}
