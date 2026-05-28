<script setup lang="ts">
import { computed } from 'vue';
import InlineEditableText from '@/components/builder/blocks/InlineEditableText.vue';
import {
    blockContentStyle,
    blockFontSizeClass,
} from '@/composables/useBlockStyles';
import { useBuilderStore } from '@/stores/builder';
import type { CoverMessageBlockConfig, WorkspaceSettings } from '@/types';

const props = defineProps<{
    config: CoverMessageBlockConfig;
    settings: WorkspaceSettings;
    previewMode: boolean;
    editMode?: boolean;
}>();

const builderStore = useBuilderStore();

const effectiveContextText = computed(() => {
    return props.config.contextText ?? '';
});

const fontSizeClass = computed(() => {
    const size = props.config.fontSize ?? 'md';
    const sizeMap: Record<string, string> = {
        sm: 'text-sm leading-6',
        md: 'text-base leading-7',
        lg: 'text-lg leading-8',
    };

    return sizeMap[size];
});

const textColorClass = computed(() => {
    return props.config.textColor || 'text-gray-700';
});

const showBlock = computed(
    () =>
        !!effectiveContextText.value.trim() ||
        props.previewMode ||
        props.editMode,
);

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

    const allLineItems = builderStore.sections.flatMap((s) => s.line_items);

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
        v-if="showBlock"
        :style="blockContentStyle(config)"
        :class="blockFontSizeClass(config.fontSize)"
    >
        <InlineEditableText
            v-if="config.showLabel"
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="A note from us"
            empty-text="A note from us"
            display-class="mb-2 font-semibold text-base"
            @update:model-value="
                (value) => {
                    const block = builderStore.layout?.blocks.find(
                        (b) => b.type === 'cover_message',
                    );
                    if (block) {
                        (block.config as any).labelText = value ?? '';
                    }
                }
            "
        />

        <div v-if="editMode" class="mb-2">
            <InlineEditableText
                :model-value="effectiveContextText"
                :edit-mode="editMode"
                :rows="4"
                placeholder="Write a personal intro message for your client..."
                empty-text="Write a personal intro message for your client..."
                :display-class="`w-full whitespace-pre-wrap ${textColorClass} ${fontSizeClass}`"
                enable-ai-write
                block-type="cover_message"
                :quote-context="quoteContext"
                @update:model-value="
                    (value) => {
                        const block = builderStore.layout?.blocks.find(
                            (b) => b.type === 'cover_message',
                        );
                        if (block) {
                            builderStore.$patch({
                                layout: {
                                    ...builderStore.layout,
                                    blocks: builderStore.layout!.blocks.map(
                                        (b) =>
                                            b.type === 'cover_message'
                                                ? {
                                                      ...b,
                                                      config: {
                                                          ...b.config,
                                                          contextText:
                                                              value ?? '',
                                                      },
                                                  }
                                                : b,
                                    ),
                                },
                            });
                        }
                    }
                "
            />
        </div>

        <InlineEditableText
            v-else
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="4"
            placeholder="Write a personal intro message for your client..."
            empty-text="Write a personal intro message for your client..."
            :display-class="`whitespace-pre-wrap ${textColorClass} ${fontSizeClass}`"
            @update:model-value="
                (value) => {
                    const block = builderStore.layout?.blocks.find(
                        (b) => b.type === 'cover_message',
                    );
                    if (block) {
                        builderStore.$patch({
                            layout: {
                                ...builderStore.layout,
                                blocks: builderStore.layout!.blocks.map((b) =>
                                    b.type === 'cover_message'
                                        ? {
                                              ...b,
                                              config: {
                                                  ...b.config,
                                                  contextText: value ?? '',
                                              },
                                          }
                                        : b,
                                ),
                            },
                        });
                    }
                }
            "
        />
    </div>
</template>
