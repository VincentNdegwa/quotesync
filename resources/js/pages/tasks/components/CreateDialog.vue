<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Check, ChevronsUpDown } from 'lucide-vue-next';
import { ref, watch, computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';

const open = defineModel<boolean>('open', {
    required: true,
});

type EntityContext = {
    type: 'quote' | 'invoice';
    id: number | null;
    title?: string | null;
    number?: string | null;
    locked?: boolean;
};

const props = defineProps<{
    users: Array<{ id: number; name: string; email: string }>;
    entity?: EntityContext | null;
}>();

const form = useForm({
    taskable_type: 'quote',
    taskable_id: null as number | null,
    title: '',
    description: '',
    assigned_to: null as number | null,
    due_date: '',
});

const availableEntities = ref<
    Array<{ id: number; title: string; number?: string }>
>([]);
const entitySearch = ref('');
const entityPopoverOpen = ref(false);
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const selectedEntityText = computed(() => {
    const entity = availableEntities.value.find(
        (e) => e.id === form.taskable_id,
    );

    if (entity) {
        return entity.number
            ? `${entity.title} (${entity.number})`
            : entity.title;
    }

    return `Select ${form.taskable_type === 'quote' ? 'quote' : 'invoice'}`;
});

const lockedEntity = computed(() => {
    if (!props.entity?.id) {
        return null;
    }

    return props.entity;
});

const isEntityLocked = computed(
    () => Boolean(lockedEntity.value?.locked && lockedEntity.value.id),
);

const lockedEntityLabel = computed(() => {
    if (!lockedEntity.value) {
        return '';
    }

    const title = lockedEntity.value.title || `#${lockedEntity.value.id}`;

    if (lockedEntity.value.number) {
        return `${title} (${lockedEntity.value.number})`;
    }

    return title;
});

function ensureLockedEntityOption(): void {
    if (!lockedEntity.value?.id) {
        return;
    }

    const exists = availableEntities.value.some(
        (entity) => entity.id === lockedEntity.value?.id,
    );

    if (!exists) {
        availableEntities.value = [
            {
                id: lockedEntity.value.id,
                title:
                    lockedEntity.value.title ||
                    lockedEntityLabel.value ||
                    `#${lockedEntity.value.id}`,
                number: lockedEntity.value.number || undefined,
            },
            ...availableEntities.value,
        ];
    }
}

watch(
    () => form.taskable_type,
    async (newType) => {
        if (!newType) {
            return;
        }

        entitySearch.value = '';
        await fetchEntities();
        ensureLockedEntityOption();
    },
    { immediate: true },
);

watch(
    () => props.entity,
    (entity) => {
        if (entity?.type) {
            form.taskable_type = entity.type;
        }

        if (entity?.id) {
            form.taskable_id = entity.id;
            ensureLockedEntityOption();
        } else if (!entity?.id && isEntityLocked.value === false) {
            form.taskable_id = null;
        }
    },
    { immediate: true },
);

watch(entitySearch, (_search: string) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        fetchEntities();
    }, 300);
});

async function fetchEntities(): Promise<void> {
    if (!form.taskable_type) {
        return;
    }

    try {
        const endpoint =
            form.taskable_type === 'quote' ? '/quotes' : '/invoices';
        const response = await fetch(
            `${endpoint}?search=${entitySearch.value}&per_page=50`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            },
        );
        const data = await response.json();

        if (data.data) {
            availableEntities.value = data.data.map((item: any) => ({
                 
                id: item.id,
                title: item.title || item.invoice_number || `#${item.id}`,
                number: item.quote_number || item.invoice_number,
            }));
        }
    } catch (error) {
        console.error('Failed to fetch entities:', error);
        availableEntities.value = [];
    }
}

