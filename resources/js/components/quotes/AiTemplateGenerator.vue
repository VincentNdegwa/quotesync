<script setup lang="ts">
import { Loader2, Sparkles, Palette, CheckCircle2, AlertCircle, Circle } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { TemplateLayout } from '@/types/builder';

const open = defineModel<boolean>('open', { default: false });
const emit = defineEmits<{
    apply: [data: {
        layout: TemplateLayout;
        template_name: string;
        template_description: string | null;
        industry: string | null;
    }];
}>();

const description = ref('');
const industry = ref('');
const loading = ref(false);
const generated = ref<any>(null);
const error = ref<string | null>(null);

const industries = [
    'construction', 'design_creative', 'technology', 'consulting',
    'healthcare', 'real_estate', 'legal', 'education', 'photography', 'general',
];

const generate = async () => {
    if (!description.value.trim()) {
return;
}

    loading.value = true;
    error.value = null;
    generated.value = null;

    try {
        const response = await fetch('/ai/template/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({
                description: description.value,
                industry: industry.value || null,
            }),
        });

        const data = await response.json();

        if (!response.ok) {
            error.value = data.message || 'Failed to generate template. Please try again.';

            return;
        }

        generated.value = data;
    } catch (e) {
        error.value = 'Failed to generate template. Please try again.';
    } finally {
        loading.value = false;
    }
};

const apply = () => {
    if (generated.value?.layout) {
        emit('apply', {
            layout: generated.value.layout,
            template_name: generated.value.template_name,
            template_description: generated.value.template_description,
            industry: generated.value.industry,
        });
        open.value = false;
        description.value = '';
        industry.value = '';
        generated.value = null;
    }
};

const blockTypeLabel: Record<string, string> = {
    header: 'Header',
    from_to: 'From / To',
    cover_message: 'Cover Message',
    line_items: 'Line Items',
    totals: 'Totals',
    rich_text: 'Text',
    image: 'Image',
    image_row: 'Image Row',
    payment_terms: 'Payment Terms',
    timeline: 'Timeline',
    terms: 'Terms & Conditions',
    signature: 'Signature',
    divider: 'Divider',
    spacer: 'Spacer',
};

const themePreview = computed(() => {
    if (!generated.value?.layout?.theme) {
return null;
}

    const t = generated.value.layout.theme;

    return {
        primary: t.primaryColor,
        accent: t.accentColor,
        bg: t.backgroundColor,
        font: t.fontFamily,
    };
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Palette class="w-5 h-5 text-primary" />
                    Generate Template with AI
                </DialogTitle>
                <DialogDescription>
                    Describe your business and the AI will design a complete template with layout, theme, and block configurations.
                </DialogDescription>
            </DialogHeader>

            <div v-if="!generated" class="space-y-4">
                <div>
                    <Label class="text-sm font-medium mb-2 block">Business Description</Label>
                    <Textarea
                        v-model="description"
                        placeholder="e.g., We are a construction company specializing in residential solar installations. We need a professional template that highlights materials, labor costs, and payment schedules."
                        class="min-h-[120px]"
                        :disabled="loading"
                    />
                </div>

                <div>
                    <Label class="text-sm font-medium mb-2 block">Industry (optional)</Label>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="ind in industries"
                            :key="ind"
                            :variant="industry === ind ? 'default' : 'outline'"
                            class="cursor-pointer capitalize"
                            @click="industry = industry === ind ? '' : ind"
                        >
                            {{ ind.replace('_', ' ') }}
                        </Badge>
                    </div>
                </div>

                <Button @click="generate" :disabled="loading || !description.trim()" class="w-full">
                    <Loader2 v-if="loading" class="w-4 h-4 mr-2 animate-spin" />
                    <Sparkles v-else class="w-4 h-4 mr-2" />
                    {{ loading ? 'Designing your template...' : 'Generate Template' }}
                </Button>

                <div v-if="error" class="p-3 bg-destructive/10 text-destructive rounded-md text-sm">
                    {{ error }}
                </div>
            </div>

            <div v-else class="space-y-6">
                <!-- Template Info -->
                <div class="space-y-2">
                    <h3 class="font-semibold text-lg">{{ generated.template_name }}</h3>
                    <p v-if="generated.template_description" class="text-sm text-muted-foreground">{{ generated.template_description }}</p>
                    <Badge v-if="generated.industry" variant="secondary" class="capitalize">
                        {{ generated.industry?.replace('_', ' ') }}
                    </Badge>
                </div>

                <!-- Theme Preview -->
                <div v-if="themePreview" class="border rounded-lg p-4">
                    <h4 class="font-semibold mb-3">Theme Preview</h4>
                    <div class="flex items-center gap-4">
                        <div class="flex gap-2">
                            <div class="w-10 h-10 rounded-md border" :style="{ backgroundColor: themePreview.primary }" title="Primary" />
                            <div class="w-10 h-10 rounded-md border" :style="{ backgroundColor: themePreview.accent }" title="Accent" />
                            <div class="w-10 h-10 rounded-md border" :style="{ backgroundColor: themePreview.bg }" title="Background" />
                        </div>
                        <div class="text-sm text-muted-foreground">
                            <p>Font: <span class="capitalize font-medium">{{ themePreview.font }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Block List -->
                <div v-if="generated.layout?.blocks?.length" class="border rounded-lg p-4">
                    <h4 class="font-semibold mb-3">Blocks ({{ generated.layout.blocks.length }})</h4>
                    <div class="space-y-1">
                        <div
                            v-for="(block, index) in generated.layout.blocks"
                            :key="block.id"
                            class="flex items-center gap-2 text-sm py-1 px-2 rounded"
                            :class="block.locked ? 'bg-muted' : ''"
                        >
                            <span class="text-muted-foreground w-5">{{ Number(index) + 1 }}</span>
                            <CheckCircle2 v-if="block.locked" class="w-3.5 h-3.5 text-blue-500" />
                            <AlertCircle v-else-if="!block.visible" class="w-3.5 h-3.5 text-muted-foreground" />
                            <Circle v-else class="w-3.5 h-3.5 text-green-500" />
                            <span class="font-medium">{{ blockTypeLabel[block.type] || block.type }}</span>
                            <Badge v-if="block.locked" variant="outline" class="text-xs ml-auto">Required</Badge>
                            <Badge v-else-if="!block.visible" variant="outline" class="text-xs ml-auto">Hidden</Badge>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="generated = null" :disabled="loading">
                        Regenerate
                    </Button>
                    <Button @click="apply" :disabled="!generated?.layout">
                        <CheckCircle2 class="w-4 h-4 mr-2" />
                        Use This Template
                    </Button>
                </DialogFooter>
            </div>
        </DialogContent>
    </Dialog>
</template>
