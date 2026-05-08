<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, RotateCcw, CheckCircle2, MoreHorizontal } from 'lucide-vue-next';
import QuoteController from '@/actions/App/Http/Controllers/QuoteController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import type { QuoteData } from '@/types';

const _props = defineProps<{
    quote: QuoteData;
    versions: QuoteData[];
}>();

const emit = defineEmits<{
    restore: [versionId: number];
}>();

const restoreVersion = (versionId: number): void => {
    if (
        confirm(
            'Are you sure you want to restore this version? This will set it as the active version.',
        )
    ) {
        emit('restore', versionId);
    }
};

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold">Revision History</h3>
                <p class="text-sm text-muted-foreground">
                    Track changes and restore previous revisions
                </p>
            </div>
        </div>

        <ScrollArea class="h-[400px] pr-4">
            <div class="space-y-3">
                <!-- Parent quote (original) -->
                <div
                    v-if="quote.parent_quote_id === null"
                    class="group relative rounded-md border-2 border-primary/20 bg-linear-to-br from-primary/5 to-transparent p-4 transition-all hover:border-primary/40"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="mb-0 flex items-center gap-2">
                                <Badge variant="default" class="mb-2 gap-1">
                                    <CheckCircle2 class="h-3 w-3" />
                                    Current
                                </Badge>
                                <Badge class="mb-2" variant="secondary"
                                    >Original</Badge
                                >
                            </div>
                            <div class="mb-1 text-lg font-semibold">
                                Revision {{ quote.version || 1 }}
                            </div>
                            <div
                                class="space-y-1 text-sm text-muted-foreground"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{
                                        quote.number
                                    }}</span>
                                    <span>•</span>
                                    <span>{{
                                        formatDate(quote.created_at)
                                    }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2"></div>
                    </div>
                </div>

                <Separator
                    v-if="quote.parent_quote_id === null && versions.length > 0"
                    class="my-4"
                />

                <!-- Child versions -->
                <div
                    v-for="version in versions"
                    :key="version.id"
                    class="group relative rounded-md border p-4 transition-all hover:border-primary/30 hover:shadow-md"
                    :class="{
                        'border-primary/50 bg-primary/5':
                            version.id === quote.active_version_id,
                        'border-border bg-card':
                            version.id !== quote.active_version_id,
                    }"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex-1">
                            <div class="mb-0 flex items-center gap-2">
                                <Badge
                                    v-if="
                                        version.id === quote.active_version_id
                                    "
                                    variant="default"
                                    class="mb-2 gap-1"
                                >
                                    <CheckCircle2 class="h-3 w-3" />
                                    Active
                                </Badge>
                            </div>
                            <div class="mb-1 text-lg font-semibold">
                                Revision {{ version.version }}
                            </div>
                            <div
                                class="space-y-1 text-sm text-muted-foreground"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{
                                        version.number
                                    }}</span>
                                    <span>•</span>
                                    <span>{{
                                        formatDate(version.created_at)
                                    }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-8 w-8"
                                    >
                                        <MoreHorizontal class="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end" class="w-40">
                                    <DropdownMenuItem
                                        :as-child="true"
                                        class="gap-2"
                                    >
                                        <Link
                                            :href="
                                                QuoteController.show(version.id)
                                                    .url
                                            "
                                            class="flex w-full items-center gap-2"
                                        >
                                            <Eye class="h-4 w-4" />
                                            <span>View</span>
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="
                                            version.id !==
                                            quote.active_version_id
                                        "
                                        class="gap-2"
                                        @select="restoreVersion(version.id)"
                                    >
                                        <RotateCcw class="h-4 w-4" />
                                        <span>Restore</span>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                </div>
            </div>
        </ScrollArea>
    </div>
</template>
