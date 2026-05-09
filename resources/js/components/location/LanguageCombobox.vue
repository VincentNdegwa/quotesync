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
import {
    getLanguageOptions,
    translationLanguageOptions,
} from '@/utils/location-options';
import type { LanguageOption } from '@/utils/location-options';

const model = defineModel<string>({
    default: '',
});

const props = withDefaults(
    defineProps<{
        options?: LanguageOption[];
        placeholder?: string;
        searchPlaceholder?: string;
        emptyText?: string;
        triggerClass?: string;
    }>(),
    {
        options: () => translationLanguageOptions,
        placeholder: 'Select language...',
        searchPlaceholder: 'Search language...',
        emptyText: 'No language found.',
        triggerClass: 'w-full justify-between',
    },
);

const open = ref(false);
const query = ref('');

const selectedLanguage = computed(() =>
    props.options.find((language) => language.code === model.value),
);

const filteredLanguages = computed(() => {
    if (props.options.length === 0) {
        return [];
    }

    return getLanguageOptions(query.value, props.options);
});

const selectLanguage = (selectedValue: string): void => {
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
                {{ selectedLanguage?.label || placeholder }}
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
                            v-for="language in filteredLanguages"
                            :key="language.code"
                            :value="language.code"
                            @select="
                                (ev) =>
                                    selectLanguage(ev.detail.value as string)
                            "
                        >
                            {{ language.label }}
                            <CheckIcon
                                :class="
                                    cn(
                                        'ml-auto h-4 w-4',
                                        model === language.code
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
