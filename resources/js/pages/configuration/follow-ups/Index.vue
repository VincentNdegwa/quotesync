<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ChevronRight,
    Clock,
    GripVertical,
    Mail,
    MessageSquare,
    Phone,
    Plus,
    ToggleLeft,
    Trash2,
    X,
    Zap,
} from 'lucide-vue-next';
import { computed, nextTick, ref } from 'vue';
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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { useEnums } from '@/composables/useEnums';
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';

// ─── Types ────────────────────────────────────────────────────────────────────

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

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    sequences: Sequence[];
    placeholders: Record<string, string>;
}>();

defineOptions({ layout: ConfigurationLayout });

const { enums } = useEnums();

// ─── Slide-over state ─────────────────────────────────────────────────────────

const slideOverOpen = ref(false);
const editingSequence = ref<Sequence | null>(null);
const activeStepIndex = ref<number | null>(null);

// ─── Textarea refs for cursor-aware placeholder insertion ─────────────────────

const textareaRefs = ref<Record<number, HTMLTextAreaElement | null>>({});

const setTextareaRef = (el: unknown, index: number): void => {
    textareaRefs.value[index] = el as HTMLTextAreaElement | null;
};

// ─── Form ─────────────────────────────────────────────────────────────────────

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

// ─── Open / close ─────────────────────────────────────────────────────────────

const openCreate = (): void => {
    editingSequence.value = null;
    form.reset();
    form.name = '';
    form.is_default = false;
    form.steps = [emptyStep(0)];
    activeStepIndex.value = 0;
    slideOverOpen.value = true;
};

const openEdit = (sequence: Sequence): void => {
    editingSequence.value = sequence;
    form.name = sequence.name;
    form.is_default = sequence.is_default;
    form.steps = sequence.steps.map((s) => ({ ...s }));
    activeStepIndex.value = 0;
    slideOverOpen.value = true;
};

const closeSlideOver = (): void => {
    slideOverOpen.value = false;
    editingSequence.value = null;
    activeStepIndex.value = null;
};

// ─── Step management ──────────────────────────────────────────────────────────

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

// ─── Placeholder insertion — cursor-aware ─────────────────────────────────────

const insertPlaceholder = (key: string, stepIndex: number): void => {
    const el = textareaRefs.value[stepIndex];
    const token = `{${key}}`;

    if (!el) {
        form.steps[stepIndex].message_template += token;
        return;
    }

    const start =
        el.selectionStart ?? form.steps[stepIndex].message_template.length;
    const end = el.selectionEnd ?? start;
    const current = form.steps[stepIndex].message_template;

    form.steps[stepIndex].message_template =
        current.slice(0, start) + token + current.slice(end);

    nextTick(() => {
        el.focus();
        const pos = start + token.length;
        el.setSelectionRange(pos, pos);
    });
};

// ─── Submit ───────────────────────────────────────────────────────────────────

const submit = (): void => {
    const stepsWithOrder = form.steps.map((s, i) => ({ ...s, sort_order: i }));
    const payload = { ...form.data(), steps: stepsWithOrder };

    if (editingSequence.value) {
        form.transform(() => payload).put(
            `/configuration/follow-ups/${editingSequence.value.id}`,
            { onSuccess: closeSlideOver },
        );
    } else {
        form.transform(() => payload).post('/configuration/follow-ups', {
            onSuccess: closeSlideOver,
        });
    }
};

// ─── Channel icon ─────────────────────────────────────────────────────────────

const channelIcon = (channel: string) => {
    return (
        { email: Mail, whatsapp: MessageSquare, sms: Phone }[channel] ?? Mail
    );
};

const channelLabel = (value: string): string =>
    enums.followUpChannel?.find((c: { value: string }) => c.value === value)
        ?.label ?? value;

const channelColor = (channel: string): string =>
    ({
        email: 'text-blue-600 bg-blue-50 border-blue-200',
        whatsapp: 'text-emerald-600 bg-emerald-50 border-emerald-200',
        sms: 'text-orange-600 bg-orange-50 border-orange-200',
    })[channel] ?? 'text-muted-foreground bg-muted';

// ─── Timeline display ─────────────────────────────────────────────────────────

const sortedSteps = computed(() =>
    [...form.steps].sort((a, b) => a.day_offset - b.day_offset),
);

