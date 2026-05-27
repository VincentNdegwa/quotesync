<script setup lang="ts">
import { Head, setLayoutProps, usePage } from '@inertiajs/vue3';
import { Edit, Image as ImageIcon, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useFormat } from '@/composables/useFormat';
import type { CatalogItemRecord, ConfigurationUnitRecord } from '@/types';
import CatalogActions from './components/CatalogActions.vue';

const props = defineProps<{
    item: CatalogItemRecord;
    availableTaxes: Array<{ id: number; name: string; rate: number | string }>;
    units: ConfigurationUnitRecord[];
    margin: {
        profit_per_unit: number;
        margin_percent: number;
    };
}>();

const breadcrumbs = computed(() => [
    { title: 'Catalog', href: '/catalog' },
    { title: 'Item detail', href: `/catalog/${props.item.id}` },
]);

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: breadcrumbs.value,
    });
});

const page = usePage();
const { formatCurrency, formatDate } = useFormat(
    (page.props.workspace_currency as string) || undefined,
);

const statCards = computed(() => [
    {
        label: 'Unit price',
        value: formatCurrency(props.item.unit_price),
    },
    {
        label: 'Cost price',
        value: formatCurrency(props.item.cost_price),
    },
    {
        label: 'Profit / unit',
        value: formatCurrency(props.margin.profit_per_unit),
    },
    {
        label: 'Margin',
        value: `${Number(props.margin.margin_percent).toFixed(1)}%`,
    },
    {
        label: 'Variants',
        value: (props.item.variants?.length).toString(),
    },
]);

const unitDisplay = computed(
    () => props.units.find((unit) => unit.id === props.item.unit_id) ?? null,
);

const appliedTaxes = computed(() => {
    const ids = new Set((props.item.tax_ids ?? []).map((id) => Number(id)));

    return props.availableTaxes.filter((tax) => ids.has(Number(tax.id)));
});

const appliedTaxBadges = computed(() =>
    appliedTaxes.value.map((tax) => ({
        name: tax.name,
        rate: tax.rate,
        inclusive: (tax as any).inclusive ?? false,
    })),
);

const descriptionText = computed(
    () => props.item.description?.trim() || 'No description provided yet.',
);

const quickFacts = computed(() => {
    const itemWithTimestamps = props.item as CatalogItemRecord & {
        updated_at?: string | null;
    };

    return [
        { label: 'SKU', value: props.item.sku || '—' },
        {
            label: 'Status',
            value: props.item.is_active ? 'Active' : 'Inactive',
            badge: (props.item.is_active ? 'default' : 'destructive') as
                | 'default'
                | 'destructive',
        },
        { label: 'Unit', value: unitDisplay.value?.name || '—' },
        {
            label: 'Created',
            value: props.item.created_at
                ? formatDate(props.item.created_at)
                : '—',
        },
        {
            label: 'Last updated',
            value: itemWithTimestamps.updated_at
                ? formatDate(itemWithTimestamps.updated_at)
                : props.item.created_at
                  ? formatDate(props.item.created_at)
                  : '—',
        },
    ];
});

const hasVariants = computed(() => (props.item.variants?.length ?? 0) > 0);
const hasPriceTiers = computed(() => (props.item.price_tiers?.length ?? 0) > 0);

const catalogActionsRef = ref<{
    openVariantDialog: (variant: any) => void;
    openPriceTierDialog: (priceTier: any) => void;
    deleteVariant: (variantId: number) => void;
    deletePriceTier: (priceTierId: number) => void;
} | null>(null);
</script>

