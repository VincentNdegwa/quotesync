<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

type Props = {
    id: number;
    clientName: string;
    number: string | null;
    badge?: {
        label: string;
        variant?: 'default' | 'secondary' | 'destructive' | 'outline';
    };
    description: string;
    buttonText: string;
    buttonLink: string;
};

const props = withDefaults(defineProps<Props>(), {
    badge: undefined,
});
</script>

<template>
    <div class="group flex items-center justify-between gap-3 rounded-sm border p-2.5 hover:bg-muted/50 transition-colors">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <p class="text-sm font-medium truncate">{{ clientName }}</p>
                <Badge
                    v-if="badge"
                    :variant="badge.variant || 'secondary'"
                    class="text-xs shrink-0 h-5 px-1.5"
                >
                    {{ badge.label }}
                </Badge>
            </div>
            <p class="text-xs text-muted-foreground mt-0.5 truncate">
                {{ number || `#${id}` }} • {{ description }}
            </p>
        </div>
        <Link :href="buttonLink.replace(':id', id.toString())">
            <Button
                variant="ghost"
                size="sm"
                class="h-7 text-xs opacity-0 group-hover:opacity-100 transition-opacity shrink-0"
            >
                {{ buttonText }}
            </Button>
        </Link>
    </div>
</template>
