<script setup lang="ts">
import { MousePointerClick } from 'lucide-vue-next';
import CoverMessageConfig from '@/components/builder/config-panels/CoverMessageConfig.vue';
import DividerConfig from '@/components/builder/config-panels/DividerConfig.vue';
import FromToConfig from '@/components/builder/config-panels/FromToConfig.vue';
import HeaderConfig from '@/components/builder/config-panels/HeaderConfig.vue';
import ImageConfig from '@/components/builder/config-panels/ImageConfig.vue';
import ImageRowConfig from '@/components/builder/config-panels/ImageRowConfig.vue';
import LineItemsConfig from '@/components/builder/config-panels/LineItemsConfig.vue';
import PaymentTermsConfig from '@/components/builder/config-panels/PaymentTermsConfig.vue';
import RichTextConfig from '@/components/builder/config-panels/RichTextConfig.vue';
import SignatureConfig from '@/components/builder/config-panels/SignatureConfig.vue';
import SpacerConfig from '@/components/builder/config-panels/SpacerConfig.vue';
import TermsConfig from '@/components/builder/config-panels/TermsConfig.vue';
import TimelineConfig from '@/components/builder/config-panels/TimelineConfig.vue';
import TotalsConfig from '@/components/builder/config-panels/TotalsConfig.vue';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type { Block, BuilderCatalogItem, BuilderTaxOption } from '@/types';

const block = defineModel<Block | null>('block', { required: true });

defineProps<{
    catalogItems: BuilderCatalogItem[];
    taxes: BuilderTaxOption[];
}>();
</script>

<template>
    <div class="flex h-full min-h-0 flex-col rounded-lg border bg-card">
        <div v-if="block" class="border-b px-4 py-3">
            <div class="flex items-center justify-between">
                <Label class="text-xs uppercase tracking-wide text-muted-foreground">Block visibility</Label>
                <Switch
                    :model-value="block.visible"
                    :disabled="block.locked"
                    @update:model-value="(value) => (block.visible = Boolean(value))"
                />
            </div>
            <p v-if="block.locked" class="mt-2 text-xs text-muted-foreground">Required blocks cannot be hidden.</p>
        </div>

        <div v-if="!block" class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-muted-foreground">
            <MousePointerClick class="size-8 opacity-50" />
            <p class="text-sm">Select a block to configure it.</p>
        </div>

        <HeaderConfig v-else-if="block.type === 'header'" v-model="block.config" />
        <FromToConfig v-else-if="block?.type === 'from_to'" v-model="block.config" />
        <CoverMessageConfig v-else-if="block?.type === 'cover_message'" v-model="block.config" />
        <LineItemsConfig
            v-else-if="block?.type === 'line_items'"
            v-model="block.config"
        />
        <TotalsConfig v-else-if="block?.type === 'totals'" v-model="block.config" />
        <RichTextConfig v-else-if="block?.type === 'rich_text'" v-model="block.config" />
        <ImageConfig v-else-if="block?.type === 'image'" v-model="block.config" />
        <ImageRowConfig v-else-if="block?.type === 'image_row'" v-model="block.config" />
        <PaymentTermsConfig v-else-if="block?.type === 'payment_terms'" v-model="block.config" />
        <TimelineConfig v-else-if="block?.type === 'timeline'" v-model="block.config" />
        <TermsConfig v-else-if="block?.type === 'terms'" v-model="block.config" />
        <SignatureConfig v-else-if="block?.type === 'signature'" v-model="block.config" />
        <DividerConfig v-else-if="block?.type === 'divider'" v-model="block.config" />
        <SpacerConfig v-else-if="block?.type === 'spacer'" v-model="block.config" />
        <div v-else class="p-4 text-sm text-muted-foreground">Select a block from the list.</div>
    </div>
</template>
