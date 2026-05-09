<script setup lang="ts">
import { computed } from 'vue';
import ColorPickerRow from '@/components/Colorpickerrow.vue';
import type { Block, ContentBlockConfig, FontSize } from '@/types';

const block = defineModel<Block>({ required: true });

const content = computed(() => block.value.config as ContentBlockConfig);

const fontSizeOptions: Array<FontSize | null> = [null, 'sm', 'md', 'lg'];
</script>

<template>
    <section class="px-4 py-3 space-y-2">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Text</p>
        <div class="flex gap-1">
            <button
                v-for="size in fontSizeOptions"
                :key="size ?? 'auto'"
                type="button"
                class="flex-1 rounded border py-1.5 transition-colors"
                :class="content.fontSize === size ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-muted-foreground/50'"
                @click="content.fontSize = size"
            >
                <span :class="size === 'sm' ? 'text-[10px]' : size === 'lg' ? 'text-sm' : 'text-xs'">
                    {{ size === null ? 'Auto' : size.toUpperCase() }}
                </span>
            </button>
        </div>
        <ColorPickerRow
            :model-value="content.textColor"
            placeholder="Inherit"
            @update:model-value="content.textColor = $event"
            @reset="content.textColor = null"
        />
    </section>
</template>
