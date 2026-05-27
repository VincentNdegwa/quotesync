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
    PaymentTermsBlockConfig,
    WorkspaceSettings,
} from '@/types';

const props = defineProps<{
    config: PaymentTermsBlockConfig;
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

const methodLabelMap: Record<
    PaymentTermsBlockConfig['paymentMethods'][number],
    string
> = {
    bank_transfer: 'Bank transfer',
    card: 'Card',
    mobile_money: 'Mobile money',
    cash: 'Cash',
    cheque: 'Cheque',
};

const hasEditableContent = computed(
    () =>
        !!effectiveContextText.value || !!props.editMode || !!props.previewMode,
);

const emitUpdate = (
    labelText: string | null,
    contextText: string | null,
): void => {
    const block = builderStore.layout?.blocks.find(b => b.type === 'payment_terms');

    if (block) {
        builderStore.$patch({
            layout: {
                ...builderStore.layout,
                blocks: builderStore.layout!.blocks.map(b => 
                    b.type === 'payment_terms' 
                        ? { ...b, config: { ...b.config, labelText: (labelText ?? '').trim() || 'Payment Terms', contextText: contextText ?? '' } }
                        : b
                )
            }
        });
    }
};

const updateLabel = (value: string | null): void => {
    emitUpdate(value, effectiveContextText.value);
};

const updateContextText = (value: string | null): void => {
    emitUpdate(props.config.labelText, value);
};
</script>

<template>
    <div
        v-if="hasEditableContent"
        :class="[
            config.style === 'card'
                ? 'rounded-md border'
                : config.style === 'highlighted'
                  ? 'rounded-md bg-muted/40'
                  : '',
            blockFontSizeClass(config.fontSize),
        ]"
        :style="blockContentStyle(config)"
    >
        <InlineEditableText
            :model-value="config.labelText"
            :edit-mode="editMode"
            :multiline="false"
            placeholder="Payment terms"
            empty-text="Payment terms"
            display-class="mb-2 font-semibold text-base"
            :style="{ color: theme.primaryColor }"
            @update:model-value="updateLabel"
        />
        <div
            v-if="config.showPaymentMethods && config.paymentMethods.length > 0"
            class="mt-2 flex flex-wrap gap-1.5"
        >
            <span
                v-for="method in config.paymentMethods"
                :key="method"
                class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
            >
                {{ methodLabelMap[method] }}
            </span>
        </div>

        <div v-if="editMode" class="relative">
            <InlineEditableText
                :model-value="effectiveContextText"
                :edit-mode="editMode"
                :rows="6"
                placeholder="Add payment instructions"
                :empty-text="
                    previewMode
                        ? 'Add payment instructions in block settings.'
                        : 'Click to add payment instructions.'
                "
                :display-class="`w-full whitespace-pre-wrap text-sm ${textColorClass}`"
                enable-ai-write
                block-type="payment_terms"
                :quote-context="quoteContext"
                @update:model-value="updateContextText"
            />
        </div>

        <InlineEditableText
            v-else
            :model-value="effectiveContextText"
            :edit-mode="editMode"
            :rows="6"
            placeholder="Add payment instructions"
            :empty-text="
                previewMode
                    ? 'Add payment instructions in block settings.'
                    : 'Click to add payment instructions.'
            "
            :display-class="`whitespace-pre-wrap text-sm ${textColorClass}`"
            @update:model-value="updateContextText"
        />
    </div>
</template>
