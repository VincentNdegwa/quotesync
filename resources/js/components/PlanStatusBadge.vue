<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { Crown, Sparkles } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const page = usePage();
const workspace = computed(() => page.props.auth.currentWorkspace);

const planName = computed(() => workspace.value?.plan?.name || 'Free');
const planSlug = computed(() => workspace.value?.plan?.slug || 'free');
const isActive = computed(
    () => workspace.value?.subscription?.is_active ?? true,
);

const badgeVariant = computed(() => {
    if (planSlug.value === 'free') {
        return 'secondary';
    }

    if (planSlug.value === 'growth') {
        return 'default';
    }

    return 'outline';
});

const showUpgradePrompt = computed(
    () => planSlug.value === 'free' && !isActive.value,
);
</script>

<template>
    <div class="flex items-center gap-2">
        <Badge :variant="badgeVariant" class="gap-1">
            <Crown v-if="planSlug !== 'free'" class="h-3 w-3" />
            <Sparkles v-else class="h-3 w-3" />
            {{ planName }}
        </Badge>

        <Link
            v-if="showUpgradePrompt"
            href="/billing"
            class="text-xs text-primary hover:underline"
        >
            Upgrade
        </Link>
    </div>
</template>
