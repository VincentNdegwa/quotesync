<script setup lang="ts">
import { computed } from 'vue';
import type { BrandingData, LineItemsBlockConfig, QuoteData } from '@/types';

const props = defineProps<{
    config: LineItemsBlockConfig;
    quote: QuoteData;
    branding: BrandingData;
    previewMode: boolean;
}>();

// ─── Helpers ────────────────────────────────────────────────────────────────

const fmt = (value: number): string =>
    new Intl.NumberFormat(undefined, {
        style: 'currency',
        currency: props.quote.currency || 'USD',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value || 0));

type LineItem = QuoteData['sections'][number]['lineItems'][number];
type Section = QuoteData['sections'][number];

// ─── Font / padding maps ─────────────────────────────────────────────────────

const fontClass = computed(() =>
    ({ sm: 'text-xs', md: 'text-sm', lg: 'text-base' })[props.config.fontSize],
);

const nameFontClass = computed(() =>
    ({ sm: 'text-sm', md: 'text-base', lg: 'text-lg' })[props.config.fontSize],
);

const cellPad = computed(() =>
    ({ sm: 'px-2 py-1.5', md: 'px-3 py-2.5', lg: 'px-4 py-3.5' })[props.config.fontSize],
);

// ─── Grid template columns ───────────────────────────────────────────────────
// Build a CSS grid-template-columns string from config.
// description col gets the remainder so it always fills.

const gridTemplate = computed((): string => {
    const c = props.config;
    const w = c.columnWidths;

    const cols: string[] = [];

    // Description is always first and fills remaining space
    cols.push('1fr');

    if (c.showQuantity) {
        cols.push(`${w.quantity}%`);
    }

    if (c.showUnitPrice) {
        cols.push(`${w.unitPrice}%`);
    }

    if (c.showDiscount) {
        cols.push(`${w.discount}%`);
    }

    if (c.showTax) {
        cols.push(`${w.tax}%`);
    }

    if (c.showLineTotal) {
        cols.push(`${w.total}%`);
    }

    return cols.join(' ');
});

// ─── Section subtotal ────────────────────────────────────────────────────────

const sectionSubtotal = (section: Section): number =>
    section.lineItems.reduce((sum, item) => sum + Number(item.total || 0), 0);

// ─── Inline meta line (minimal + cards) ─────────────────────────────────────

const itemMeta = (item: LineItem): string => {
    const c = props.config;
    const parts: string[] = [];

    if (c.showQuantity) {
        const unit = c.showUnit && item.unit ? ` ${item.unit}` : '';
        parts.push(`${item.quantity}${unit}`);
    }

    if (c.showUnitPrice) {
        const unit = c.showUnit && item.unit ? `/${item.unit}` : '';
        parts.push(`${fmt(item.unitPrice)}${unit}`);
    }

    if (c.showDiscount && Number(item.discountPercent || 0) > 0) {
        parts.push(`${item.discountPercent}% disc`);
    }

    if (c.showTax && Number(item.taxAmount || 0) > 0) {
        parts.push(`tax ${fmt(item.taxAmount)}`);
    }

    return parts.join(' · ');
};

// ─── Optional item helpers ────────────────────────────────────────────────────

const isGreyed = (item: LineItem): boolean =>
    item.isOptional && props.config.optionalItemStyle === 'greyed';

const showBadge = (item: LineItem): boolean =>
    item.isOptional &&
    props.config.showOptionalBadge &&
    props.config.optionalItemStyle === 'badge';

const showCheckbox = (item: LineItem): boolean =>
    item.isOptional && props.config.optionalItemStyle === 'checkbox';

// ─── Striped row color ────────────────────────────────────────────────────────

const stripeClass = (index: number): string => {
    if (props.config.tableStyle !== 'striped' || !props.config.alternateRowColor) {
        return '';
    }

    return index % 2 !== 0 ? 'bg-muted/40' : '';
};

// ─── Border / header color styles ────────────────────────────────────────────

const borderStyle = computed(() => ({
    borderColor: props.config.borderColor ?? undefined,
}));

const headerBgStyle = computed(() => ({
    backgroundColor: props.config.headerBackgroundColor ?? undefined,
    borderColor: props.config.borderColor ?? undefined,
}));

// ─── Style flags ─────────────────────────────────────────────────────────────

const isColumnLayout = computed(() =>
    ['default', 'bordered', 'striped'].includes(props.config.tableStyle),
);

const isMinimal = computed(() => props.config.tableStyle === 'minimal');
const isCards = computed(() => props.config.tableStyle === 'cards');
</script>