<template>
    <Head :title="item.name" />

    <div class="space-y-6">
        <section class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="item.name"
                description="Catalog item detail and profitability overview"
            />

            <div class="flex flex-wrap items-center gap-2">
                <CatalogActions
                    ref="catalogActionsRef"
                    :item="item"
                    variant="buttons"
                />
            </div>
        </section>

        <div class="grid gap-4 md:grid-cols-5">
            <article
                v-for="card in statCards"
                :key="card.label"
                class="rounded-md border border-border bg-card p-4"
            >
                <p
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    {{ card.label }}
                </p>
                <p class="mt-1 text-2xl font-semibold text-foreground">
                    {{ card.value }}
                </p>
            </article>
        </div>

        <div class="space-y-6">
            <div class="space-y-6">
                <article
                    class="space-y-5 rounded-md border border-border bg-card p-6"
                >
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">
                            Overview
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Product insight
                        </p>
                    </div>

                    <div class="grid gap-6 sm:grid-cols-[auto_1fr]">
                        <div
                            v-if="item.image_url"
                            class="overflow-hidden rounded-md border border-dashed border-border"
                        >
                            <img
                                :src="item.image_url"
                                alt="Product image"
                                class="h-48 w-48 object-cover"
                            />
                        </div>
                        <div
                            v-else
                            class="flex h-48 w-48 flex-col items-center justify-center gap-2 rounded-md border border-dashed border-border text-muted-foreground"
                        >
                            <ImageIcon class="h-12 w-12" />
                            <span class="text-sm">No image</span>
                        </div>

                        <div class="space-y-4">
                            <p
                                class="text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ descriptionText }}
                            </p>

                            <Separator />

                            <dl class="grid gap-4 sm:grid-cols-2">
                                <div
                                    v-for="fact in quickFacts"
                                    :key="fact.label"
                                >
                                    <dt
                                        class="text-xs tracking-wide text-muted-foreground uppercase"
                                    >
                                        {{ fact.label }}
                                    </dt>
                                    <dd
                                        class="text-sm font-semibold text-foreground"
                                    >
                                        <Badge
                                            v-if="fact.badge"
                                            :variant="fact.badge"
                                        >
                                            {{ fact.value }}
                                        </Badge>
                                        <span v-else>{{ fact.value }}</span>
                                    </dd>
                                </div>
                            </dl>

                            <Separator />

                            <div
                                v-if="appliedTaxBadges.length"
                                class="space-y-2"
                            >
                                <p
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Taxes
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <Badge
                                        v-for="tax in appliedTaxBadges"
                                        :key="tax.name"
                                        variant="outline"
                                        class="gap-1"
                                    >
                                        {{ tax.name }}
                                        <span class="text-muted-foreground">
                                            {{ tax.rate }}%
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground"
                                        >
                                            ({{
                                                tax.inclusive
                                                    ? 'inclusive'
                                                    : 'exclusive'
                                            }})
                                        </span>
                                    </Badge>
                                </div>
                            </div>
                            <div v-else class="space-y-2">
                                <p
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Taxes
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    No taxes applied.
                                </p>
                            </div>
                        </div>
                    </div>
                </article>

                <article
                    class="space-y-4 rounded-md border border-border bg-card p-6"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">
                                Variants
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Packaging options
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="catalogActionsRef?.openVariantDialog(null)"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Add Variant
                        </Button>
                    </div>

                    <div
                        v-if="hasVariants"
                        class="rounded-md border border-dashed border-border"
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>SKU</TableHead>
                                    <TableHead>Unit price</TableHead>
                                    <TableHead>Cost price</TableHead>
                                    <TableHead>Default</TableHead>
                                    <TableHead class="text-right"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="variant in item.variants"
                                    :key="variant.id"
                                >
                                    <TableCell>{{ variant.name }}</TableCell>
                                    <TableCell>{{
                                        variant.sku || '—'
                                    }}</TableCell>
                                    <TableCell>
                                        {{ formatCurrency(variant.unit_price) }}
                                    </TableCell>
                                    <TableCell>
                                        {{ formatCurrency(variant.cost_price) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            v-if="variant.is_default"
                                            variant="secondary"
                                        >
                                            Default
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            class="h-8 w-8"
                                                            @click="
                                                                catalogActionsRef?.openVariantDialog(
                                                                    variant,
                                                                )
                                                            "
                                                        >
                                                            <Edit
                                                                class="h-4 w-4"
                                                            />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Edit variant</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            class="h-8 w-8 text-destructive"
                                                            @click="
                                                                catalogActionsRef?.deleteVariant(
                                                                    variant.id,
                                                                )
                                                            "
                                                        >
                                                            <Trash2
                                                                class="h-4 w-4"
                                                            />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Delete variant</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div
                        v-else
                        class="rounded-md border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        No variants yet. Create tailored options for different
                        bundles.
                    </div>
                </article>

                <article
                    class="space-y-4 rounded-md border border-border bg-card p-6"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-2"
                    >
                        <div>
                            <h3 class="text-lg font-semibold text-foreground">
                                Price Tiers
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                Volume incentives
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="
                                catalogActionsRef?.openPriceTierDialog(null)
                            "
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Add Price Tier
                        </Button>
                    </div>

                    <div
                        v-if="hasPriceTiers"
                        class="rounded-md border border-dashed border-border"
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Min qty</TableHead>
                                    <TableHead>Max qty</TableHead>
                                    <TableHead>Pricing type</TableHead>
                                    <TableHead>Value</TableHead>
                                    <TableHead>Variant</TableHead>
                                    <TableHead class="text-right"
                                        >Actions</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="tier in item.price_tiers"
                                    :key="tier.id"
                                >
                                    <TableCell>{{
                                        tier.min_quantity
                                    }}</TableCell>
                                    <TableCell>
                                        {{ tier.max_quantity ?? 'No limit' }}
                                    </TableCell>
                                    <TableCell class="capitalize">
                                        {{
                                            tier.pricing_type.replace('_', ' ')
                                        }}
                                    </TableCell>
                                    <TableCell>
                                        <span v-if="tier.pricing_type === 'fixed_price'">
                                            {{ formatCurrency(tier.value) }}
                                        </span>
                                        <span v-else>
                                            {{ tier.value }}%
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        {{
                                            tier.variant_id
                                                ? item.variants?.find(
                                                      (v) =>
                                                          v.id ===
                                                          tier.variant_id,
                                                  )?.name || 'Unknown'
                                                : 'All variants'
                                        }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            class="h-8 w-8"
                                                            @click="
                                                                catalogActionsRef?.openPriceTierDialog(
                                                                    tier,
                                                                )
                                                            "
                                                        >
                                                            <Edit
                                                                class="h-4 w-4"
                                                            />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Edit price tier</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            class="h-8 w-8 text-destructive"
                                                            @click="
                                                                catalogActionsRef?.deletePriceTier(
                                                                    tier.id,
                                                                )
                                                            "
                                                        >
                                                            <Trash2
                                                                class="h-4 w-4"
                                                            />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <p>Delete price tier</p>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <div
                        v-else
                        class="rounded-md border border-dashed border-border p-6 text-sm text-muted-foreground"
                    >
                        No price tiers configured. Incentivize larger orders by
                        adding one.
                    </div>
                </article>
            </div>
        </div>
    </div>
</template>
