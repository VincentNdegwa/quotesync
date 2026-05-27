<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/builder/blocks/InlineEditableText.vue';
import {
    blockContentStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useThemeStyles } from '@/composables/useThemeStyles';
import { useBuilderStore } from '@/stores/builder';
import type {
    TermsBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: TermsBlockConfig;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const builderStore = useBuilderStore();
const { theme } = useThemeStyles(props.settings);

const effectiveContextText = computed(() => {
    return props.config.contextText ?? '';
});

const textColorClass = computed(() => {
    return props.config.textColor || 'text-gray-700';
});

const quoteContext = computed(() => {
    const context: any = {};

    if (builderStore.client) {
        if (builderStore.client.company_name) {
            context.client = {
                company_name: builderStore.client.company_name,
            };

            if (builderStore.client.email) {
                context.client.email = builderStore.client.email;
            }
        }
    }

    const allLineItems = builderStore.sections.flatMap(s => s.line_items);

    if (allLineItems.length > 0) {
        context.line_items = allLineItems
            .filter((item: any) => item.name)
            .map((item: any) => ({
                name: item.name,
                quantity: item.quantity,
                unit_price: item.unit_price,
            }));
    }

    context.total =
        typeof builderStore.total === 'string'
            ? parseFloat(builderStore.total)
            : builderStore.total;

    if (builderStore.currency) {
        context.currency = builderStore.currency;
    }

    return context;
});
</script>

<template>
    <div
        :style="blockContentStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
    >
        <InlineEditableText
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Terms"
            empty-text="Terms"
            display-class="mb-2 font-semibold text-base"
            :style="{ color: theme.primaryColor }"
            @update:model-value="(value) => {
                const block = builderStore.layout?.blocks.find(b => b.type === 'terms');
                if (block) {
                    (block.config as any).labelText = value ?? '';
                }
            }"
        />

        <div v-if="editMode" class="mb-2">
            <InlineEditableText
                :model-value="effectiveContextText"
                :edit-mode="editMode"
                :rows="8"
                placeholder="Enter terms and conditions"
                :empty-text="
                    previewMode
                        ? 'Add terms and conditions in block settings.'
                        : 'No terms provided.'
                "
                :display-class="`w-full whitespace-pre-wrap ${textColorClass}`"
                enable-ai-write
                block-type="terms"
                :quote-context="quoteContext"
                @update:model-value="(value) => {
                    const block = builderStore.layout?.blocks.find(b => b.type === 'terms');
                    if (block) {
                        builderStore.$patch({
                            layout: {
                                ...builderStore.layout,
                                blocks: builderStore.layout!.blocks.map(b => 
                                    b.type === 'terms' 
                                        ? { ...b, config: { ...b.config, contextText: value ?? '' } }
                                        : b
                                )
                            }
                        });
                    }
                }"
            />
        </div>

        <InlineEditableText
            v-else
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="8"
            placeholder="Enter terms and conditions"
            :empty-text="
                previewMode
                    ? 'Add terms and conditions in block settings.'
                    : 'No terms provided.'
            "
            :display-class="`whitespace-pre-wrap ${textColorClass}`"
            @update:model-value="(value) => {
                const block = builderStore.layout?.blocks.find(b => b.type === 'terms');
                if (block) {
                    builderStore.$patch({
                        layout: {
                            ...builderStore.layout,
                            blocks: builderStore.layout!.blocks.map(b => 
                                b.type === 'terms' 
                                    ? { ...b, config: { ...b.config, contextText: value ?? '' } }
                                    : b
                            )
                        }
                    });
                }
            }"
        />
    </div>
</template>
