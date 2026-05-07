<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ChevronRight,
    Clock,
    Mail,
    MessageCircle,
    Phone,
    Plus,
    Trash2,
    Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Sheet,
    SheetClose,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import TiptapEditor from '@/components/ui/tiptap-editor/TiptapEditor.vue';
import { useEnums } from '@/composables/useEnums';
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';

type Channel = 'email' | 'whatsapp' | 'sms';

type Step = {
    id?: number;
    day_offset: number;
    channel: Channel;
    subject: string;
    message_template: string;
    sort_order: number;
};

type Sequence = {
    id: number;
    name: string;
    is_default: boolean;
    steps: Step[];
};

const props = defineProps<{
    sequences: Sequence[];
    placeholders: Record<string, string>;
}>();

defineOptions({ layout: ConfigurationLayout });

const { enums, getFollowUpChannelIcon, getFollowUpChannelColor } = useEnums();

const drawerOpen = ref(false);
const editingSequence = ref<Sequence | null>(null);
const activeStepIndex = ref<number | null>(null);
const subjectInputRef = ref<InstanceType<typeof Input> | null>(null);
const tiptapEditorRef = ref<{ insertText: (text: string) => void } | null>(
    null,
);
const deleteOpen = ref(false);
const sequenceToDelete = ref<Sequence | null>(null);

const emptyStep = (sortOrder = 0): Step => ({
    day_offset: sortOrder === 0 ? 2 : (sortOrder + 1) * 3,
    channel: 'email',
    subject: '',
    message_template: '',
    sort_order: sortOrder,
});

const form = useForm<{
    name: string;
    is_default: boolean;
    steps: Step[];
}>({
    name: '',
    is_default: false,
    steps: [emptyStep(0)],
});

const openCreate = (): void => {
    editingSequence.value = null;
    form.reset();
    form.name = '';
    form.is_default = false;
    form.steps = [emptyStep(0)];
    activeStepIndex.value = 0;
    drawerOpen.value = true;
};

const openEdit = (sequence: Sequence): void => {
    editingSequence.value = sequence;
    form.name = sequence.name;
    form.is_default = sequence.is_default;
    form.steps = sequence.steps.map((s) => ({ ...s }));
    activeStepIndex.value = 0;
    drawerOpen.value = true;
};

const closeDrawer = (): void => {
    drawerOpen.value = false;
    editingSequence.value = null;
    activeStepIndex.value = null;
};

const removeSequence = (sequence: Sequence): void => {
    sequenceToDelete.value = sequence;
    deleteOpen.value = true;
};