// ─── Placeholder groups for better UX ────────────────────────────────────────

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

        <!-- ── Sequence list ──────────────────────────────────────────────── -->
        <div v-if="sequences.length > 0" class="space-y-3">
            <div
                v-for="sequence in sequences"
                :key="sequence.id"
                class="group rounded-xl border bg-card transition-shadow hover:shadow-sm"
            >
                <!-- Header -->
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
                        <Link
                            :href="`/configuration/follow-ups/${sequence.id}`"
                            method="delete"
                            as="button"
                            preserve-scroll
                        >
                            <Button
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 text-muted-foreground hover:text-destructive"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </Link>
                    </div>
                </div>

                <!-- Timeline strip -->
                <div class="border-t px-4 pt-3 pb-4">
                    <div class="flex items-center gap-0">
                        <template
                            v-for="(step, si) in sequence.steps"
                            :key="step.id ?? si"
                        >
                            <!-- Step node -->
                            <div class="flex flex-col items-center gap-1">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full border-2 bg-background"
                                    :class="channelColor(step.channel)"
                                >
                                    <component
                                        :is="channelIcon(step.channel)"
                                        class="h-3.5 w-3.5"
                                    />
                                </div>
                                <span
                                    class="text-[10px] font-medium whitespace-nowrap text-muted-foreground"
                                >
                                    Day {{ step.day_offset }}
                                </span>
                            </div>
                            <!-- Connector line between steps -->
                            <div
                                v-if="si < sequence.steps.length - 1"
                                class="mx-1 h-px flex-1 border-t border-dashed border-muted-foreground/30"
                                style="min-width: 24px"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
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

    <!-- ── Slide-over ──────────────────────────────────────────────────────── -->
    <Transition name="overlay-fade">
        <div
            v-if="slideOverOpen"
            class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
            @click="closeSlideOver"
        />
    </Transition>

    <Transition name="slide-over">
        <div
            v-if="slideOverOpen"
            class="fixed inset-y-0 right-0 z-50 flex w-full max-w-2xl flex-col bg-background shadow-2xl"
        >
            <!-- Slide-over header -->
            <div
                class="flex shrink-0 items-center justify-between border-b px-6 py-4"
            >
                <div>
                    <h2 class="text-base font-semibold">
                        {{
                            editingSequence
                                ? 'Edit sequence'
                                : 'New follow-up sequence'
                        }}
                    </h2>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{
                            editingSequence
                                ? 'Update steps and settings.'
                                : 'Define when and how to follow up with clients.'
                        }}
                    </p>
                </div>
                <Button variant="ghost" size="icon" @click="closeSlideOver">
                    <X class="h-4 w-4" />
                </Button>
            </div>

            <!-- Slide-over body -->
            <div class="flex min-h-0 flex-1 overflow-hidden">
                <!-- LEFT: step timeline nav (fixed) -->
                <div class="w-[200px] shrink-0 overflow-y-auto border-r py-4">
                    <p
                        class="mb-2 px-4 text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Steps
                    </p>

                    <div class="relative px-4">
                        <!-- Vertical line -->
                        <div
                            class="absolute top-4 bottom-4 left-[27px] w-px bg-border"
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
                                <!-- Timeline dot -->
                                <div
                                    class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full border-2 bg-background transition-colors"
                                    :class="
                                        activeStepIndex === index
                                            ? 'border-primary'
                                            : 'border-border'
                                    "
                                >
                                    <component
                                        :is="channelIcon(step.channel)"
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

                            <!-- Add step button inline in timeline -->
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

                <!-- RIGHT: active step editor + sequence settings -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Sequence settings (always visible at top) -->
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

                    <!-- Active step editor -->
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

                        <!-- Day + Channel -->
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
                                                <MessageSquare
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

                        <!-- Subject (email only) -->
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
                                v-model="form.steps[activeStepIndex].subject"
                                placeholder="Following up on {quote_title} — {quote_number}"
                                class="h-9"
                            />
                        </div>

                        <!-- Message -->
                        <div class="space-y-1.5">
                            <Label
                                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Message
                            </Label>
                            <Textarea
                                :ref="
                                    (el) => setTextareaRef(el, activeStepIndex)
                                "
                                v-model="
                                    form.steps[activeStepIndex].message_template
                                "
                                :rows="7"
                                placeholder="Write your follow-up message here. Use placeholders below to personalise it."
                                class="resize-none font-mono text-sm leading-relaxed"
                            />
                            <InputError
                                :message="
                                    form.errors[
                                        `steps.${activeStepIndex}.message_template`
                                    ]
                                "
                            />
                        </div>

                        <!-- Placeholder picker — grouped -->
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
                                        @click="
                                            insertPlaceholder(
                                                key,
                                                activeStepIndex,
                                            )
                                        "
                                    >
                                        {{ '{' }}{{ key }}{{ '}' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- No steps yet -->
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

            <!-- Slide-over footer -->
            <div
                class="flex shrink-0 items-center justify-between border-t px-6 py-4"
            >
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
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeSlideOver"
                    >
                        Cancel
                    </Button>
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
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.overlay-fade-enter-active,
.overlay-fade-leave-active {
    transition: opacity 0.2s ease;
}
.overlay-fade-enter-from,
.overlay-fade-leave-to {
    opacity: 0;
}

.slide-over-enter-active,
.slide-over-leave-active {
    transition: transform 0.25s ease;
}
.slide-over-enter-from,
.slide-over-leave-to {
    transform: translateX(100%);
}
</style>
