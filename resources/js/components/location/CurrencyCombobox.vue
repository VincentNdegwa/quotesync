<script setup lang="ts">
import { CheckIcon, ChevronsUpDownIcon } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { CurrencyOption } from '@/types';
import { currencyOptions, getCurrencyOptions } from '@/utils/location-options';

const model = defineModel<string>({
    default: '',
});

const props = withDefaults(
    defineProps<{
        options?: CurrencyOption[];
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        triggerClass?: string;
        commonLimit?: number;
    }>(),
    {
        options: () => currencyOptions,
        placeholder: 'Select currency...',
        searchPlaceholder: 'Search currency...',
        emptyText: 'No currency found.',
        triggerClass: 'w-full justify-between',
        commonLimit: 20,
    },
);

const open = ref(false);
const query = ref('');

const normalizedOptions = computed(() => props.options);

const selectedCurrency = computed(() =>
    normalizedOptions.value.find((currency) => currency.code === model.value),
);

const filteredCurrencies = computed(() => {
    const source = props.options;

    if (source.length === 0) {
        return [];
    }

    return getCurrencyOptions(query.value, props.commonLimit, source);
});

const selectCurrency = (selectedValue: string): void => {
    model.value = selectedValue;
    query.value = '';
    open.value = false;
};
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                :class="cn('w-full justify-between', triggerClass)"
            >
                {{ selectedCurrency?.label || placeholder }}
                <ChevronsUpDownIcon class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>

        <PopoverContent class="w-[var(--reka-popover-trigger-width)] p-0">
            <Command>
                <div class="flex h-9 items-center border-b px-3">
                    <Input
                        v-model="query"
                        class="h-8 border-none bg-transparent px-0 shadow-none focus-visible:ring-0"
                        :placeholder="searchPlaceholder"
                    />
                </div>

                <CommandList>
                    <CommandEmpty>{{ emptyText }}</CommandEmpty>

                    <CommandGroup>
                        <CommandItem
                            v-for="currency in filteredCurrencies"
                            :key="currency.code"
                            :value="currency.code"
                            @select="
                                (ev) =>
                                    selectCurrency(ev.detail.value as string)
                            "
                        >
                            {{ currency.label }}
                            <CheckIcon
                                :class="
                                    cn(
                                        'ml-auto h-4 w-4',
                                        model === currency.code
                                            ? 'opacity-100'
                                            : 'opacity-0',
                                    )
                                "
                            />
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template>
