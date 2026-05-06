<script setup lang="ts">
import { computed, ref } from 'vue';
import { ChevronsUpDownIcon, CheckIcon, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import type { BuilderCatalogItem } from '@/types';

const props = defineProps<{
    catalogItems: BuilderCatalogItem[];
    recentItemIds?: number[];
    placeholder?: string;
    ctaCopy?: string;
}>();

const emit = defineEmits<{
    (e: 'select', catalogItem: BuilderCatalogItem): void;
}>();

const open = ref(false);
const query = ref('');

const recentItems = computed(() => {
    if (!props.recentItemIds?.length) {
        return [];
    }

    return props.recentItemIds
        .map((id) => props.catalogItems.find((item) => item.id === id))
        .filter((item): item is BuilderCatalogItem => Boolean(item))
        .slice(0, 5);
});

const filteredItems = computed(() => {
    if (!query.value) {
        return props.catalogItems.slice(0, 25);
    }

    const term = query.value.toLowerCase();

    return props.catalogItems
        .filter((item) =>
            item.name.toLowerCase().includes(term) ||
            (item.sku && item.sku.toLowerCase().includes(term)),
        )
        .slice(0, 25);
});

const handleSelect = (catalogItem: BuilderCatalogItem): void => {
    emit('select', catalogItem);
    query.value = '';
    open.value = false;
};

</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <slot name="trigger">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-8 gap-2"
                >
                    <slot name="label">Add item</slot>
                    <ChevronsUpDownIcon class="h-4 w-4 opacity-50" />
                </Button>
            </slot>
        </PopoverTrigger>
        <PopoverContent class="w-[320px] p-0" align="start">
            <Command>
                <CommandInput
                    v-model="query"
                    :placeholder="props.placeholder ?? 'Search catalog items...'"
                />
                <CommandList>
                    <CommandEmpty>No catalog items found.</CommandEmpty>

                    <CommandGroup>
                        <CommandItem
                            v-for="item in filteredItems"
                            :key="item.id"
                            :value="String(item.id)"
                            @select="() => handleSelect(item)"
                        >
                            <div class="flex text-start flex-col text-sm">
                                <span class="font-medium">{{ item.name }}</span>
                                <span v-if="item.sku" class="text-xs text-muted-foreground">SKU {{ item.sku }}</span>
                            </div>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
