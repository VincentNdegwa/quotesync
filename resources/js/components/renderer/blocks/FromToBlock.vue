<script setup lang="ts">
import { computed } from 'vue';
import {
    blockBaseStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import type {
    DocumentData,
    FromToBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: FromToBlockConfig;
    data: DocumentData;
    settings: WorkspaceSettings;
    previewMode: boolean;
}>();

const effectiveBranding = computed(() => props.settings.workspace);
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
                v-if="effectiveBranding.company_email"
                class="text-muted-foreground"
            >
                {{ effectiveBranding.company_email }}
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
                {{ data.client?.company_name || 'Client' }}
            </p>
            <p v-if="config.showClientAddress" class="text-muted-foreground">
                {{ data.client?.address || 'No client address available' }}
            </p>
        </div>
    </div>
</template>
