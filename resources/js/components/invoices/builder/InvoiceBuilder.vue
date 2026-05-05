<script setup lang="ts">
import { computed, ref, toRaw } from 'vue';
import { watch } from 'vue';
import BlockConfigPanel from '@/components/builder/BlockConfigPanel.vue';
import BlockList from '@/components/builder/BlockList.vue';
import InvoiceRenderer from '@/components/renderer/InvoiceRenderer.vue';
import {
    ADDABLE_BLOCK_TYPES,
    createBlock,
    ensureTemplateLayout,
} from '@/types';
import type {
    Block,
    BlockType,
    BuilderCatalogItem,
    BuilderConfigurationUnit,
    BuilderTaxOption,
    InvoiceBuilderLineItem,
    InvoiceBuilderState,
    WorkspaceSettings,
} from '@/types';

const model = defineModel<InvoiceBuilderState>({
    required: true,
});

const props = withDefaults(
    defineProps<{
        mode: 'invoice';
        catalogItems: BuilderCatalogItem[];
        taxes: BuilderTaxOption[];
        units: BuilderConfigurationUnit[];
        settings: WorkspaceSettings;
        processing?: boolean;
    }>(),
    {
        processing: false,
    },
);

const emit = defineEmits<{
    save: [];
}>();

const selectedBlockId = ref<string | null>(null);

const layout = computed({
    get: () => {
        return ensureTemplateLayout(model.value.layout_snapshot || model.value.layout);
    },
    set: (value) => {
        model.value.layout_snapshot = value;
    },
});

const blocks = computed(() => layout.value?.blocks || []);

const selectedBlock = computed(() => {
    return blocks.value.find((b) => b.id === selectedBlockId.value) || null;
});

const selectBlock = (id: string | null) => {
    selectedBlockId.value = id;
};

const addBlock = (type: BlockType) => {
    if (!layout.value) {
return;
}

    const newBlock = createBlock(type);
    layout.value.blocks.push(newBlock);
    model.value.layout_snapshot = layout.value;
    selectedBlockId.value = newBlock.id;
};

const updateBlock = (block: Block) => {
    if (!layout.value) {
return;
}

    const index = layout.value.blocks.findIndex((b) => b.id === block.id);

    if (index !== -1) {
        layout.value.blocks[index] = block;
        model.value.layout_snapshot = layout.value;
    }
};

const deleteBlock = (id: string) => {
    if (!layout.value) {
return;
}

    layout.value.blocks = layout.value.blocks.filter((b) => b.id !== id);
    model.value.layout_snapshot = layout.value;

    if (selectedBlockId.value === id) {
        selectedBlockId.value = null;
    }
};

const save = () => {
    emit('save');
};

// Watch for changes to line items and recalculate totals
watch(
    () => model.value.line_items,
    (lineItems) => {
        const subtotal = lineItems.reduce((sum, item) => sum + (item.subtotal || 0), 0);
        const taxAmount = lineItems.reduce((sum, item) => sum + (item.tax_amount || 0), 0);
        const discountAmount = lineItems.reduce((sum, item) => {
            const discount = (item.subtotal || 0) * ((item.discount_percent || 0) / 100);

            return sum + discount;
        }, 0);
        const total = subtotal + taxAmount - discountAmount;

        model.value.subtotal = subtotal;
        model.value.tax_amount = taxAmount;
        model.value.discount_amount = discountAmount;
        model.value.total = total;
    },
    { deep: true },
);
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-[1fr_400px]">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">Invoice Details</h2>
                <button
                    type="button"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    :disabled="processing"
                    @click="save"
                >
                    {{ processing ? 'Saving...' : 'Save' }}
                </button>
            </div>

            <div class="space-y-4 rounded-lg border bg-card p-4">
                <div class="grid gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Invoice Number</label>
                        <input
                            v-model="model.invoice_number"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Title</label>
                        <input
                            v-model="model.title"
                            type="text"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Issue Date</label>
                            <input
                                v-model="model.issue_date"
                                type="date"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </div>

                        <div>
                            <label class="mb-1 block text-sm font-medium">Due Date</label>
                            <input
                                v-model="model.due_date"
                                type="date"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Cover Message</label>
                        <textarea
                            v-model="model.cover_message"
                            rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Terms</label>
                        <textarea
                            v-model="model.terms"
                            rows="3"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">Notes</label>
                        <textarea
                            v-model="model.notes"
                            rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </div>

            <div class="space-y-4 rounded-lg border bg-card p-4">
                <h3 class="text-base font-semibold">Line Items</h3>
                
                <div class="space-y-3">
                    <div
                        v-for="(item, index) in model.line_items"
                        :key="item.id || index"
                        class="rounded-md border bg-background p-3"
                    >
                        <div class="grid gap-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium">Name</label>
                                <input
                                    v-model="item.name"
                                    type="text"
                                    class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                />
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium">Description</label>
                                <textarea
                                    v-model="item.description"
                                    rows="2"
                                    class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                />
                            </div>

                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Quantity</label>
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        step="0.01"
                                        class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium">Unit Price</label>
                                    <input
                                        v-model.number="item.unit_price"
                                        type="number"
                                        step="0.01"
                                        class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium">Discount %</label>
                                    <input
                                        v-model.number="item.discount_percent"
                                        type="number"
                                        step="0.1"
                                        class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Tax Rate %</label>
                                    <input
                                        v-model.number="item.tax_rate"
                                        type="number"
                                        step="0.1"
                                        class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                    />
                                </div>

                                <div>
                                    <label class="mb-1 block text-xs font-medium">Total</label>
                                    <input
                                        :value="item.total"
                                        type="number"
                                        step="0.01"
                                        class="w-full rounded-md border border-input bg-muted px-2 py-1 text-sm"
                                        disabled
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1 block text-xs font-medium">Notes</label>
                                <input
                                    v-model="item.notes"
                                    type="text"
                                    class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-2 rounded-lg border bg-card p-4">
                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>{{ model.subtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Tax</span>
                    <span>{{ model.tax_amount.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Discount</span>
                    <span>-{{ model.discount_amount.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-base font-semibold">
                    <span>Total</span>
                    <span>{{ model.total.toFixed(2) }}</span>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <h2 class="text-lg font-semibold">Preview</h2>

            <InvoiceRenderer
                v-if="layout_snapshot && settings"
                :data="{ ...model, documentType: 'invoice' }"
                :layout="layout_snapshot"
                :settings="settings"
                :preview-mode="true"
                :edit-mode="false"
                :is-internal-view="true"
            />
        </div>
    </div>
</template>
