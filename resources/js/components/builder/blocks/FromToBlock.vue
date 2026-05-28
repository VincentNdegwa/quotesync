<script setup lang="ts">
import { computed } from 'vue';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useBuilderStore } from '@/stores/builder';
import type { FromToBlockConfig, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: FromToBlockConfig;
    settings: WorkspaceSettings;
    previewMode: boolean;
}>();

const builderStore = useBuilderStore();
const effectiveBranding = computed(() => props.settings.workspace);
const client = computed(() => builderStore.client);
</script>

<template>
    <div
        :class="[
            config.layout === 'split' ? 'grid grid-cols-2 gap-6' : 'space-y-4',
            blockFontSizeClass(config.fontSize),
        ]"
        :style="blockBaseStyle(config)"
    >
        <div>
            <p
                v-if="config.showLabels"
                class="text-xs tracking-wide text-muted-foreground uppercase"
            >
                From
            </p>
            <p class="font-semibold">
                {{ effectiveBranding.company_name || 'Your company' }}
            </p>
            <p
                v-if="
                    config.showCompanyAddress &&
                    effectiveBranding.company_address
                "
                class="text-muted-foreground"
            >
                {{ effectiveBranding.company_address }}
            </p>
            <p
                v-if="
                    config.showCompanyEmail && effectiveBranding.company_email
                "
                class="text-muted-foreground"
            >
                {{ effectiveBranding.company_email }}
            </p>
            <p
                v-if="
                    config.showCompanyPhone && effectiveBranding.company_phone
                "
                class="text-muted-foreground"
            >
                {{ effectiveBranding.company_phone }}
            </p>
        </div>
        <div :class="config.layout === 'split' ? 'text-right' : ''">
            <p
                v-if="config.showLabels"
                class="text-xs tracking-wide text-muted-foreground uppercase"
            >
                To
            </p>
            <p class="font-semibold">
                {{ client?.company_name || 'Client' }}
            </p>
            <p
                v-if="config.showClientAddress && client?.address"
                class="text-muted-foreground"
            >
                {{ client.address }}
            </p>
            <p
                v-if="config.showClientEmail && client?.email"
                class="text-muted-foreground"
            >
                {{ client.email }}
            </p>
            <p
                v-if="config.showClientPhone && client?.phone"
                class="text-muted-foreground"
            >
                {{ client.phone }}
            </p>
        </div>
    </div>
</template>