const submit = (): void => {
    form.post('/tasks', {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
            form.reset();
            form.clearErrors();

            if (lockedEntity.value) {
                form.taskable_type = lockedEntity.value.type;
                form.taskable_id = lockedEntity.value.id;
                ensureLockedEntityOption();
            }
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => (open = value)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>Create task</DialogTitle>
                <DialogDescription
                    >Add a new task to your workspace.</DialogDescription
                >
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="task_create_title" required>Title</Label>
                    <Input
                        id="task_create_title"
                        v-model="form.title"
                        placeholder="Task title"
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_create_description">Description</Label>
                    <Textarea
                        id="task_create_description"
                        v-model="form.description"
                        placeholder="Task description..."
                        rows="3"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_create_assigned_to" required
                        >Assign To</Label
                    >
                    <Select v-model="form.assigned_to as number | undefined">
                        <SelectTrigger
                            class="w-full"
                            id="task_create_assigned_to"
                        >
                            <SelectValue placeholder="Select a team member" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="user in users"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                                <span
                                    v-if="user.email"
                                    class="text-xs text-muted-foreground"
                                    >({{ user.email }})</span
                                >
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.assigned_to" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_create_due_date">Due Date</Label>
                    <Input
                        id="task_create_due_date"
                        v-model="form.due_date"
                        type="date"
                    />
                    <InputError :message="form.errors.due_date" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_create_taskable_type"
                        >Related Entity Type</Label
                    >
                    <Select
                        v-model="form.taskable_type"
                        :disabled="isEntityLocked"
                    >
                        <SelectTrigger
                            class="w-full"
                            id="task_create_taskable_type"
                        >
                            <SelectValue placeholder="Select entity type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="quote">Quote</SelectItem>
                            <SelectItem value="invoice">Invoice</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.taskable_type" />
                </div>

                <div class="grid gap-2">
                    <Label for="task_create_taskable_id" required
                        >Related Entity</Label
                    >
                    <div v-if="isEntityLocked" class="rounded-md border bg-muted/40 px-3 py-2 text-sm">
                        {{ lockedEntityLabel }}
                    </div>
                    <Popover v-else v-model:open="entityPopoverOpen">
                        <PopoverTrigger as-child>
                            <Button
                                variant="outline"
                                :class="
                                    cn(
                                        'justify-between',
                                        !form.taskable_id &&
                                            'text-muted-foreground',
                                    )
                                "
                                class="w-full"
                            >
                                {{ selectedEntityText }}
                                <ChevronsUpDown
                                    class="ml-2 h-4 w-4 shrink-0 opacity-50"
                                />
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-[300px] p-0">
                            <Command>
                                <CommandInput
                                    v-model="entitySearch"
                                    placeholder="Search..."
                                    class="h-9"
                                />
                                <CommandList>
                                    <CommandEmpty
                                        >No
                                        {{
                                            form.taskable_type === 'quote'
                                                ? 'quotes'
                                                : 'invoices'
                                        }}
                                        found.</CommandEmpty
                                    >
                                    <CommandGroup>
                                        <CommandItem
                                            v-for="entity in availableEntities"
                                            :key="entity.id"
                                            :value="entity.id"
                                            @select="
                                                () => {
                                                    form.taskable_id =
                                                        entity.id as number;
                                                    entityPopoverOpen = false;
                                                }
                                            "
                                        >
                                            {{ entity.title }}
                                            <span
                                                v-if="entity.number"
                                                class="ml-2 text-xs text-muted-foreground"
                                                >({{ entity.number }})</span
                                            >
                                            <Check
                                                :class="
                                                    cn(
                                                        'ml-auto',
                                                        form.taskable_id ===
                                                            entity.id
                                                            ? 'opacity-100'
                                                            : 'opacity-0',
                                                    )
                                                "
                                                class="h-4 w-4"
                                            />
                                        </CommandItem>
                                    </CommandGroup>
                                </CommandList>
                            </Command>
                        </PopoverContent>
                    </Popover>
                    <InputError :message="form.errors.taskable_id" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="open = false"
                        >Cancel</Button
                    >
                    <Button type="submit" :disabled="form.processing"
                        >Create task</Button
                    >
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
