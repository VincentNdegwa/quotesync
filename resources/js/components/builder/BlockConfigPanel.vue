<script setup lang="ts">
import { MousePointerClick } from 'lucide-vue-next';
import BaseConfigPanel from '@/components/builder/BaseConfigPanel.vue';
import CoverMessageConfig from '@/components/builder/config-panels/CoverMessageConfig.vue';
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
import ContentConfigSection from '@/components/builder/ContentConfigSection.vue';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type {
    Block,
    BlockConfigMap,
    BlockType,
    BuilderCatalogItem,
    BuilderTaxOption,
    LayoutBlock,
} from '@/types';
import { useBuilderData } from '@/composables/useBuilderData';
import { useBuilderStore } from '@/stores/builder';

const block = defineModel<Block | null>('block');

const { catalogItems, taxes } = useBuilderData();
const builderStore = useBuilderStore();

function blockAs<T extends BlockType>(b: Block): LayoutBlock<T> {
    return b as LayoutBlock<T>;
}

function _configOf<T extends BlockType>(b: Block): BlockConfigMap[T] {
    return b.config as BlockConfigMap[T];
}

const handleLogoFileSelected = (file: File | null, base64: string | null): void => {
    builderStore.pendingLogoFile = file;
    builderStore.pendingLogoBase64 = base64;
};
</script>

<template>
    <div class="h-full min-h-0 rounded-lg bg-card">
        <div v-if="block" class="border-b px-4 py-3">
            <div class="flex items-center justify-between">
                <Label
                    class="text-xs tracking-wide text-muted-foreground uppercase"
                    >Block visibility</Label
                >
                <Switch
                    :model-value="block.visible"
                    :disabled="block.locked"
                    @update:model-value="
                        (value) => {
                            if (block) block.visible = Boolean(value);
                        }
                    "
                />
            </div>
            <p v-if="block.locked" class="mt-2 text-xs text-muted-foreground">
                Required blocks cannot be hidden.
            </p>
        </div>

        <div
            v-if="!block"
            class="flex flex-1 flex-col items-center justify-center gap-2 p-8 text-muted-foreground"
        >
            <MousePointerClick class="size-8 opacity-50" />
            <p class="text-sm">Select a block to configure it.</p>
        </div>

        <template v-else>
            <div class="flex-1 divide-y overflow-y-auto">
                <HeaderConfig
                    v-if="block.type === 'header'"
                    v-model="blockAs<'header'>(block).config"
                    @logo-file-selected="handleLogoFileSelected"
                />
                <FromToConfig
                    v-else-if="block.type === 'from_to'"
                    v-model="blockAs<'from_to'>(block).config"
                />
                <CoverMessageConfig
                    v-else-if="block.type === 'cover_message'"
                    v-model="blockAs<'cover_message'>(block).config"
                />
                <LineItemsConfig
                    v-else-if="block.type === 'line_items'"
                    v-model="blockAs<'line_items'>(block).config"
                />
                <TotalsConfig
                    v-else-if="block.type === 'totals'"
                    v-model="blockAs<'totals'>(block).config"
                />
                <RichTextConfig
                    v-else-if="block.type === 'rich_text'"
                    v-model="blockAs<'rich_text'>(block).config"
                />
                <ImageConfig
                    v-else-if="block.type === 'image'"
                    v-model="blockAs<'image'>(block).config"
                />
                <ImageRowConfig
                    v-else-if="block.type === 'image_row'"
                    v-model="blockAs<'image_row'>(block).config"
                />
                <PaymentTermsConfig
                    v-else-if="block.type === 'payment_terms'"
                    v-model="blockAs<'payment_terms'>(block).config"
                />
                <TimelineConfig
                    v-else-if="block.type === 'timeline'"
                    v-model="blockAs<'timeline'>(block).config"
                />
                <TermsConfig
                    v-else-if="block.type === 'terms'"
                    v-model="blockAs<'terms'>(block).config"
                />
                <SignatureConfig
                    v-else-if="block.type === 'signature'"
                    v-model="blockAs<'signature'>(block).config"
                />
                <SpacerConfig
                    v-else-if="block.type === 'spacer'"
                    v-model="blockAs<'spacer'>(block).config"
                />

                <ContentConfigSection
                    v-if="
                        [
                            'cover_message',
                            'rich_text',
                            'terms',
                            'payment_terms',
                        ].includes(block.type)
                    "
                    v-model="block"
                />
                <BaseConfigPanel v-model="block" />
            </div>
        </template>
    </div>
</template>
