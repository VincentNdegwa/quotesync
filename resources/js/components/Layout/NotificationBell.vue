<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Bell,
    CircleCheckBig,
    CircleX,
    Clock3,
    Eye,
    Send,
} from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { read, readAll } from '@/routes/notifications';
import type { NotificationSharedData, NotificationSummary } from '@/types';

const page = usePage();

const sharedNotifications = computed<NotificationSharedData>(() => {
    return (
        (page.props.notifications as NotificationSharedData | undefined) ?? {
            unread_count: 0,
            items: [],
        }
    );
});

const unreadCount = computed<number>(
    () => sharedNotifications.value.unread_count ?? 0,
);
const items = computed<NotificationSummary[]>(
    () => sharedNotifications.value.items ?? [],
);

const iconMap = {
    bell: Bell,
    eye: Eye,
    send: Send,
    'circle-check-big': CircleCheckBig,
    'circle-x': CircleX,
    'clock-3': Clock3,
    warning: AlertTriangle,
} as const;

const iconFor = (item: NotificationSummary) => {
    return iconMap[item.icon as keyof typeof iconMap] ?? Bell;
};

const markAllRead = (): void => {
    if (unreadCount.value === 0) {
        return;
    }

    router.post(readAll().url, {}, { preserveScroll: true });
};

const openNotification = (item: NotificationSummary): void => {
    router.post(
        read(item.id).url,
        { redirect_to: item.url || '/dashboard' },
        { preserveScroll: true },
    );
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                size="icon"
                class="relative h-9 w-9"
                aria-label="Notifications"
            >
                <Bell class="h-4 w-4" />
                <Badge
                    v-if="unreadCount > 0"
                    class="absolute -top-1 -right-1 h-5 min-w-5 justify-center px-1 text-[10px]"
                >
                    {{ unreadCount > 99 ? '99+' : unreadCount }}
                </Badge>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-[22rem] p-0">
            <div class="flex items-center justify-between gap-3 px-3 py-2">
                <DropdownMenuLabel class="p-0 text-sm font-semibold">
                    Notifications
                </DropdownMenuLabel>

                <button
                    type="button"
                    class="text-xs font-medium text-muted-foreground transition hover:text-foreground disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="unreadCount === 0"
                    @click="markAllRead"
                >
                    Mark all read
                </button>
            </div>

            <DropdownMenuSeparator />

            <div
                v-if="items.length === 0"
                class="px-3 py-8 text-center text-sm text-muted-foreground"
            >
                You're all caught up.
            </div>

            <div v-else class="max-h-96 overflow-y-auto">
                <button
                    v-for="item in items"
                    :key="item.id"
                    type="button"
                    class="flex w-full items-start gap-3 border-b px-3 py-3 text-left transition last:border-b-0 hover:bg-muted/40"
                    :class="item.is_read ? 'bg-background' : 'bg-primary/5'"
                    @click="openNotification(item)"
                >
                    <span
                        class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border"
                        :class="
                            item.is_read
                                ? 'border-border bg-background text-muted-foreground'
                                : 'border-primary/20 bg-primary/10 text-primary'
                        "
                    >
                        <component :is="iconFor(item)" class="h-4 w-4" />
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <p
                                class="text-sm leading-5 font-medium text-foreground"
                            >
                                {{ item.title }}
                            </p>
                            <span
                                v-if="!item.is_read"
                                class="mt-1 inline-block h-2 w-2 rounded-full bg-primary"
                            />
                        </div>

                        <p
                            v-if="item.message"
                            class="mt-1 text-xs leading-5 text-muted-foreground"
                        >
                            {{ item.message }}
                        </p>

                        <p class="mt-2 text-[11px] text-muted-foreground">
                            {{ item.time_ago || 'Just now' }}
                        </p>
                    </div>
                </button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
