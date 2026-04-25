<script setup lang="ts">
import { computed } from 'vue';
import ColorPickerRow from '@/components/Colorpickerrow.vue';
import type { BaseBlockConfig, Block, BorderRadius, BorderSide, Spacing } from '@/types';

const block = defineModel<Block>({ required: true });

const base = computed(() => block.value.config as BaseBlockConfig);

const spacingOptions: Spacing[] = ['none', 'xs', 'sm', 'md', 'lg', 'xl'];
const spacingLabel: Record<Spacing, string> = { none: '—', xs: 'XS', sm: 'SM', md: 'MD', lg: 'LG', xl: 'XL' };

const borderSides: BorderSide[] = ['none', 'all', 'top', 'bottom', 'left', 'right'];
const borderSideLabel: Record<BorderSide, string> = { none: 'None', all: 'All', top: 'Top', bottom: 'Btm', left: 'Left', right: 'Right' };

const borderRadiusOptions: BorderRadius[] = ['none', 'sm', 'md', 'lg', 'full'];
const borderRadiusClass: Record<BorderRadius, string> = { none: '', sm: 'rounded-sm', md: 'rounded', lg: 'rounded-lg', full: 'rounded-full' };

function sidePreviewStyle(side: BorderSide): Record<string, string> {
    if (side === 'none') { return {} }
    if (side === 'all') { return { border: '2px solid currentColor' } }
    const cap = side.charAt(0).toUpperCase() + side.slice(1);
    return { [`border${cap}`]: '2px solid currentColor' };
}
</script>

<template>
    <div class="divide-y">

        <section class="px-4 py-3 space-y-2">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Spacing</p>
            <div class="flex gap-1">
                <button
                    v-for="size in spacingOptions"
                    :key="size"
                    type="button"
                    class="flex-1 rounded border py-1.5 text-[11px] font-medium transition-colors"
                    :class="base.padding === size ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-muted-foreground/50'"
                    @click="base.padding = size"
                >
                    {{ spacingLabel[size] }}
                </button>
            </div>
        </section>

        <section class="px-4 py-3 space-y-2">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Background</p>
            <ColorPickerRow
                :model-value="base.background"
                placeholder="Transparent"
                @update:model-value="base.background = $event"
                @reset="base.background = null"
            />
        </section>

        <section class="px-4 py-3 space-y-3">
            <p class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Border</p>

            <div class="grid grid-cols-3 gap-1">
                <button
                    v-for="side in borderSides"
                    :key="side"
                    type="button"
                    class="flex flex-col items-center gap-1.5 rounded border px-1 py-2 transition-colors"
                    :class="base.border.sides === side ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-muted-foreground/50'"
                    @click="base.border.sides = side"
                >
                    <span class="block h-3.5 w-3.5 rounded-[2px]" :style="sidePreviewStyle(side)" />
                    <span class="text-[10px] leading-none">{{ borderSideLabel[side] }}</span>
                </button>
            </div>

            <template v-if="base.border.sides !== 'none'">
                <div class="space-y-1.5">
                    <p class="text-[11px] text-muted-foreground">Width</p>
                    <div class="flex gap-1">
                        <button
                            v-for="w in ['thin', 'medium', 'thick']"
                            :key="w"
                            type="button"
                            class="flex flex-1 items-center justify-center rounded border py-2 transition-colors"
                            :class="base.border.width === w ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-muted-foreground/50'"
                            @click="base.border.width = w as 'thin' | 'medium' | 'thick'"
                        >
                            <span
                                class="block w-5 rounded-full bg-current"
                                :class="w === 'thin' ? 'h-px' : w === 'medium' ? 'h-0.5' : 'h-1'"
                            />
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <p class="text-[11px] text-muted-foreground">Style</p>
                    <div class="flex gap-1">
                        <button
                            v-for="style in ['solid', 'dashed', 'dotted']"
                            :key="style"
                            type="button"
                            class="flex-1 rounded border py-1.5 text-[11px] transition-colors"
                            :class="base.border.style === style ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-muted-foreground/50'"
                            @click="base.border.style = style as 'solid' | 'dashed' | 'dotted'"
                        >
                            {{ style }}
                        </button>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <p class="text-[11px] text-muted-foreground">Color</p>
                    <ColorPickerRow
                        :model-value="base.border.color"
                        placeholder="Default"
                        @update:model-value="base.border.color = $event"
                        @reset="base.border.color = null"
                    />
                </div>
            </template>

            <div class="space-y-1.5">
                <p class="text-[11px] text-muted-foreground">Corners</p>
                <div class="flex gap-1">
                    <button
                        v-for="r in borderRadiusOptions"
                        :key="r"
                        type="button"
                        class="flex flex-1 flex-col items-center gap-1.5 rounded border py-2 transition-colors"
                        :class="base.border.radius === r ? 'border-primary bg-primary/10 text-primary' : 'text-muted-foreground hover:border-muted-foreground/50'"
                        @click="base.border.radius = r"
                    >
                        <span
                            class="block h-3.5 w-3.5 border border-current"
                            :class="borderRadiusClass[r]"
                        />
                        <span class="text-[10px] leading-none">{{ r === 'none' ? '—' : r }}</span>
                    </button>
                </div>
            </div>
        </section>

    </div>
</template>