<template>
    <div class="px-6 py-4" :class="fontClass">

        <!-- ── Empty state (builder preview only) ─────────────────────── -->
        <div
            v-if="quote.sections.length === 0 && previewMode"
            class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            No line items yet. Add products from the catalog.
        </div>

        <!-- ── Sections loop ──────────────────────────────────────────── -->
        <div
            v-for="section in quote.sections"
            :key="`section-${section.id ?? section.title}`"
            class="mb-8 last:mb-0"
        >
            <!-- Section title -->
            <h4
                v-if="config.showSectionTitles && section.title"
                class="mb-3 font-semibold tracking-tight"
                :class="nameFontClass"
                :style="{ color: branding.primaryColor }"
            >
                {{ section.title }}
            </h4>

            <!-- ════════════════════════════════════════════════════════
                 STYLE: default | bordered | striped
                 Uses CSS grid with grid-template-columns
                 ════════════════════════════════════════════════════════ -->
            <template v-if="isColumnLayout">
                <div
                    class="overflow-hidden rounded-md border"
                    :style="borderStyle"
                >
                    <!-- Header row -->
                    <div
                        class="grid items-center border-b"
                        :class="cellPad"
                        :style="[headerBgStyle, { gridTemplateColumns: gridTemplate }]"
                    >
                        <div class="truncate text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Item
                        </div>
                        <div v-if="config.showQuantity" class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Qty
                        </div>
                        <div v-if="config.showUnitPrice" class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Unit Price
                        </div>
                        <div v-if="config.showDiscount" class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Disc
                        </div>
                        <div v-if="config.showTax" class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Tax
                        </div>
                        <div v-if="config.showLineTotal" class="text-right text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Total
                        </div>
                    </div>

                    <!-- Item rows -->
                    <div
                        v-for="(item, itemIndex) in section.lineItems"
                        :key="`col-item-${item.id ?? itemIndex}`"
                        class="border-b last:border-b-0"
                        :class="[stripeClass(itemIndex)]"
                        :style="borderStyle"
                    >
                        <!-- Main grid row -->
                        <div
                            class="grid items-start"
                            :class="[cellPad, isGreyed(item) ? 'opacity-50' : '']"
                            :style="{ gridTemplateColumns: gridTemplate }"
                        >
                            <!-- Description cell -->
                            <div class="min-w-0 pr-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium leading-snug" :class="nameFontClass">
                                        {{ item.name || 'Line item' }}
                                    </span>
                                    <!-- Optional badge inline with name -->
                                    <span
                                        v-if="showBadge(item)"
                                        class="inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
                                    >
                                        Optional
                                    </span>
                                </div>

                                <!-- Description (not in bordered — too cramped) -->
                                <p
                                    v-if="config.showItemDescription && config.tableStyle !== 'bordered' && item.description"
                                    class="mt-0.5 text-xs text-muted-foreground"
                                >
                                    {{ item.description }}
                                </p>

                                <!-- SKU -->
                                <p v-if="config.showSku && item.sku" class="mt-0.5 font-mono text-[10px] text-muted-foreground/70">
                                    {{ item.sku }}
                                </p>
                            </div>

                            <!-- Quantity -->
                            <div v-if="config.showQuantity" class="text-right tabular-nums">
                                {{ item.quantity }}
                                <span v-if="config.showUnit && item.unit" class="text-muted-foreground">
                                    {{ item.unit }}
                                </span>
                            </div>

                            <!-- Unit price -->
                            <div v-if="config.showUnitPrice" class="text-right tabular-nums">
                                {{ fmt(item.unitPrice) }}
                            </div>

                            <!-- Discount -->
                            <div v-if="config.showDiscount" class="text-right tabular-nums">
                                <span v-if="Number(item.discountPercent || 0) > 0">
                                    {{ item.discountPercent }}%
                                </span>
                                <span v-else class="text-muted-foreground/40">—</span>
                            </div>

                            <!-- Tax -->
                            <div v-if="config.showTax" class="text-right tabular-nums">
                                <span v-if="Number(item.taxAmount || 0) > 0">
                                    {{ fmt(item.taxAmount) }}
                                </span>
                                <span v-else class="text-muted-foreground/40">—</span>
                            </div>

                            <!-- Total -->
                            <div v-if="config.showLineTotal" class="text-right font-semibold tabular-nums">
                                {{ fmt(item.total) }}
                            </div>
                        </div>

                        <!-- Checkbox row for optional items (below the grid) -->
                        <div
                            v-if="showCheckbox(item)"
                            class="flex items-center gap-2 border-t border-dashed px-3 py-2 text-xs text-muted-foreground"
                            :style="borderStyle"
                        >
                            <input type="checkbox" class="h-3.5 w-3.5 cursor-pointer rounded accent-primary" :disabled="previewMode" />
                            <span>Include this item</span>
                        </div>
                    </div>

                    <!-- Section subtotal -->
                    <div
                        v-if="config.showSectionSubtotals"
                        class="flex items-center justify-end border-t px-3 py-2"
                        :style="borderStyle"
                    >
                        <span class="text-xs text-muted-foreground">Section subtotal&nbsp;</span>
                        <span class="font-semibold" :class="fontClass">
                            {{ fmt(sectionSubtotal(section)) }}
                        </span>
                    </div>
                </div>
            </template>

            <!-- ════════════════════════════════════════════════════════
                 STYLE: minimal
                 No borders, just spacing and typography
                 ════════════════════════════════════════════════════════ -->
            <template v-else-if="isMinimal">
                <div class="space-y-0 divide-y divide-border/40">
                    <div
                        v-for="(item, itemIndex) in section.lineItems"
                        :key="`min-item-${item.id ?? itemIndex}`"
                        class="py-3"
                        :class="isGreyed(item) ? 'opacity-50' : ''"
                    >
                        <!-- Name + total -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                <span class="font-medium leading-snug" :class="nameFontClass">
                                    {{ item.name || 'Line item' }}
                                </span>
                                <span
                                    v-if="showBadge(item)"
                                    class="inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
                                >
                                    Optional
                                </span>
                            </div>
                            <span v-if="config.showLineTotal" class="shrink-0 font-semibold tabular-nums">
                                {{ fmt(item.total) }}
                            </span>
                        </div>

                        <!-- Description -->
                        <p
                            v-if="config.showItemDescription && item.description"
                            class="mt-0.5 text-xs text-muted-foreground"
                        >
                            {{ item.description }}
                        </p>

                        <!-- Meta line: qty · price · discount · tax -->
                        <p v-if="itemMeta(item)" class="mt-0.5 text-xs text-muted-foreground/70">
                            {{ itemMeta(item) }}
                        </p>

                        <!-- SKU -->
                        <p v-if="config.showSku && item.sku" class="mt-0.5 font-mono text-[10px] text-muted-foreground/50">
                            SKU {{ item.sku }}
                        </p>

                        <!-- Checkbox -->
                        <div v-if="showCheckbox(item)" class="mt-1.5 flex items-center gap-2 text-xs text-muted-foreground">
                            <input type="checkbox" class="h-3.5 w-3.5 cursor-pointer rounded accent-primary" :disabled="previewMode" />
                            <span>Include this item</span>
                        </div>
                    </div>
                </div>

                <!-- Divider + section subtotal -->
                <div class="mt-1 border-t pt-2">
                    <p v-if="config.showSectionSubtotals" class="text-right text-xs">
                        <span class="text-muted-foreground">Section subtotal&nbsp;</span>
                        <span class="font-semibold">{{ fmt(sectionSubtotal(section)) }}</span>
                    </p>
                </div>
            </template>

            <!-- ════════════════════════════════════════════════════════
                 STYLE: cards
                 Each item is its own card, no table columns
                 ════════════════════════════════════════════════════════ -->
            <template v-else-if="isCards">
                <div class="space-y-3">
                    <div
                        v-for="(item, itemIndex) in section.lineItems"
                        :key="`card-item-${item.id ?? itemIndex}`"
                        class="rounded-lg border p-4 transition-colors"
                        :class="isGreyed(item) ? 'opacity-50' : ''"
                        :style="{ borderColor: config.borderColor ?? undefined }"
                    >
                        <!-- Name + total -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                                <span class="font-semibold leading-snug" :class="nameFontClass">
                                    {{ item.name || 'Line item' }}
                                </span>
                                <span
                                    v-if="showBadge(item)"
                                    class="inline-flex items-center rounded-full border px-1.5 py-px text-[10px] font-medium uppercase tracking-wide text-muted-foreground"
                                >
                                    Optional
                                </span>
                            </div>
                            <span v-if="config.showLineTotal" class="shrink-0 text-right font-semibold tabular-nums" :class="nameFontClass">
                                {{ fmt(item.total) }}
                            </span>
                        </div>

                        <!-- Description -->
                        <p
                            v-if="config.showItemDescription && item.description"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ item.description }}
                        </p>

                        <!-- Meta line -->
                        <p v-if="itemMeta(item)" class="mt-1 text-xs text-muted-foreground/70">
                            {{ itemMeta(item) }}
                        </p>

                        <!-- SKU -->
                        <p v-if="config.showSku && item.sku" class="mt-1 font-mono text-[10px] text-muted-foreground/50">
                            SKU {{ item.sku }}
                        </p>

                        <!-- Checkbox for optional -->
                        <div
                            v-if="showCheckbox(item)"
                            class="mt-3 flex items-center gap-2 rounded-md border border-dashed p-2 text-xs text-muted-foreground"
                        >
                            <input
                                type="checkbox"
                                class="h-3.5 w-3.5 cursor-pointer rounded accent-primary"
                                :disabled="previewMode"
                            />
                            <span>Include this item in my order</span>
                        </div>
                    </div>

                    <!-- Section subtotal -->
                    <div v-if="config.showSectionSubtotals" class="text-right text-xs">
                        <span class="text-muted-foreground">Section subtotal&nbsp;</span>
                        <span class="font-semibold">{{ fmt(sectionSubtotal(section)) }}</span>
                    </div>
                </div>
            </template>

        </div>
        <!-- end sections loop -->

    </div>
</template>