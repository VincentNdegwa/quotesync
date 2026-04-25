<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import TiptapEditor from '@/components/ui/tiptap-editor/TiptapEditor.vue';
import { useEnums } from '@/composables/useEnums';
import ConfigurationLayout from '@/layouts/configuration/Layout.vue';

type Step = {
    id?: number;
    day_offset: number;
    channel: string;
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

defineOptions({
    layout: ConfigurationLayout,
});

const { enums } = useEnums();

const insertPlaceholder = (placeholder: string, stepIndex: number): void => {
    form.value.steps[stepIndex].message_template += `{${placeholder}}`;
};

const formatPlaceholder = (key: string): string => {
    return `{${key}}`;
};

const showForm = ref(false);
const editingSequence = ref<Sequence | null>(null);
const formErrors = ref<Record<string, string>>({});

const emptyStep = (): Step => ({
    day_offset: 1,
    channel: 'email',
    subject: '',
    message_template: '',
    sort_order: 0,
});

const form = ref({
    name: '',
    is_default: false,
    steps: [emptyStep()] as Step[],
});

const openCreate = (): void => {
    editingSequence.value = null;
    form.value = { name: '', is_default: false, steps: [emptyStep()] };
    formErrors.value = {};
    showForm.value = true;
};

const openEdit = (sequence: Sequence): void => {
    editingSequence.value = sequence;
    form.value = {
        name: sequence.name,
        is_default: sequence.is_default,
        steps: sequence.steps.map((s) => ({ ...s })),
    };
    formErrors.value = {};
    showForm.value = true;
};

const addStep = (): void => {
    const lastSort = form.value.steps.length > 0
        ? form.value.steps[form.value.steps.length - 1].sort_order
        : -1;
    form.value.steps.push({ ...emptyStep(), sort_order: lastSort + 1 });
};

const removeStep = (index: number): void => {
    form.value.steps.splice(index, 1);
};

const submitAction = computed(() => {
    if (editingSequence.value) {
        return `/configuration/follow-ups/${editingSequence.value.id}`;
    }
    return '/configuration/follow-ups';
});

const submitMethod = computed(() => (editingSequence.value ? 'put' : 'post'));

const channelLabel = (value: string): string => {
    return enums.followUpChannel.find((c) => c.value === value)?.label ?? value;
};
</script>

<template>
    <Head title="Configuration - Follow-ups" />

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Follow-up automation"
            description="Configure automated follow-up sequences sent to clients after a quote is delivered."
        />

        <div class="flex justify-end">
            <Button @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                New sequence
            </Button>
        </div>

        <!-- Sequence list -->
        <div v-if="sequences.length > 0" class="space-y-4">
            <Card v-for="sequence in sequences" :key="sequence.id">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <div class="flex items-center gap-2">
                        <CardTitle class="text-base font-medium">{{ sequence.name }}</CardTitle>
                        <Badge v-if="sequence.is_default" variant="default">Default</Badge>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button variant="ghost" size="sm" @click="openEdit(sequence)">Edit</Button>
                        <Link
                            :href="`/configuration/follow-ups/${sequence.id}`"
                            method="delete"
                            as="button"
                        >
                            <Button variant="ghost" size="sm" class="text-destructive">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="space-y-2">
                        <div
                            v-for="step in sequence.steps"
                            :key="step.id"
                            class="flex items-center gap-3 rounded-md border p-3 text-sm"
                        >
                            <Badge variant="outline">Day {{ step.day_offset }}</Badge>
                            <Badge variant="secondary">{{ channelLabel(step.channel) }}</Badge>
                            <span class="text-muted-foreground">{{ step.subject || 'No subject' }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-else class="rounded-md border border-dashed p-8 text-center text-sm text-muted-foreground">
            No follow-up sequences configured. Create one to automate client follow-ups.
        </div>

        <!-- Create / Edit form -->
        <Card v-if="showForm">
            <CardHeader>
                <CardTitle class="text-base">
                    {{ editingSequence ? 'Edit sequence' : 'New sequence' }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <Form
                    :action="submitAction"
                    :method="submitMethod"
                    #default="{ errors, processing }"
                    @error="(errs: Record<string, string>) => formErrors = errs"
                >
                    <div class="space-y-5">
                        <div class="space-y-2">
                            <Label for="seq-name">Name</Label>
                            <Input id="seq-name" name="name" v-model="form.name" />
                            <InputError :message="errors.name || formErrors.name" />
                        </div>

                        <div class="flex items-center gap-2">
                            <Switch
                                id="seq-default"
                                :model-value="form.is_default"
                                @update:model-value="(v: boolean) => form.is_default = v"
                            />
                            <Label for="seq-default">Set as default sequence</Label>
                            <input type="hidden" name="is_default" :value="form.is_default ? '1' : '0'" />
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <Label>Steps</Label>
                                <Button type="button" variant="outline" size="sm" @click="addStep">
                                    <Plus class="mr-1 h-3 w-3" />
                                    Add step
                                </Button>
                            </div>

                            <div
                                v-for="(step, index) in form.steps"
                                :key="index"
                                class="space-y-3 rounded-md border p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Step {{ index + 1 }}</span>
                                    <Button
                                        v-if="form.steps.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeStep(index)"
                                    >
                                        <Trash2 class="h-4 w-4 text-destructive" />
                                    </Button>
                                </div>

                                <input type="hidden" :name="`steps[${index}][id]`" :value="step.id" />
                                <input type="hidden" :name="`steps[${index}][sort_order]`" :value="index" />

                                <div class="grid grid-cols-2 gap-3">
                                    <div class="space-y-1">
                                        <Label>Day offset</Label>
                                        <Input
                                            type="number"
                                            :name="`steps[${index}][day_offset]`"
                                            v-model="step.day_offset"
                                            :min="0"
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <Label>Channel</Label>
                                        <Select v-model="step.channel">
                                            <SelectTrigger class="w-full">
                                                <SelectValue placeholder="Channel" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    v-for="ch in enums.followUpChannel"
                                                    :key="ch.value"
                                                    :value="ch.value"
                                                >
                                                    {{ ch.label }}
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <input type="hidden" :name="`steps[${index}][channel]`" :value="step.channel" />
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <Label>Subject</Label>
                                    <Input
                                        :name="`steps[${index}][subject]`"
                                        v-model="step.subject"
                                        placeholder="Follow up for {quote_number}"
                                    />
                                </div>

                                <div class="space-y-1">
                                    <Label>Message template</Label>
                                    <TiptapEditor
                                        v-model="step.message_template"
                                        placeholder="Hi {client_name}, please review {quote_link}"
                                    />
                                    <input type="hidden" :name="`steps[${index}][message_template]`" :value="step.message_template" />
                                </div>

                                <div class="space-y-1">
                                    <Label class="text-xs text-muted-foreground">Available placeholders (click to insert)</Label>
                                    <div class="flex flex-wrap gap-1">
                                        <Button
                                            v-for="(description, key) in placeholders"
                                            :key="key"
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            class="h-7 text-xs"
                                            @click="insertPlaceholder(key, index)"
                                        >
                                            {{ formatPlaceholder(key) }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button type="button" variant="outline" @click="showForm = false">Cancel</Button>
                            <Button type="submit" :disabled="processing">
                                {{ editingSequence ? 'Update sequence' : 'Create sequence' }}
                            </Button>
                        </div>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
