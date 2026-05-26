<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';
import { X, Zap } from 'lucide-vue-next';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

const page = usePage();
const workspace = computed(() => page.props.auth.currentWorkspace);

const planSlug = computed(() => workspace.value?.plan?.slug || 'free');
const isActive = computed(() => workspace.value?.subscription?.is_active ?? true);

const showBanner = computed(() => {
    return planSlug.value === 'free' && !isActive.value;
});

const emit = defineEmits<{
    dismiss: [];
}>();
</script>

<template>
    <div
        v-if="showBanner"
        class="mb-4 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950"
    >
        <div class="flex items-center gap-3">
            <div class="rounded-full bg-amber-200 p-2 dark:bg-amber-900">
                <Zap class="h-4 w-4 text-amber-700 dark:text-amber-300" />
            </div>
            <div>
                <p class="font-medium text-amber-900 dark:text-amber-100">
                    Upgrade to unlock more features
                </p>
                <p class="text-sm text-amber-700 dark:text-amber-300">
                    Get unlimited quotes, invoices, templates, and more with our Growth plan.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <Link href="/billing">
                <Button size="sm" variant="default">
                    View Plans
                </Button>
            </Link>
            <Button
                size="icon"
                variant="ghost"
                @click="emit('dismiss')"
                class="h-8 w-8"
            >
                <X class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
