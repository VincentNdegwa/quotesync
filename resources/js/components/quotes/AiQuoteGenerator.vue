<script setup lang="ts">
import { Loader2, Sparkles, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{
    apply: [data: {
        sections: Array<{ title: string; line_items: any[] }>;
        cover_message: { label_text: string; context_text: string | null } | null;
        payment_terms: { label_text: string; context_text: string | null } | null;
        terms: { label_text: string; context_text: string | null } | null;
        timeline: { label_text: string; rows: Array<{ phase: string; description: string | null; start_date: string | null; end_date: string | null }> } | null;
    }];
}>();

const description = ref('');
const loading = ref(false);
const generated = ref<any>(null);
const error = ref<string | null>(null);

const generate = async () => {
    if (!description.value.trim()) {
return;
}

    loading.value = true;
    error.value = null;
    generated.value = null;

    try {
        const response = await fetch('/ai/quote/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({ description: description.value }),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = data.message || 'Failed to generate quote. Please try again.';

            return;
        }

        generated.value = data;
    } catch (e) {
        error.value = 'Failed to generate quote. Please try again.';
    } finally {
        loading.value = false;
    }
};

const apply = () => {
    if (generated.value) {
        emit('apply', {
            sections: generated.value.sections,
            cover_message: generated.value.cover_message,
            payment_terms: generated.value.payment_terms,
            terms: generated.value.terms,
            timeline: generated.value.timeline,
        });
        open.value = false;
        description.value = '';
        generated.value = null;
    }
};

const getMatchIcon = (item: any) => {
    if (item.catalog_item_id) {
return CheckCircle2;
}

    return AlertCircle;
};

const getMatchColor = (item: any) => {
    if (item.catalog_item_id) {
return 'text-green-500';
}

    return 'text-amber-500';
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Sparkles class="w-5 h-5 text-primary" />
                    Generate Quote with AI
                </DialogTitle>
                <DialogDescription>
                    Describe the job or project, and AI will generate a complete quote with line items, cover message, payment terms, and timeline.
                </DialogDescription>
            </DialogHeader>

            <div v-if="!generated" class="space-y-4">
                <div>
                    <label class="text-sm font-medium mb-2 block">Job Description</label>
                    <Textarea
                        v-model="description"
                        placeholder="e.g., Build a 3-page website with contact form, blog, and SEO optimization. Includes design, development, and 1 month of support."
                        class="min-h-[120px]"
                        :disabled="loading"
                    />
                </div>

                <Button @click="generate" :disabled="loading || !description.trim()" class="w-full">
                    <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
                    <Sparkles v-else class="w-4 h-4 mr-2" />
                    Generate Quote
                </Button>

                <div v-if="error" class="p-3 bg-destructive/10 text-destructive rounded-md text-sm">
                    {{ error }}
                </div>
            </div>

            <div v-else class="space-y-6">
                <div v-if="generated.confidence_note" class="p-3 bg-amber-50 border border-amber-200 rounded-md text-sm text-amber-800">
                    <strong>Note:</strong> {{ generated.confidence_note }}
                </div>

                <div v-for="section in generated.sections" :key="section.title" class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-3">{{ section.title }}</h3>
                    <div class="space-y-2">
                        <div v-for="item in section.line_items" :key="item.name" class="flex items-start gap-3 text-sm">
                            <component :is="getMatchIcon(item)" :class="getMatchColor(item)" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                            <div class="flex-1 min-w-0">
                                <div class="font-medium">{{ item.name }}</div>
                                <div v-if="item.description" class="text-muted-foreground text-xs mt-0.5">{{ item.description }}</div>
                                <div class="flex items-center gap-2 mt-1 text-xs text-muted-foreground">
                                    <span>{{ item.quantity }} {{ item.unit }}</span>
                                    <span>×</span>
                                    <span>{{ item.unit_price }}</span>
                                    <span>=</span>
                                    <span class="font-medium">{{ (item.quantity * item.unit_price).toFixed(2) }}</span>
                                    <Badge v-if="item.is_optional" variant="secondary" class="ml-auto">Optional</Badge>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="generated.cover_message?.context_text" class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-2">{{ generated.cover_message.label_text }}</h3>
                    <p class="text-sm text-muted-foreground">{{ generated.cover_message.context_text }}</p>
                </div>

                <div v-if="generated.payment_terms?.context_text" class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-2">{{ generated.payment_terms.label_text }}</h3>
                    <p class="text-sm text-muted-foreground">{{ generated.payment_terms.context_text }}</p>
                </div>

                <div v-if="generated.terms?.context_text" class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-2">{{ generated.terms.label_text }}</h3>
                    <p class="text-sm text-muted-foreground">{{ generated.terms.context_text }}</p>
                </div>

                <div v-if="generated.timeline?.rows?.length > 0" class="border rounded-lg p-4">
                    <h3 class="font-semibold mb-3">{{ generated.timeline.label_text }}</h3>
                    <div class="space-y-2">
                        <div v-for="row in generated.timeline.rows" :key="row.phase" class="text-sm">
                            <div class="font-medium">{{ row.phase }}</div>
                            <div v-if="row.description" class="text-muted-foreground text-xs">{{ row.description }}</div>
                            <div v-if="row.start_date || row.end_date" class="text-xs text-muted-foreground mt-0.5">
                                {{ row.start_date }} - {{ row.end_date }}
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="generated = null" :disabled="loading">
                        Regenerate
                    </Button>
                    <Button @click="apply">
                        <CheckCircle2 class="w-4 h-4 mr-2" />
                        Use This Quote
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