const executeDelete = (): void => {
    if (sequenceToDelete.value) {
        router.delete(
            `/configuration/follow-ups/${sequenceToDelete.value.id}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    deleteOpen.value = false;
                    sequenceToDelete.value = null;
                },
            },
        );
    }
};

const addStep = (): void => {
    const lastDay =
        form.steps.length > 0
            ? form.steps[form.steps.length - 1].day_offset
            : 0;
    const newStep = emptyStep(form.steps.length);
    newStep.day_offset = lastDay + 3;
    form.steps.push(newStep);
    activeStepIndex.value = form.steps.length - 1;
};

const removeStep = (index: number): void => {
    form.steps.splice(index, 1);

    if (
        activeStepIndex.value !== null &&
        activeStepIndex.value >= form.steps.length
    ) {
        activeStepIndex.value = form.steps.length - 1;
    }
};

const insertPlaceholder = (key: string): void => {
    if (activeStepIndex.value === null) {
        return;
    }

    const token = `{${key}}`;

    // Check if subject input is currently focused (use $el to get native DOM element from component)
    const inputEl = subjectInputRef.value?.$el as HTMLInputElement | null;

    if (inputEl && document.activeElement === inputEl) {
        const start = inputEl.selectionStart ?? 0;
        const end = inputEl.selectionEnd ?? 0;
        const currentValue = form.steps[activeStepIndex.value].subject;
        form.steps[activeStepIndex.value].subject =
            currentValue.slice(0, start) + token + currentValue.slice(end);
        setTimeout(() => {
            inputEl.setSelectionRange(
                start + token.length,
                start + token.length,
            );
            inputEl.focus();
        }, 0);
    }
    // Otherwise insert into Tiptap editor at cursor position
    else if (tiptapEditorRef.value) {
        tiptapEditorRef.value.insertText(token);
    }
    // Fallback: append to message template
    else {
        form.steps[activeStepIndex.value].message_template += token;
    }
};

const submit = (): void => {
    const stepsWithOrder = form.steps.map((s, i) => ({ ...s, sort_order: i }));
    const payload = { ...form.data(), steps: stepsWithOrder };

    if (editingSequence.value) {
        form.transform(() => payload).put(
            `/configuration/follow-ups/${editingSequence.value.id}`,
            { onSuccess: closeDrawer },
        );
    } else {
        form.transform(() => payload).post('/configuration/follow-ups', {
            onSuccess: closeDrawer,
        });
    }
};

const channelLabel = (value: string): string =>
    enums.followUpChannel?.find((c: { value: string }) => c.value === value)
        ?.label ?? value;

const placeholderGroups = computed(() => ({
    quote: {
        label: 'Quote',
        keys: [
            'quote_number',
            'quote_title',
            'quote_link',
            'total',
            'currency',
            'valid_until',
            'issue_date',
        ],
    },
    client: {
        label: 'Client',
        keys: ['client_name', 'client_contact', 'client_email'],
    },
    sender: {
        label: 'You',
        keys: ['company_name', 'user_name', 'user_email'],
    },
}));
</script>

<template>
    <Head title="Configuration - Follow-ups" />

    <div class="space-y-6">
        <div class="flex items-start justify-between">
            <Heading
                variant="small"
                title="Follow-up sequences"
                description="Automate follow-up messages sent to clients after a quote is delivered."
            />
            <Button @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                New sequence
            </Button>
        </div>

        <div v-if="sequences.length > 0" class="space-y-3">
            <div
                v-for="sequence in sequences"
                :key="sequence.id"
                class="group rounded-xl border bg-card transition-shadow hover:shadow-sm"
            >
                <div class="flex items-center gap-3 px-4 py-3">
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <Zap class="h-4 w-4 shrink-0 text-muted-foreground" />
                        <span class="truncate font-medium">{{
                            sequence.name
                        }}</span>
                        <Badge
                            v-if="sequence.is_default"
                            variant="default"
                            class="shrink-0 text-xs"
                        >
                            Default
                        </Badge>
                    </div>
                    <div
                        class="flex items-center gap-1 opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openEdit(sequence)"
                        >
                            Edit
                            <ChevronRight class="ml-1 h-3.5 w-3.5" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="h-8 w-8 text-muted-foreground hover:text-destructive"
                            @click="removeSequence(sequence)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </Button>
                    </div>
                </div>

                <div class="border-t px-4 pt-3 pb-4">
                    <div class="flex items-center gap-0">
                        <template
                            v-for="(step, si) in sequence.steps"
                            :key="step.id ?? si"
                        >
                            <div class="flex flex-col items-center gap-1">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 bg-background"
                                    :class="
                                        getFollowUpChannelColor(step.channel)
                                    "
                                >
                                    <component
                                        :is="
                                            getFollowUpChannelIcon(step.channel)
                                        "
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                                <span
                                    class="text-[10px] font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    Day {{ step.day_offset }}
                                </span>
                            </div>
                            <div
                                v-if="si < sequence.steps.length - 1"
                                class="mx-1 mb-4 h-px flex-1 border-t border-dashed border-muted-foreground/30"
                                style="min-width: 24px"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="rounded-xl border border-dashed p-12 text-center">
            <Zap class="mx-auto mb-3 h-8 w-8 text-muted-foreground/40" />
            <p class="font-medium text-foreground">No follow-up sequences</p>
            <p class="mt-1 text-sm text-muted-foreground">
                Create a sequence to automatically follow up with clients after
                sending a quote.
            </p>
            <Button class="mt-4" @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                Create your first sequence
            </Button>
        </div>
    </div>

    <Sheet
        :open="drawerOpen"
        @update:open="(value: boolean) => (drawerOpen = value)"
    >
        <SheetContent side="right" custom-width="900px" class="overflow-y-auto">
            <SheetHeader>
                <SheetTitle>
                    {{
                        editingSequence
                            ? 'Edit sequence'
                            : 'New follow-up sequence'
                    }}
                </SheetTitle>
                <SheetDescription>
                    {{
                        editingSequence
                            ? 'Update steps and settings.'
                            : 'Define when and how to follow up with clients.'
                    }}
                </SheetDescription>
            </SheetHeader>

            <div class="custom-scrollbar flex min-h-0 flex-1 overflow-hidden">
                <div class="w-[200px] shrink-0 overflow-y-auto border-r py-4">
                    <p
                        class="mb-2 px-4 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Steps
                    </p>

                    <div class="relative px-4">
                        <div
                            class="absolute top-4 bottom-4 left-[36px] w-px bg-border"
                        />

                        <div class="space-y-1">
                            <button
                                v-for="(step, index) in form.steps"
                                :key="index"
                                type="button"
                                class="relative flex w-full items-center gap-3 rounded-lg px-2 py-2.5 text-left transition-colors"
                                :class="
                                    activeStepIndex === index
                                        ? 'bg-primary/10 text-primary'
                                        : 'text-muted-foreground hover:bg-muted/50'
                                "
                                @click="activeStepIndex = index"
                            >
                                <div
                                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 bg-background transition-colors"
                                    :class="
                                        activeStepIndex === index
                                            ? 'border-primary'
                                            : 'border-border'
                                    "
                                >
                                    <component
                                        :is="
                                            getFollowUpChannelIcon(step.channel)
                                        "
                                        class="h-3 w-3"
                                        :class="
                                            activeStepIndex === index
                                                ? 'text-primary'
                                                : 'text-muted-foreground'
                                        "
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs leading-none font-medium">
                                        Day {{ step.day_offset }}
                                    </p>
                                    <p
                                        class="mt-0.5 truncate text-[10px] capitalize"
                                    >
                                        {{ channelLabel(step.channel) }}
                                    </p>
                                </div>
                            </button>

                            <button
                                type="button"
                                class="relative flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left text-muted-foreground transition-colors hover:bg-muted/50 hover:text-foreground"
                                @click="addStep"
                            >
                                <div
                                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 border-dashed border-muted-foreground/40"
                                >
                                    <Plus class="h-3 w-3" />
                                </div>
                                <span class="text-xs">Add step</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <div class="space-y-3 border-b px-6 py-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Sequence name
                                </Label>
                                <Input
                                    v-model="form.name"
                                    placeholder="e.g. Standard follow-up"
                                    class="h-9"
                                />
                                <InputError :message="form.errors.name" />
                            </div>
                            <div class="space-y-1.5">
                                <Label
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Default sequence
                                </Label>
                                <div class="flex h-9 items-center gap-2">
                                    <Switch
                                        :model-value="form.is_default"
                                        @update:model-value="
                                            (v: boolean) =>
                                                (form.is_default = v)
                                        "
                                    />
                                    <span class="text-sm text-muted-foreground">
                                        {{
                                            form.is_default
                                                ? 'Applied to all new quotes'
                                                : 'Manual selection only'
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="
                            activeStepIndex !== null &&
                            form.steps[activeStepIndex]
                        "
                        class="space-y-5 px-6 py-5"
                    >
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold">
                                Step {{ activeStepIndex + 1 }}
                                <span
                                    class="ml-1 font-normal text-muted-foreground"
                                >
                                    —
                                    {{
                                        channelLabel(
                                            form.steps[activeStepIndex].channel,
                                        )
                                    }}
                                    on day
                                    {{ form.steps[activeStepIndex].day_offset }}
                                </span>
                            </h3>
                            <Button
                                v-if="form.steps.length > 1"
                                type="button"
                                variant="ghost"
                                size="sm"
                                class="h-7 gap-1 text-xs text-muted-foreground hover:text-destructive"
                                @click="removeStep(activeStepIndex)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                                Remove step
                            </Button>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Send on day
                                </Label>
                                <div class="relative">
                                    <Input
                                        type="number"
                                        v-model.number="
                                            form.steps[activeStepIndex]
                                                .day_offset
                                        "
                                        min="1"
                                        class="h-9 pr-12"
                                    />
                                    <span
                                        class="pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-xs text-muted-foreground"
                                    >
                                        after sent
                                    </span>
                                </div>
                                <p class="text-[11px] text-muted-foreground">
                                    Day 1 = next day after quote is sent
                                </p>
                            </div>

                            <div class="space-y-1.5">
                                <Label
                                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                                >
                                    Channel
                                </Label>
                                <Select
                                    v-model="
                                        form.steps[activeStepIndex].channel
                                    "
                                >
                                    <SelectTrigger class="h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="email">
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Mail
                                                    class="h-3.5 w-3.5 text-blue-500"
                                                />
                                                Email
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="whatsapp" disabled>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <MessageCircle
                                                    class="h-3.5 w-3.5 text-emerald-500"
                                                />
                                                WhatsApp
                                                <Badge
                                                    variant="secondary"
                                                    class="h-4 px-1 text-[10px]"
                                                    >Soon</Badge
                                                >
                                            </div>
                                        </SelectItem>
                                        <SelectItem value="sms" disabled>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Phone
                                                    class="h-3.5 w-3.5 text-orange-500"
                                                />
                                                SMS
                                                <Badge
                                                    variant="secondary"
                                                    class="h-4 px-1 text-[10px]"
                                                    >Soon</Badge
                                                >
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div
                            v-if="
                                form.steps[activeStepIndex].channel === 'email'
                            "
                            class="space-y-1.5"
                        >
                            <Label
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Email subject
                            </Label>
                            <Input
                                ref="subjectInputRef"
                                v-model="form.steps[activeStepIndex].subject"
                                placeholder="Following up on {quote_title} — {quote_number}"
                                class="h-9"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Message
                            </Label>
                            <TiptapEditor
                                ref="tiptapEditorRef"
                                v-model="
                                    form.steps[activeStepIndex].message_template
                                "
                                placeholder="Write your follow-up message here. Use placeholders below to personalise it."
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `steps.${activeStepIndex}.message_template`
                                    ]
                                "
                            />
                        </div>

                        <div
                            class="space-y-2.5 rounded-lg border bg-muted/30 p-3"
                        >
                            <p
                                class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Insert placeholder
                            </p>
                            <div
                                v-for="(group, groupKey) in placeholderGroups"
                                :key="groupKey"
                                class="space-y-1.5"
                            >
                                <p
                                    class="text-[10px] font-medium tracking-wide text-muted-foreground/60 uppercase"
                                >
                                    {{ group.label }}
                                </p>
                                <div class="flex flex-wrap gap-1.5">
                                    <button
                                        v-for="key in group.keys"
                                        :key="key"
                                        type="button"
                                        class="inline-flex items-center rounded-md border bg-background px-2 py-1 font-mono text-[11px] text-foreground transition-colors hover:border-primary hover:bg-primary/5 hover:text-primary"
                                        :title="placeholders[key]"
                                        @mousedown.prevent
                                        @click="insertPlaceholder(key)"
                                    >
                                        {{ '{' }}{{ key }}{{ '}' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex flex-col items-center justify-center gap-3 py-16 text-center"
                    >
                        <Clock class="h-8 w-8 text-muted-foreground/40" />
                        <p class="text-sm text-muted-foreground">
                            No steps yet. Add a step to get started.
                        </p>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addStep"
                        >
                            <Plus class="mr-2 h-3.5 w-3.5" />
                            Add first step
                        </Button>
                    </div>
                </div>
            </div>

            <SheetFooter>
                <p
                    v-if="form.steps.length > 0"
                    class="text-xs text-muted-foreground"
                >
                    {{ form.steps.length }} step{{
                        form.steps.length !== 1 ? 's' : ''
                    }}
                    · Sends from day
                    {{ Math.min(...form.steps.map((s) => s.day_offset)) }} to
                    day {{ Math.max(...form.steps.map((s) => s.day_offset)) }}
                </p>
                <div v-else />
                <div class="flex items-center gap-2">
                    <SheetClose as-child>
                        <Button variant="outline">Cancel</Button>
                    </SheetClose>
                    <Button
                        :disabled="
                            form.processing ||
                            !form.name.trim() ||
                            form.steps.length === 0
                        "
                        @click="submit"
                    >
                        {{
                            editingSequence ? 'Save changes' : 'Create sequence'
                        }}
                    </Button>
                </div>
            </SheetFooter>
        </SheetContent>
    </Sheet>

    <ConfirmDialog
        v-model:open="deleteOpen"
        title="Delete follow-up sequence"
        description="Are you sure you want to delete this follow-up sequence? This action cannot be undone."
        confirm-text="Delete"
        variant="destructive"
        @confirm="executeDelete"
    />
</template>
